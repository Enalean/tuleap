<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use BackendLogger;
use ProjectManager;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_DescriptionFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_TitleFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ArtifactCreatorAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ArtifactLinkFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\DescriptionFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\StatusFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\SynchronizedFieldsAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\TimeFrameFieldsAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\TitleFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProgramManagementProjectAdapter;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use UserManager;

class TaskBuilder
{
    public function build(): CreateProgramIncrementsTask
    {
        $user_manager = UserManager::instance();
        $program_dao  = new ProgramDao();

        $form_element_factory = Tracker_FormElementFactory::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $logger               = BackendLogger::getDefaultLogger("program_management_syslog");

        $tracker_artifact_factory       = Tracker_ArtifactFactory::instance();
        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $retrieve_user                  = new UserManagerAdapter($user_manager);
        $user_stories_planner           = new UserStoriesInMirroredProgramIncrementsPlanner(
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            $artifacts_linked_to_parent_dao,
            $tracker_artifact_factory,
            new MirroredTimeboxRetriever(new MirroredTimeboxesDao()),
            new ContentDao(),
            $logger,
            $retrieve_user,
            $artifacts_linked_to_parent_dao
        );
        $artifact_creator               = new ArtifactCreatorAdapter(
            TrackerArtifactCreator::build(
                Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
                Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                $logger,
            ),
            \TrackerFactory::instance()
        );

        $synchronized_fields_gatherer = new SynchronizedFieldsAdapter(
            new ArtifactLinkFieldAdapter($form_element_factory),
            new TitleFieldAdapter(new Tracker_Semantic_TitleFactory()),
            new DescriptionFieldAdapter(new Tracker_Semantic_DescriptionFactory()),
            new StatusFieldAdapter(new Tracker_Semantic_StatusFactory()),
            new TimeFrameFieldsAdapter(
                SemanticTimeframeBuilder::build()
            )
        );

        $mirror_creator = new ProgramIncrementsCreator(
            $transaction_executor,
            $synchronized_fields_gatherer,
            new StatusValueMapper($form_element_factory),
            $artifact_creator,
            $retrieve_user
        );

        return new CreateProgramIncrementsTask(
            new PlanningAdapter(\PlanningFactory::build(), $retrieve_user),
            $mirror_creator,
            $logger,
            new PendingArtifactCreationDao(),
            $user_stories_planner,
            $program_dao,
            new ProgramManagementProjectAdapter(ProjectManager::instance()),
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever(
                $tracker_artifact_factory,
                $form_element_factory
            )
        );
    }
}

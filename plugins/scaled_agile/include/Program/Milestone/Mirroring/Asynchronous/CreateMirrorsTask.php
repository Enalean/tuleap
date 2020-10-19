<?php
/*
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

namespace Tuleap\ScaledAgile\Program\Milestone\Mirroring\Asynchronous;

use BackendLogger;
use PFUser;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_Semantic_DescriptionFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_TitleFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ScaledAgile\Program\Milestone\MilestoneTrackerCollectionFactory;
use Tuleap\ScaledAgile\Program\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Milestone\Mirroring\CopiedValuesGatherer;
use Tuleap\ScaledAgile\Program\Milestone\Mirroring\MilestoneMirroringException;
use Tuleap\ScaledAgile\Program\Milestone\Mirroring\MirrorMilestonesCreator;
use Tuleap\ScaledAgile\Program\Milestone\SynchronizedFieldRetrievalException;
use Tuleap\ScaledAgile\Program\ProgramDao;
use Tuleap\ScaledAgile\Program\TeamProjectsCollectionBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use UserManager;
use XMLImportHelper;

class CreateMirrorsTask
{
    /**
     * @var CopiedValuesGatherer
     */
    private $copied_values_gatherer;
    /**
     * @var TeamProjectsCollectionBuilder
     */
    private $projects_collection_builder;
    /**
     * @var MilestoneTrackerCollectionFactory
     */
    private $milestone_trackers_factory;
    /**
     * @var MirrorMilestonesCreator
     */
    private $mirror_creator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;

    public function __construct(
        CopiedValuesGatherer $copied_values_gatherer,
        TeamProjectsCollectionBuilder $projects_collection_builder,
        MilestoneTrackerCollectionFactory $milestone_trackers_factory,
        MirrorMilestonesCreator $mirror_creator,
        LoggerInterface $logger,
        PendingArtifactCreationDao $pending_artifact_creation_dao
    ) {
        $this->copied_values_gatherer        = $copied_values_gatherer;
        $this->projects_collection_builder   = $projects_collection_builder;
        $this->milestone_trackers_factory    = $milestone_trackers_factory;
        $this->mirror_creator                = $mirror_creator;
        $this->logger                        = $logger;
        $this->pending_artifact_creation_dao = $pending_artifact_creation_dao;
    }

    public function createMirrors(Artifact $artifact, PFUser $user, \Tracker_Artifact_Changeset $changeset): void
    {
        try {
            $this->create($artifact, $user, $changeset);
        } catch (MilestoneTrackerRetrievalException | MilestoneMirroringException | SynchronizedFieldRetrievalException $exception) {
            $this->logger->error('Error during creation of mirror milestones', ['exception' => $exception]);
        }
    }

    /**
     * @throws MilestoneMirroringException
     * @throws MilestoneTrackerRetrievalException
     * @throws SynchronizedFieldRetrievalException
     */
    private function create(
        \Tuleap\Tracker\Artifact\Artifact $program_top_milestone_artifact,
        \PFUser $current_user,
        \Tracker_Artifact_Changeset $last_changeset
    ): void {
        $tracker                = $program_top_milestone_artifact->getTracker();

        $copied_values          = $this->copied_values_gatherer->gather(
            $last_changeset,
            $tracker
        );
        $team_projects   = $this->projects_collection_builder->getTeamProjectForAGivenProgramProject(
            $tracker->getProject()
        );
        $team_milestones = $this->milestone_trackers_factory->buildFromTeamProjects(
            $team_projects,
            $current_user
        );

        $this->mirror_creator->createMirrors($copied_values, $team_milestones, $current_user);

        $this->pending_artifact_creation_dao->deleteArtifactFromPendingCreation(
            (int) $program_top_milestone_artifact->getId(),
            (int) $current_user->getId()
        );
    }

    public static function build(): self
    {
        $user_manager     = UserManager::instance();
        $program_dao   = new ProgramDao();
        $planning_factory = \PlanningFactory::build();

        $form_element_factory = \Tracker_FormElementFactory::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $logger               = BackendLogger::getDefaultLogger("scaled_agile_syslog");

        $artifact_creator = TrackerArtifactCreator::build(
            Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
            Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
            $logger
        );

        $synchronized_fields_gatherer = new \Tuleap\ScaledAgile\Program\Milestone\SynchronizedFieldsGatherer(
            $form_element_factory,
            new Tracker_Semantic_TitleFactory(),
            new Tracker_Semantic_DescriptionFactory(),
            new Tracker_Semantic_StatusFactory(),
            new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder(
                new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao(),
                $form_element_factory
            )
        );

        $mirror_creator = new \Tuleap\ScaledAgile\Program\Milestone\Mirroring\MirrorMilestonesCreator(
            $transaction_executor,
            $synchronized_fields_gatherer,
            new \Tuleap\ScaledAgile\Program\Milestone\Mirroring\Status\StatusValueMapper(
                new \Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher(new XMLImportHelper($user_manager))
            ),
            $artifact_creator
        );

        return new self(
            new \Tuleap\ScaledAgile\Program\Milestone\Mirroring\CopiedValuesGatherer(
                $synchronized_fields_gatherer
            ),
            new TeamProjectsCollectionBuilder(
                $program_dao,
                ProjectManager::instance()
            ),
            new \Tuleap\ScaledAgile\Program\Milestone\MilestoneTrackerCollectionFactory($planning_factory),
            $mirror_creator,
            $logger,
            new PendingArtifactCreationDao()
        );
    }
}

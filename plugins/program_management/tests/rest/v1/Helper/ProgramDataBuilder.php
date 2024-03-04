<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\REST\v1\Helper;

use Psr\Log\NullLogger;
use REST_TestDataBuilder;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\DB\ThereIsAnOngoingTransactionChecker;
use Tuleap\ProgramManagement\Adapter\Events\ArtifactCreatedProxy;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationDispatcher;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessorBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDaoProject;
use Tuleap\ProgramManagement\Adapter\Team\TeamAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Team\Creation\Team;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\Queue\QueueFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use UserManager;

final class ProgramDataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_TEAM_NAME        = 'team';
    public const PROJECT_PROGRAM_NAME     = 'program';
    private const MAX_ATTEMPTS_ASYNC_WAIT = 10;

    private ProgramIncrementCreationDispatcher $creation_dispatcher;
    private ?\PFUser $user;
    private \Tracker $program_increment;
    private \Tracker $user_story;
    private \Tracker $feature;
    private \Tracker $iteration;
    private \Tracker $mirrored_iteration;
    private \Tracker_ArtifactFactory $artifact_factory;
    private ProgramIncrementsDAO $program_increment_DAO;

    public function setUp(): void
    {
        echo 'Setup Program Management REST Tests configuration' . PHP_EOL;

        $user_manager                 = UserManager::instance();
        $user_adapter                 = new UserManagerAdapter($user_manager);
        $this->artifact_factory       = Tracker_ArtifactFactory::instance();
        $team_dao                     = new TeamDao();
        $program_dao                  = new ProgramDaoProject();
        $this->program_increment_DAO  = new ProgramIncrementsDAO();
        $project_permissions_verifier = new ProjectPermissionVerifier(RetrieveUserStub::withGenericUser());

        $team_builder = new TeamAdapter(
            new ProjectManagerAdapter($this->project_manager, $user_adapter),
            $program_dao,
            new ExplicitBacklogDao(),
            $user_adapter
        );

        $null_logger               = new NullLogger();
        $this->creation_dispatcher = new ProgramIncrementCreationDispatcher(
            $null_logger,
            new QueueFactory($null_logger, new ThereIsAnOngoingTransactionChecker()),
            new ProgramIncrementCreationProcessorBuilder()
        );

        $this->user = $user_manager->getUserByUserName(\TestDataBuilder::TEST_USER_1_NAME);

        $program_project = $this->getProjectByShortName(self::PROJECT_PROGRAM_NAME);
        $team_project    = $this->getProjectByShortName(self::PROJECT_TEAM_NAME);

        $program = ProgramForAdministrationIdentifier::fromProject(
            $team_dao,
            $project_permissions_verifier,
            UserProxy::buildFromPFUser($this->user),
            ProjectProxy::buildFromProject($program_project)
        );

        $team = Team::buildForRestTest($team_builder, (int) $team_project->getID());
        $team_dao->save(TeamCollection::fromProgramAndTeams($program, $team));

        $tracker_factory = \TrackerFactory::instance();
        assert($tracker_factory instanceof \TrackerFactory);
        $program_trackers = $tracker_factory->getTrackersByGroupId((int) $program_project->getID());
        $team_trackers    = $tracker_factory->getTrackersByGroupId((int) $team_project->getID());

        $this->feature            = $this->getTrackerByName($program_trackers, "features");
        $this->user_story         = $this->getTrackerByName($team_trackers, "story");
        $this->iteration          = $this->getTrackerByName($program_trackers, "iteration");
        $this->mirrored_iteration = $this->getTrackerByName($team_trackers, "sprint");

        $this->program_increment = $this->getTrackerByName($program_trackers, "pi");

        $this->linkFeatureAndUserStories();
        $this->linkProgramIncrementToMirroredRelease();
        $this->linkProgramIncrementToIteration();
        $is_success  = false;
        $nb_attempts = 0;
        while (! $is_success) {
            try {
                $this->linkUserStoryToIteration();
            } catch (\LogicException $exception) {
                if ($nb_attempts >= self::MAX_ATTEMPTS_ASYNC_WAIT) {
                    throw $exception;
                }
                $nb_attempts++;
                sleep(1);
                continue;
            }
            $is_success = true;
        }
    }

    private function linkFeatureAndUserStories(): void
    {
        $feature_list    = $this->artifact_factory->getArtifactsByTrackerId($this->feature->getId());
        $user_story_list = $this->artifact_factory->getArtifactsByTrackerId($this->user_story->getId());

        $featureA = $this->getArtifactByTitle($feature_list, "FeatureA");
        $featureB = $this->getArtifactByTitle($feature_list, "FeatureB");
        $us1      = $this->getArtifactByTitle($user_story_list, "US1");
        $us2      = $this->getArtifactByTitle($user_story_list, "US2");

        $featureA_artifact_link = $featureA->getAnArtifactLinkField($this->user);
        assert($featureA_artifact_link instanceof \Tracker_FormElement_Field_ArtifactLink);
        $fieldsA_data                                                     = [];
        $fieldsA_data[$featureA_artifact_link->getId()]['new_values']     = (string) $us1->getId();
        $fieldsA_data[$featureA_artifact_link->getId()]['type']           = Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD;
        $fieldsA_data[$featureA_artifact_link->getId()]['types']          = [
            $us1->getId() => Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD,
        ];
        $fieldsA_data[$featureA_artifact_link->getId()]['removed_values'] = [];

        $featureA->createNewChangeset($fieldsA_data, "", $this->user);

        $featureB_artifact_link = $featureB->getAnArtifactLinkField($this->user);
        assert($featureB_artifact_link instanceof \Tracker_FormElement_Field_ArtifactLink);
        $fieldsB_data                                                     = [];
        $fieldsB_data[$featureB_artifact_link->getId()]['new_values']     = (string) $us2->getId();
        $fieldsB_data[$featureB_artifact_link->getId()]['removed_values'] = [];
        $featureA->createNewChangeset($fieldsB_data, "", $this->user);
    }

    public function linkProgramIncrementToMirroredRelease(): void
    {
        $program_increment_list = $this->artifact_factory->getArtifactsByTrackerId($this->program_increment->getId());

        $pi = $this->getArtifactByTitle($program_increment_list, "PI");

        $tracker_event              = new ArtifactCreated($pi, $pi->getLastChangeset(), $this->user);
        $created_event              = ArtifactCreatedProxy::fromArtifactCreated($tracker_event);
        $program_increment_creation = ProgramIncrementCreation::fromArtifactCreatedEvent(
            $this->program_increment_DAO,
            $created_event
        );
        if (! $program_increment_creation) {
            return;
        }
        $this->creation_dispatcher->dispatchCreation($program_increment_creation);
    }

    /**
     * @param \Tracker[] $trackers
     */
    private function getTrackerByName(array $trackers, string $name): \Tracker
    {
        foreach ($trackers as $tracker) {
            if ($tracker->getItemName() === $name) {
                return $tracker;
            }
        }

        throw new \LogicException("Can not find asked tracker $name");
    }

    /**
     * @param Artifact[] $artifacts
     */
    private function getArtifactByTitle(array $artifacts, string $title): Artifact
    {
        foreach ($artifacts as $artifact) {
            if ($artifact->getTitle() === $title) {
                return $artifact;
            }
        }

        throw new \LogicException("Can not find asked artifact with title $title");
    }

    private function getProjectByShortName(string $short_name): \Project
    {
        $program_project = $this->project_manager->getProjectByUnixName($short_name);
        if (! $program_project) {
            throw new \LogicException(sprintf('Could not find project with short name %s', $short_name));
        }

        return $program_project;
    }

    private function linkProgramIncrementToIteration(): void
    {
        $program_increment_list = $this->artifact_factory->getArtifactsByTrackerId($this->program_increment->getId());
        $iteration_list         = $this->artifact_factory->getArtifactsByTrackerId($this->iteration->getId());

        $iteration = $this->getArtifactByTitle($iteration_list, "iteration");
        $pi        = $this->getArtifactByTitle($program_increment_list, "PI");

        $pi_artifact_link_field = $pi->getAnArtifactLinkField($this->user);
        assert($pi_artifact_link_field instanceof \Tracker_FormElement_Field_ArtifactLink);
        $data                                                     = [];
        $data[$pi_artifact_link_field->getId()]['new_values']     = (string) $iteration->getId();
        $data[$pi_artifact_link_field->getId()]['type']           = Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD;
        $data[$pi_artifact_link_field->getId()]['types']          = [
            $iteration->getId() => Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD,
        ];
        $data[$pi_artifact_link_field->getId()]['removed_values'] = [];
        $pi->createNewChangeset($data, "", $this->user);
    }

    private function linkUserStoryToIteration(): void
    {
        $mirrored_iteration_list = $this->artifact_factory->getArtifactsByTrackerId($this->mirrored_iteration->getId());
        $user_story_list         = $this->artifact_factory->getArtifactsByTrackerId($this->user_story->getId());

        $mirrored_iteration = $this->getArtifactByTitle($mirrored_iteration_list, "iteration");
        $us1                = $this->getArtifactByTitle($user_story_list, "US1");

        $iteration_artifact_link_field = $mirrored_iteration->getAnArtifactLinkField($this->user);
        assert($iteration_artifact_link_field instanceof \Tracker_FormElement_Field_ArtifactLink);
        $data                                                            = [];
        $data[$iteration_artifact_link_field->getId()]['new_values']     = (string) $us1->getId();
        $data[$iteration_artifact_link_field->getId()]['nature']         = "";
        $data[$iteration_artifact_link_field->getId()]['natures']        = [];
        $data[$iteration_artifact_link_field->getId()]['removed_values'] = [];
        $mirrored_iteration->createNewChangeset($data, "", $this->user);
    }
}

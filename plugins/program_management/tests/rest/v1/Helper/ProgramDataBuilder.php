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
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\CreateProgramIncrementsRunner;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\TaskBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Team\TeamAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;
use Tuleap\ProgramManagement\Domain\Team\Creation\Team;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Queue\QueueFactory;
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

class ProgramDataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_TEAM_NAME    = 'team';
    public const PROJECT_PROGRAM_NAME = 'program';
    /**
     * @var ReplicationDataAdapter
     */
    public $replication_data_adapter;
    /**
     * @var CreateProgramIncrementsRunner
     */
    private $runner;
    /**
     * @var \PFUser|null
     */
    private $user;
    /**
     * @var \Tracker
     */
    private $program_increment;
    /**
     * @var \Tracker
     */
    private $user_story;
    /**
     * @var \Tracker
     */
    private $feature;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var \Project|null
     */
    private $team;
    /**
     * @var \Project|null
     */
    private $program;

    public function setUp(): void
    {
        echo 'Setup Program Management REST Tests configuration' . PHP_EOL;

        $team_adapter    = new TeamAdapter($this->project_manager, new ProgramDao(), new ExplicitBacklogDao());
        $program_adapter = new ProgramAdapter(
            $this->project_manager,
            new ProjectAccessChecker(new RestrictedUserCanAccessProjectVerifier(), \EventManager::instance()),
            new ProgramDao()
        );

        $this->replication_data_adapter = new ReplicationDataAdapter(
            Tracker_ArtifactFactory::instance(),
            UserManager::instance(),
            new PendingArtifactCreationDao(),
            Tracker_Artifact_ChangesetFactoryBuilder::build()
        );
        $this->runner                   = new CreateProgramIncrementsRunner(
            new NullLogger(),
            new QueueFactory(new NullLogger()),
            new ReplicationDataAdapter(
                Tracker_ArtifactFactory::instance(),
                UserManager::instance(),
                new PendingArtifactCreationDao(),
                Tracker_Artifact_ChangesetFactoryBuilder::build()
            ),
            new TaskBuilder()
        );

        $this->user = \UserManager::instance()->getUserByUserName(\TestDataBuilder::TEST_USER_1_NAME);

        $this->program = $this->project_manager->getProjectByUnixName(self::PROJECT_PROGRAM_NAME);
        $this->team    = $this->project_manager->getProjectByUnixName(self::PROJECT_TEAM_NAME);

        $team_dao = new TeamDao();
        $team_dao->save(
            new TeamCollection(
                [Team::buildForRestTest($team_adapter, (int) $this->team->getID(), $this->user)],
                ToBeCreatedProgram::fromId($program_adapter, (int) $this->program->getID(), $this->user)
            )
        );

        $tracker_factory = \TrackerFactory::instance();
        assert($tracker_factory instanceof \TrackerFactory);
        $program_trackers = $tracker_factory->getTrackersByGroupId((int) $this->program->getGroupId());
        $team_trackers    = $tracker_factory->getTrackersByGroupId((int) $this->team->getGroupId());

        $this->feature    = $this->getTrackerByName($program_trackers, "features");
        $this->user_story = $this->getTrackerByName($team_trackers, "story");

        $this->program_increment = $this->getTrackerByName($program_trackers, "pi");

        $this->artifact_factory = \Tracker_ArtifactFactory::instance();

        $this->linkFeatureAndUserStories();
        $this->linkProgramIncrementToMirroredRelease();
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
        $fieldsA_data[$featureA_artifact_link->getId()]['nature']         = Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD;
        $fieldsA_data[$featureA_artifact_link->getId()]['natures']        = [$us1->getId() => Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD];
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
        $dao                    = new PendingArtifactCreationDao();
        $program_increment_list = $this->artifact_factory->getArtifactsByTrackerId($this->program_increment->getId());

        $pi = $this->getArtifactByTitle($program_increment_list, "PI");
        $dao->addArtifactToPendingCreation((int) $pi->getId(), (int) $pi->getSubmittedBy(), (int) $pi->getLastChangeset()->getId());
        $replication_data = $this->replication_data_adapter->buildFromArtifactAndUserId(
            $pi->getId(),
            (int) $pi->getSubmittedBy()
        );

        if ($replication_data === null) {
            return;
        }

        $this->runner->processProgramIncrementCreation($replication_data);
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
}

<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use TuleapTestCase;

require_once dirname(__FILE__) .'/bootstrap.php';

class ConfigConformanceAsserterTest extends TuleapTestCase {

    /** @var ConfigConformanceAsserter */
    private $validator;

    /** @var \Tracker_Artifact */
    private $artifact_outside_of_project;

    /** @var \Tracker_Artifact */
    private $execution_artifact;

    /** @var \Tracker_Artifact */
    private $another_execution_artifact;

    /** @var \Tracker_Artifact */
    private $campaign_artifact;

    /** @var \Tracker_Artifact */
    private $definition_artifact;

    private $user_id                      = 100;
    private $project_id                   = 101;
    private $campaign_tracker_id          = 444;
    private $execution_tracker_id         = 555;
    private $definition_tracker_id        = 666;
    private $another_project_id           = 102;
    private $another_execution_tracker_id = 666;

    public function setUp() {
        parent::setUp();
        $project         = stub('Project')->getId()->returns($this->project_id);
        $another_project = stub('Project')->getId()->returns($this->another_project_id);

        $campaign_tracker = aTracker()
            ->withId($this->campaign_tracker_id)
            ->withProject($project)
            ->build();

        $definition_tracker = aTracker()
            ->withId($this->definition_tracker_id)
            ->withProject($project)
            ->build();

        $execution_tracker = aTracker()
            ->withId($this->execution_tracker_id)
            ->withProject($project)
            ->build();

        $another_execution_tracker = aTracker()
            ->withId($this->another_execution_tracker_id)
            ->withProject($another_project)
            ->build();

        $config = mock('Tuleap\\Trafficlights\\Config');
        stub($config)
            ->getCampaignTrackerId($project)
            ->returns($campaign_tracker->getId());
        stub($config)
            ->getTestDefinitionTrackerId($project)
            ->returns($definition_tracker->getId());
        stub($config)
            ->getTestExecutionTrackerId($project)
            ->returns($execution_tracker->getId());
        stub($config)
            ->getTestExecutionTrackerId($another_project)
            ->returns($another_execution_tracker->getId());

        $this->validator = new ConfigConformanceValidator($config);

        $this->user = aUser()->withId($this->user_id)->build();


        $this->campaign_artifact = mock('Tracker_Artifact');
        $this->definition_artifact = mock('Tracker_Artifact');
        $this->execution_artifact = mock('Tracker_Artifact');
        $this->another_execution_artifact = mock('Tracker_Artifact');
        $this->artifact_outside_of_project = mock('Tracker_Artifact');

        # Stub campaign methods
        stub($this->campaign_artifact)
            ->getTracker()
            ->returns($campaign_tracker);
        stub($this->campaign_artifact)
            ->getLinkedArtifacts($this->user)
            ->returns(array($this->execution_artifact));

        # Stub definition methods
        stub($this->definition_artifact)
            ->getTracker()
            ->returns($definition_tracker);

        # Stub execution methods
        stub($this->execution_artifact)
            ->getTracker()
            ->returns($execution_tracker);
        stub($this->execution_artifact)
            ->getLinkedArtifacts($this->user)
            ->returns(array());

        # Stub other execution methods
        stub($this->another_execution_artifact)
            ->getTracker()
            ->returns($another_execution_tracker);
        stub($this->another_execution_artifact)
            ->getLinkedArtifacts($this->user)
            ->returns(array($this->campaign_artifact));

        # Stub execution out of project methods
        stub($this->artifact_outside_of_project)
            ->getTracker()
            ->returns(
                aTracker()
                    ->withId(111)
                    ->withProject($another_project)
                    ->build()
            );

    }

    public function itReturnsFalseWhenProjectHasNoCampaignTracker() {
        $this->assertFalse(
            $this->validator->isArtifactACampaign($this->artifact_outside_of_project)
        );
    }

    public function itReturnsFalseWhenTrackerIsNotACampaignTracker() {
        $this->assertFalse(
            $this->validator->isArtifactACampaign($this->execution_artifact)
        );
    }

    public function itReturnsTrueWhenTrackerIsACampaignTracker() {
        $this->assertTrue(
            $this->validator->isArtifactACampaign($this->campaign_artifact)
        );
    }

    public function itReturnsTrueWhenCampaignIsLinkedToExecution() {
        $this->assertTrue(
            $this->validator->isArtifactAnExecutionOfCampaign(
                $this->user,
                $this->execution_artifact,
                $this->campaign_artifact
            )
        );
    }

    public function itReturnsTrueWhenExecutionIsLinkedToCampaign()
    {
        $this->assertTrue(
            $this->validator->isArtifactAnExecutionOfCampaign(
                $this->user,
                $this->another_execution_artifact,
                $this->campaign_artifact
            )
        );
    }

    public function itReturnsFalseWhenExecutionDoesNotBelongsToCampaign() {
        $this->assertFalse(
            $this->validator->isArtifactAnExecutionOfCampaign(
                $this->user,
                $this->artifact_outside_of_project,
                $this->campaign_artifact
            )
        );
    }

    public function itReturnsTrueWhenExecutionBelongsToDefinition() {
        $this->assertTrue(
            $this->validator->isArtifactAnExecutionOfDefinition(
                $this->execution_artifact,
                $this->definition_artifact
            )
        );
    }

    public function itReturnsFalseWhenExecutionDoesNotBelongsToDefinition() {
        $this->assertFalse(
            $this->validator->isArtifactAnExecutionOfDefinition(
                $this->another_execution_artifact,
                $this->definition_artifact
            )
        );
    }
}

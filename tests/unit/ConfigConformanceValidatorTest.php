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

namespace Tuleap\Testing;

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

    private $project_id                   = 101;
    private $campaign_tracker_id          = 444;
    private $execution_tracker_id         = 555;
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

        $execution_tracker = aTracker()
            ->withId($this->execution_tracker_id)
            ->withProject($project)
            ->build();

        $another_execution_tracker = aTracker()
            ->withId($this->another_execution_tracker_id)
            ->withProject($another_project)
            ->build();

        $config = mock('Tuleap\\Testing\\Config');
        stub($config)
            ->getCampaignTrackerId($project)
            ->returns($campaign_tracker->getId());
        stub($config)
            ->getTestExecutionTrackerId($project)
            ->returns($execution_tracker->getId());
        stub($config)
            ->getTestExecutionTrackerId($another_project)
            ->returns($another_execution_tracker->getId());

        $this->validator = new ConfigConformanceValidator($config);

        $this->artifact_outside_of_project = anArtifact()
            ->withTracker(
                aTracker()
                    ->withId(111)
                    ->withProject($another_project)
                    ->build()
            )->build();

        $this->execution_artifact = anArtifact()
            ->withTracker($execution_tracker)
            ->build();

        $this->another_execution_artifact = anArtifact()
            ->withTracker($another_execution_tracker)
            ->build();

        $this->campaign_artifact = anArtifact()
            ->withTracker($campaign_tracker)
            ->build();
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

    public function itReturnsTrueWhenExecutionBelongsToCampaign() {
        $this->assertTrue(
            $this->validator->isArtifactAnExecutionOfCampaign(
                $this->execution_artifact,
                $this->campaign_artifact
            )
        );
    }

    public function itReturnsFalseWhenExecutionDoesNotBelongsToCampaign() {
        $this->assertFalse(
            $this->validator->isArtifactAnExecutionOfCampaign(
                $this->another_execution_artifact,
                $this->campaign_artifact
            )
        );
    }
}
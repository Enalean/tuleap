<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\TestManagement\Administration\StepFieldUsageDetector;
use Tuleap\TestManagement\Event\GetMilestone;

class AdminControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $globals;
    /** @var Project */
    private $project;
    /** @var AdminController */
    private $admin_controller;
    /** @var \Codendi_Request */
    private $request;
    /** @var Config */
    private $config;
    /** @var \TrackerFactory */
    private $tracker_factory;
    /** @var \EventManager */
    private $event_manager;
    /** @var \CSRFSynchronizerToken */
    private $csrf_token;
    /** @var StepFieldUsageDetector */
    private $step_field_usage_detector;
    /** @var Tracker */
    private $campaign_tracker;
    /** @var Tracker */
    private $definition_tracker;
    /** @var Tracker */
    private $execution_tracker;
    /** @var Tracker */
    private $issue_tracker;

    const PROJECT_ID = 104;
    const CAMPAIGN_TRACKER_ID = 531;
    const DEFINITION_TRACKER_ID = 532;
    const EXECUTION_TRACKER_ID = 533;
    const ISSUE_TRACKER_ID = 534;

    public function setUp()
    {
        parent::setUp();
        $this->globals = $GLOBALS;
        $GLOBALS       = [];

        $this->setUpTrackers();

        $this->tracker_factory           = Mockery::mock(\TrackerFactory::class);
        $this->config                    = Mockery::mock(Config::class);
        $this->step_field_usage_detector = Mockery::mock(StepFieldUsageDetector::class);

        $this->setUpRequest();

        $this->event_manager = Mockery::mock(\EventManager::class);
        $get_milestone_event = Mockery::mock(GetMilestone::class);
        $this->event_manager->shouldReceive('processEvent', $get_milestone_event);

        $this->csrf_token = Mockery::mock(\CSRFSynchronizerToken::class);

        $this->admin_controller = new AdminController(
            $this->request,
            $this->config,
            $this->tracker_factory,
            $this->event_manager,
            $this->csrf_token,
            $this->step_field_usage_detector
        );
    }

    protected function tearDown()
    {
        $GLOBALS = $this->globals;
        parent::tearDown();
    }

    private function setUpTrackers()
    {
        $this->campaign_tracker = Mockery::mock(Tracker::class);
        $this->campaign_tracker->shouldReceive('getId')->andReturn(self::CAMPAIGN_TRACKER_ID);
        $this->definition_tracker = Mockery::mock(Tracker::class);
        $this->definition_tracker->shouldReceive('getId')->andReturn(self::DEFINITION_TRACKER_ID);
        $this->execution_tracker = Mockery::mock(Tracker::class);
        $this->execution_tracker->shouldReceive('getId')->andReturn(self::EXECUTION_TRACKER_ID);
        $this->issue_tracker = Mockery::mock(Tracker::class);
        $this->issue_tracker->shouldReceive('getId')->andReturn(self::ISSUE_TRACKER_ID);
    }

    private function setUpRequest()
    {
        $this->request = Mockery::mock(\Codendi_Request::class);
        $this->project = Mockery::mock(\Project::class);
        $this->project->shouldReceive('getID')->andReturn(self::PROJECT_ID);
        $current_user = Mockery::mock(\PFUser::class);
        $this->request->shouldReceive(
            [
                'getProject'     => $this->project,
                'getCurrentUser' => $current_user
            ]
        );
        $this->request->shouldReceive('getValidated')->withArgs(
            ['milestone_id', Mockery::any(), Mockery::any()]
        )->andReturn(0);
    }

    public function testUpdateWithoutChange()
    {
        $project_trackers = [
            $this->campaign_tracker,
            $this->definition_tracker,
            $this->execution_tracker,
            $this->issue_tracker
        ];
        $this->setUpProjectTrackers($project_trackers);

        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(self::CAMPAIGN_TRACKER_ID);
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(
            self::DEFINITION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => self::CAMPAIGN_TRACKER_ID,
                'getTestDefinitionTrackerId' => self::DEFINITION_TRACKER_ID,
                'getTestExecutionTrackerId'  => self::EXECUTION_TRACKER_ID,
                'getIssueTrackerId'          => self::ISSUE_TRACKER_ID
            ]
        );

        $this->step_field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->withArgs(
            [
                $this->project,
                self::CAMPAIGN_TRACKER_ID,
                self::DEFINITION_TRACKER_ID,
                self::EXECUTION_TRACKER_ID,
                self::ISSUE_TRACKER_ID
            ]
        );

        $this->admin_controller->update();
    }

    public function testUpdateWhenDefinitionTrackerCantBeEdited()
    {
        $project_trackers = [
            $this->campaign_tracker,
            $this->definition_tracker,
            $this->execution_tracker,
            $this->issue_tracker
        ];
        $this->setUpProjectTrackers($project_trackers);

        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(self::CAMPAIGN_TRACKER_ID);
        // No test_definition_tracker_id is provided
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => self::CAMPAIGN_TRACKER_ID,
                'getTestDefinitionTrackerId' => self::DEFINITION_TRACKER_ID,
                'getTestExecutionTrackerId'  => self::EXECUTION_TRACKER_ID,
                'getIssueTrackerId'          => self::ISSUE_TRACKER_ID
            ]
        );

        $this->step_field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(true);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->withArgs(
            [
                $this->project,
                self::CAMPAIGN_TRACKER_ID,
                self::DEFINITION_TRACKER_ID,
                self::EXECUTION_TRACKER_ID,
                self::ISSUE_TRACKER_ID
            ]
        );

        $this->admin_controller->update();
    }

    /**
     * @param $project_trackers
     */
    private function setUpProjectTrackers($project_trackers)
    {
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(self::PROJECT_ID)->andReturn(
            $project_trackers
        );
    }
}

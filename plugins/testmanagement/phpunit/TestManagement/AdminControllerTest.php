<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use Tuleap\GlobalResponseMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\TestManagement\Administration\StepFieldUsageDetector;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Event\GetMilestone;
use Valid_UInt;

class AdminControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    private $globals;
    /** @var Project */
    private $project;
    /** @var AdminController */
    private $admin_controller;
    /** @var \Codendi_Request */
    private $request;
    /** @var Config */
    private $config;
    /** @var \EventManager */
    private $event_manager;
    /** @var \CSRFSynchronizerToken */
    private $csrf_token;
    /** @var StepFieldUsageDetector */
    private $step_field_usage_detector;
    /** @var TrackerChecker */
    private $tracker_checker;

    public const PROJECT_ID = 104;
    public const CAMPAIGN_TRACKER_ID = 531;
    public const DEFINITION_TRACKER_ID = 532;
    public const EXECUTION_TRACKER_ID = 533;
    public const ISSUE_TRACKER_ID = 534;

    public function setUp(): void
    {
        parent::setUp();
        $this->globals = $GLOBALS;
        $GLOBALS       = [];
        $GLOBALS['Response'] = Mockery::spy(BaseLayout::class);

        $this->config                    = Mockery::mock(Config::class);
        $this->step_field_usage_detector = Mockery::mock(StepFieldUsageDetector::class);

        $this->setUpRequest();

        $this->event_manager = Mockery::mock(\EventManager::class);
        $get_milestone_event = Mockery::mock(GetMilestone::class);
        $this->event_manager->shouldReceive('processEvent', $get_milestone_event);

        $this->csrf_token = Mockery::mock(\CSRFSynchronizerToken::class);

        $this->tracker_checker = Mockery::mock(TrackerChecker::class);

        $this->admin_controller = new AdminController(
            $this->request,
            $this->config,
            $this->event_manager,
            $this->csrf_token,
            $this->step_field_usage_detector,
            $this->tracker_checker,
            new Valid_UInt()
        );
    }

    protected function tearDown(): void
    {
        $GLOBALS = $this->globals;
        parent::tearDown();
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

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->admin_controller->update();
    }

    public function testUpdateWhenDefinitionTrackerCantBeEdited()
    {
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

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed');

        $this->admin_controller->update();
    }

    public function testUpdateNotDoneWhenATrackerIdIsNotSetAtInitialConfiguration()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(self::CAMPAIGN_TRACKER_ID);
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(999);
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => false,
                'getTestDefinitionTrackerId' => false,
                'getTestExecutionTrackerId'  => false,
                'getIssueTrackerId'          => false,
            ]
        );

        $this->step_field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')
            ->with($this->project, 999)
            ->andThrow(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')
            ->with($this->project, self::EXECUTION_TRACKER_ID)
            ->once();

        $this->admin_controller->update();
    }

    public function testUpdateReturnWrongIdFeedbackIfTrackerIdIsInvalid()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn('oui');
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

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(1);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->admin_controller->update();
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['warning', 'The tracker id oui is not a valid id']);
    }
}

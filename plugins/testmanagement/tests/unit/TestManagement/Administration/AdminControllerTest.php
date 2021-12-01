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

namespace Tuleap\TestManagement\Administration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\GlobalResponseMock;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Event\GetMilestone;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\TestManagement\TrackerDefinitionNotValidException;
use Tuleap\TestManagement\TrackerExecutionNotValidException;
use Valid_UInt;

class AdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

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
    /** @var FieldUsageDetector */
    private $field_usage_detector;
    /** @var TrackerChecker */
    private $tracker_checker;

    public const PROJECT_ID                     = 104;
    public const ORIGINAL_CAMPAIGN_TRACKER_ID   = 531;
    public const ORIGINAL_DEFINITION_TRACKER_ID = 532;
    public const ORIGINAL_EXECUTION_TRACKER_ID  = 533;
    public const ORIGINAL_ISSUE_TRACKER_ID      = 534;
    public const NEW_CAMPAIGN_TRACKER_ID        = 535;
    public const NEW_DEFINITION_TRACKER_ID      = 536;
    public const NEW_EXECUTION_TRACKER_ID       = 537;
    public const NEW_ISSUE_TRACKER_ID           = 538;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AdminTrackersRetriever
     */
    private $tracker_retriever;

    public function setUp(): void
    {
        parent::setUp();

        $this->config               = Mockery::mock(Config::class);
        $this->field_usage_detector = Mockery::mock(FieldUsageDetector::class);

        $this->setUpRequest();

        $this->event_manager = Mockery::mock(\EventManager::class);
        $get_milestone_event = Mockery::mock(GetMilestone::class);
        $this->event_manager->shouldReceive('processEvent', $get_milestone_event);

        $this->csrf_token = Mockery::mock(\CSRFSynchronizerToken::class);

        $this->tracker_checker   = Mockery::mock(TrackerChecker::class);
        $this->tracker_retriever = Mockery::mock(AdminTrackersRetriever::class);

        $this->admin_controller = new AdminController(
            $this->request,
            $this->config,
            $this->event_manager,
            $this->csrf_token,
            $this->field_usage_detector,
            $this->tracker_checker,
            new Valid_UInt(),
            $this->tracker_retriever
        );
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
                'getCurrentUser' => $current_user,
            ]
        );
        $this->request->shouldReceive('getValidated')->withArgs(
            ['milestone_id', Mockery::any(), Mockery::any()]
        )->andReturn(0);
    }

    public function testUpdateWithoutChange()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(
            self::NEW_CAMPAIGN_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(
            self::NEW_DEFINITION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => self::ORIGINAL_CAMPAIGN_TRACKER_ID,
                'getTestDefinitionTrackerId' => self::ORIGINAL_DEFINITION_TRACKER_ID,
                'getTestExecutionTrackerId'  => self::ORIGINAL_EXECUTION_TRACKER_ID,
                'getIssueTrackerId'          => self::ORIGINAL_ISSUE_TRACKER_ID,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->withArgs(
            [
                $this->project,
                self::NEW_CAMPAIGN_TRACKER_ID,
                self::NEW_DEFINITION_TRACKER_ID,
                self::NEW_EXECUTION_TRACKER_ID,
                self::NEW_ISSUE_TRACKER_ID,
            ]
        );

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed')->once();
        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed')->once();

        $this->admin_controller->update();
    }

    public function testUpdateWhenDefinitionTrackerCantBeEdited()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(
            self::NEW_CAMPAIGN_TRACKER_ID
        );
        // No test_definition_tracker_id is provided
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => self::ORIGINAL_CAMPAIGN_TRACKER_ID,
                'getTestDefinitionTrackerId' => self::ORIGINAL_DEFINITION_TRACKER_ID,
                'getTestExecutionTrackerId'  => self::ORIGINAL_EXECUTION_TRACKER_ID,
                'getIssueTrackerId'          => self::ORIGINAL_ISSUE_TRACKER_ID,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(true);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->withArgs(
            [
                $this->project,
                self::NEW_CAMPAIGN_TRACKER_ID,
                self::ORIGINAL_DEFINITION_TRACKER_ID,
                self::NEW_EXECUTION_TRACKER_ID,
                self::NEW_ISSUE_TRACKER_ID,
            ]
        );

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed');
        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed');

        $this->admin_controller->update();
    }

    public function testUpdateNotDoneWhenATrackerIdIsNotSetAtInitialConfiguration()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(
            self::NEW_CAMPAIGN_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(999);
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => false,
                'getTestDefinitionTrackerId' => false,
                'getTestExecutionTrackerId'  => false,
                'getIssueTrackerId'          => false,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, 999)
            ->andThrow(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID)
            ->once();

        $this->admin_controller->update();
    }

    public function testUpdateNotDoneWhenDefinitionTrackerHasNoStepDefinitionField()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(
            self::NEW_CAMPAIGN_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(999);
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => false,
                'getTestDefinitionTrackerId' => false,
                'getTestExecutionTrackerId'  => false,
                'getIssueTrackerId'          => false,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, 999)
            ->andThrow(TrackerDefinitionNotValidException::class);

        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID)
            ->once();

        $GLOBALS['Response']->method('addFeedback')->withConsecutive(
            ['warning', 'The tracker id 999 does not have step definition field'],
            [\Feedback::ERROR]
        );

        $this->admin_controller->update();
    }

    public function testUpdateNotDoneWhenExecutionTrackerHasNoStepExecutionField()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(
            self::NEW_CAMPAIGN_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(
            self::NEW_DEFINITION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => false,
                'getTestDefinitionTrackerId' => false,
                'getTestExecutionTrackerId'  => false,
                'getIssueTrackerId'          => false,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, self::NEW_DEFINITION_TRACKER_ID)
            ->once();

        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID)
            ->andThrow(TrackerExecutionNotValidException::class);

        $GLOBALS['Response']->method('addFeedback')->withConsecutive(
            ['warning', 'The tracker id 537 does not have step execution field'],
            [\Feedback::ERROR],
        );

        $this->admin_controller->update();
    }

    public function testUpdateNotDoneWhenATrackerHasNoArtifactLink()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn(
            self::NEW_CAMPAIGN_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(
            self::NEW_DEFINITION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => false,
                'getTestDefinitionTrackerId' => false,
                'getTestExecutionTrackerId'  => false,
                'getIssueTrackerId'          => false,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')
            ->andThrow(MissingArtifactLinkException::class)
            ->once();

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->once();

        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, self::NEW_DEFINITION_TRACKER_ID)
            ->once();

        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID)
            ->once();

        $GLOBALS['Response']->method('addFeedback')->withConsecutive(
            ['warning', 'The tracker id 535 does not have artifact links field'],
            [\Feedback::ERROR]
        );

        $this->admin_controller->update();
    }

    public function testUpdateReturnWrongIdFeedbackIfTrackerIdIsInvalid()
    {
        $this->request->shouldReceive('get')->with('campaign_tracker_id')->andReturn('oui');
        $this->request->shouldReceive('get')->with('test_definition_tracker_id')->andReturn(
            self::NEW_DEFINITION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('test_execution_tracker_id')->andReturn(
            self::NEW_EXECUTION_TRACKER_ID
        );
        $this->request->shouldReceive('get')->with('issue_tracker_id')->andReturn(self::NEW_ISSUE_TRACKER_ID);

        $this->config->shouldReceive(
            [
                'getCampaignTrackerId'       => self::ORIGINAL_CAMPAIGN_TRACKER_ID,
                'getTestDefinitionTrackerId' => self::ORIGINAL_DEFINITION_TRACKER_ID,
                'getTestExecutionTrackerId'  => self::ORIGINAL_EXECUTION_TRACKER_ID,
                'getIssueTrackerId'          => self::ORIGINAL_ISSUE_TRACKER_ID,
            ]
        );

        $this->field_usage_detector->shouldReceive('isStepDefinitionFieldUsed')->andReturn(false);

        $this->csrf_token->shouldReceive('check');
        $this->config->shouldReceive('setProjectConfiguration')->withArgs(
            [
                $this->project,
                self::ORIGINAL_CAMPAIGN_TRACKER_ID,
                self::NEW_DEFINITION_TRACKER_ID,
                self::NEW_EXECUTION_TRACKER_ID,
                self::NEW_ISSUE_TRACKER_ID,
            ]
        );

        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(1);
        $this->tracker_checker->shouldReceive('checkSubmittedDefinitionTrackerCanBeUsed')->once();
        $this->tracker_checker->shouldReceive('checkSubmittedExecutionTrackerCanBeUsed')->once();

        $this->admin_controller->update();
        $GLOBALS['Response']->method('addFeedback')->with(
            'warning',
            'The tracker id oui is not a valid id'
        );
    }
}

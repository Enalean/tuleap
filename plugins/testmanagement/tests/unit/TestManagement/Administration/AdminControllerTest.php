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

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\TestManagement\TrackerDefinitionNotValidException;
use Tuleap\TestManagement\TrackerExecutionNotValidException;
use Valid_UInt;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private Project $project;
    private AdminController $admin_controller;
    private Config&MockObject $config;
    private \CSRFSynchronizerToken&MockObject $csrf_token;
    private FieldUsageDetector&MockObject $field_usage_detector;
    private TrackerChecker&MockObject $tracker_checker;

    public const PROJECT_ID                     = 104;
    public const ORIGINAL_CAMPAIGN_TRACKER_ID   = 531;
    public const ORIGINAL_DEFINITION_TRACKER_ID = 532;
    public const ORIGINAL_EXECUTION_TRACKER_ID  = 533;
    public const ORIGINAL_ISSUE_TRACKER_ID      = 534;
    public const NEW_CAMPAIGN_TRACKER_ID        = 535;
    public const NEW_DEFINITION_TRACKER_ID      = 536;
    public const NEW_EXECUTION_TRACKER_ID       = 537;
    public const NEW_ISSUE_TRACKER_ID           = 538;

    public function setUp(): void
    {
        $this->config               = $this->createMock(Config::class);
        $this->field_usage_detector = $this->createMock(FieldUsageDetector::class);

        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $this->csrf_token = $this->createMock(\CSRFSynchronizerToken::class);

        $this->tracker_checker = $this->createMock(TrackerChecker::class);
    }

    private function getAdminController(\Codendi_Request $request): AdminController
    {
        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->method('processEvent');

        $project_history_dao = $this->createMock(\ProjectHistoryDao::class);
        $project_history_dao->method('addHistory');

        return new AdminController(
            $request,
            $this->config,
            $event_manager,
            $this->csrf_token,
            $this->field_usage_detector,
            $this->tracker_checker,
            new Valid_UInt(),
            $this->createMock(AdminTrackersRetriever::class),
            $project_history_dao
        );
    }

    public function testUpdateWithoutChange(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => self::NEW_CAMPAIGN_TRACKER_ID,
                'test_definition_tracker_id' => self::NEW_DEFINITION_TRACKER_ID,
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(self::ORIGINAL_CAMPAIGN_TRACKER_ID);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(self::ORIGINAL_DEFINITION_TRACKER_ID);
        $this->config->method('getTestExecutionTrackerId')->willReturn(self::ORIGINAL_EXECUTION_TRACKER_ID);
        $this->config->method('getIssueTrackerId')->willReturn(self::ORIGINAL_ISSUE_TRACKER_ID);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);

        $this->csrf_token->method('check');
        $this->config->method('setProjectConfiguration')->with(
            $this->project,
            self::NEW_CAMPAIGN_TRACKER_ID,
            self::NEW_DEFINITION_TRACKER_ID,
            self::NEW_EXECUTION_TRACKER_ID,
            self::NEW_ISSUE_TRACKER_ID,
        );

        $this->tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');

        $this->tracker_checker->expects(self::once())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $this->tracker_checker->expects(self::once())->method('checkSubmittedExecutionTrackerCanBeUsed');

        $this->getAdminController($request)->update();
    }

    public function testUpdateWhenDefinitionTrackerCantBeEdited(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => self::NEW_CAMPAIGN_TRACKER_ID,
                // No test_definition_tracker_id is provided
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(self::ORIGINAL_CAMPAIGN_TRACKER_ID);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(self::ORIGINAL_DEFINITION_TRACKER_ID);
        $this->config->method('getTestExecutionTrackerId')->willReturn(self::ORIGINAL_EXECUTION_TRACKER_ID);
        $this->config->method('getIssueTrackerId')->willReturn(self::ORIGINAL_ISSUE_TRACKER_ID);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(true);

        $this->csrf_token->method('check');
        $this->config->method('setProjectConfiguration')->with(
            $this->project,
            self::NEW_CAMPAIGN_TRACKER_ID,
            self::ORIGINAL_DEFINITION_TRACKER_ID,
            self::NEW_EXECUTION_TRACKER_ID,
            self::NEW_ISSUE_TRACKER_ID,
        );

        $this->tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');
        $this->tracker_checker->expects(self::never())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $this->tracker_checker->expects(self::once())->method('checkSubmittedExecutionTrackerCanBeUsed');

        $this->getAdminController($request)->update();
    }

    public function testUpdateNotDoneWhenATrackerIdIsNotSetAtInitialConfiguration(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => self::NEW_CAMPAIGN_TRACKER_ID,
                'test_definition_tracker_id' => 999,
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(false);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(false);
        $this->config->method('getTestExecutionTrackerId')->willReturn(false);
        $this->config->method('getIssueTrackerId')->willReturn(false);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);

        $this->csrf_token->method('check');
        $this->config->expects(self::never())->method('setProjectConfiguration');

        $this->tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');

        $this->tracker_checker->method('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, 999)
            ->willThrowException(new TrackerHasAtLeastOneFrozenFieldsPostActionException());

        $this->tracker_checker
            ->expects(self::once())
            ->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID);

        $this->getAdminController($request)->update();
    }

    public function testUpdateNotDoneWhenDefinitionTrackerHasNoStepDefinitionField(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => self::NEW_CAMPAIGN_TRACKER_ID,
                'test_definition_tracker_id' => 999,
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(false);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(false);
        $this->config->method('getTestExecutionTrackerId')->willReturn(false);
        $this->config->method('getIssueTrackerId')->willReturn(false);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);

        $this->csrf_token->method('check');
        $this->config->expects(self::never())->method('setProjectConfiguration');

        $this->tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');

        $this->tracker_checker->method('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, 999)
            ->willThrowException(new TrackerDefinitionNotValidException());

        $this->tracker_checker
            ->expects(self::once())
            ->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID);

        $GLOBALS['Response']->method('addFeedback')->willReturnCallback(
            function (string $level, string $message): void {
                match (true) {
                    $level === 'warning' && $message === 'The tracker id 999 does not have step definition field',
                        $level === \Feedback::ERROR => true
                };
            }
        );

        $this->getAdminController($request)->update();
    }

    public function testUpdateNotDoneWhenExecutionTrackerHasNoStepExecutionField(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => self::NEW_CAMPAIGN_TRACKER_ID,
                'test_definition_tracker_id' => self::NEW_DEFINITION_TRACKER_ID,
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(false);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(false);
        $this->config->method('getTestExecutionTrackerId')->willReturn(false);
        $this->config->method('getIssueTrackerId')->willReturn(false);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);

        $this->csrf_token->method('check');
        $this->config->expects(self::never())->method('setProjectConfiguration');

        $this->tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');

        $this->tracker_checker
            ->expects(self::once())
            ->method('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, self::NEW_DEFINITION_TRACKER_ID);

        $this->tracker_checker->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID)
            ->willThrowException(new TrackerExecutionNotValidException());

        $GLOBALS['Response']->method('addFeedback')->willReturnCallback(
            function (string $level, string $message): void {
                match (true) {
                    $level === 'warning' && $message === 'The tracker id 537 does not have step execution field',
                        $level === \Feedback::ERROR => true
                };
            }
        );

        $this->getAdminController($request)->update();
    }

    public function testUpdateNotDoneWhenATrackerHasNoArtifactLink(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => self::NEW_CAMPAIGN_TRACKER_ID,
                'test_definition_tracker_id' => self::NEW_DEFINITION_TRACKER_ID,
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(false);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(false);
        $this->config->method('getTestExecutionTrackerId')->willReturn(false);
        $this->config->method('getIssueTrackerId')->willReturn(false);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);

        $this->csrf_token->method('check');
        $this->config->expects(self::never())->method('setProjectConfiguration');

        $this->tracker_checker
            ->expects(self::exactly(2))
            ->method('checkSubmittedTrackerCanBeUsed')
            ->willReturnCallback(function (Project $project, int $submitted_id): void {
                if ($submitted_id === self::NEW_CAMPAIGN_TRACKER_ID) {
                    throw new MissingArtifactLinkException();
                }
            });

        $this->tracker_checker
            ->expects(self::once())
            ->method('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($this->project, self::NEW_DEFINITION_TRACKER_ID);

        $this->tracker_checker
            ->expects(self::once())
            ->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->with($this->project, self::NEW_EXECUTION_TRACKER_ID);

        $GLOBALS['Response']->method('addFeedback')->willReturnCallback(
            function (string $level, string $message): void {
                match (true) {
                    $level === 'warning' && $message === 'The tracker id 535 does not have artifact links field',
                        $level === \Feedback::ERROR => true
                };
            }
        );

        $this->getAdminController($request)->update();
    }

    public function testUpdateReturnWrongIdFeedbackIfTrackerIdIsInvalid(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withParams([
                'milestone_id'               => 0,
                'campaign_tracker_id'        => 'oui',
                'test_definition_tracker_id' => self::NEW_DEFINITION_TRACKER_ID,
                'test_execution_tracker_id'  => self::NEW_EXECUTION_TRACKER_ID,
                'issue_tracker_id'           => self::NEW_ISSUE_TRACKER_ID,
            ])->build();

        $this->config->method('getCampaignTrackerId')->willReturn(self::ORIGINAL_CAMPAIGN_TRACKER_ID);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(self::ORIGINAL_DEFINITION_TRACKER_ID);
        $this->config->method('getTestExecutionTrackerId')->willReturn(self::ORIGINAL_EXECUTION_TRACKER_ID);
        $this->config->method('getIssueTrackerId')->willReturn(self::ORIGINAL_ISSUE_TRACKER_ID);

        $this->field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);

        $this->csrf_token->method('check');
        $this->config->method('setProjectConfiguration')->with(
            $this->project,
            self::ORIGINAL_CAMPAIGN_TRACKER_ID,
            self::NEW_DEFINITION_TRACKER_ID,
            self::NEW_EXECUTION_TRACKER_ID,
            self::NEW_ISSUE_TRACKER_ID,
        );

        $this->tracker_checker->expects(self::once())->method('checkSubmittedTrackerCanBeUsed');
        $this->tracker_checker->expects(self::once())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $this->tracker_checker->expects(self::once())->method('checkSubmittedExecutionTrackerCanBeUsed');

        $this->getAdminController($request)->update();
        $GLOBALS['Response']->method('addFeedback')->with(
            'warning',
            'The tracker id oui is not a valid id'
        );
    }
}

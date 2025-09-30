<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Action_Rules_EditRulesTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    private const PARAMETER_ADD_RULE     = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_ADD_RULE;
    private const PARAMETER_UPDATE_RULES = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_UPDATE_RULES;
    private const PARAMETER_REMOVE_RULES = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_REMOVE_RULES;

    private const PARAMETER_SOURCE_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_SOURCE_FIELD;
    private const PARAMETER_TARGET_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_TARGET_FIELD;
    private const PARAMETER_COMPARATOR   = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_COMPARATOR;

    private int $tracker_id = 42;
    private Tracker_Rule_Date_Factory&MockObject $date_factory;
    private Tracker&MockObject $tracker;

    private DateField $planned_start_date;
    private DateField $actual_start_date;
    private DateField $planned_end_date;
    private DateField $actual_end_date;
    private int $source_field_id        = 44;
    private int $target_field_id        = 22;
    private int $actual_source_field_id = 66;
    private int $actual_target_field_id = 55;
    private int $rule_42_id             = 42;
    private Tracker_Rule_Date&MockObject $rule_42;
    private int $rule_66_id = 66;
    private Tracker_Rule_Date&MockObject $rule_66;
    private Tracker_Workflow_Action_Rules_EditRules $action;
    private Tracker_Rule_Date $rule_1;
    private Tracker_Rule_Date $rule_2;
    private Tracker_IDisplayTrackerLayout&MockObject $layout;
    private PFUser $user;

    private ProjectHistoryDao&\PHPUnit\Framework\MockObject\MockObject $project_history_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->date_factory = $this->createMock(\Tracker_Rule_Date_Factory::class);

        $this->tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->tracker->method('getId')->willReturn($this->tracker_id);
        $this->tracker->method('displayAdminItemHeader');
        $this->tracker->method('displayFooter');
        $this->tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->tracker->method('getId')->willReturn($this->tracker_id);

        $token = $this->createMock(\CSRFSynchronizerToken::class);
        $token->method('fetchHTMLInput');
        $token->method('check');

        $this->planned_start_date = DateFieldBuilder::aDateField($this->source_field_id)->withLabel('Planned Start Date')->build();
        $this->actual_start_date  = DateFieldBuilder::aDateField($this->target_field_id)->withLabel('Actual Start Date')->build();
        $this->planned_end_date   = DateFieldBuilder::aDateField($this->actual_source_field_id)->withLabel('Planned End Date')->build();
        $this->actual_end_date    = DateFieldBuilder::aDateField($this->actual_target_field_id)->withLabel('Actual End Date')->build();

        $this->date_factory->method('getUsedDateFieldById')
            ->willReturnCallback(fn (Tracker $tracker, int $field_id) => match ($field_id) {
                $this->planned_start_date->getId() => $this->planned_start_date,
                $this->actual_start_date->getId() => $this->actual_start_date,
                $this->planned_end_date->getId() => $this->planned_end_date,
                $this->actual_end_date->getId() => $this->actual_end_date,
                default => null,
            });

        $this->rule_1 = $this->setUpRule(123, $this->planned_start_date, Tracker_Rule_Date::COMPARATOR_EQUALS, $this->planned_end_date);
        $this->rule_2 = $this->setUpRule(456, $this->actual_start_date, Tracker_Rule_Date::COMPARATOR_LESS_THAN, $this->actual_end_date);
        $this->layout = $this->createMock(\Tracker_IDisplayTrackerLayout::class);
        $this->user   = UserTestBuilder::buildWithDefaults();
        $this->date_factory->method('searchByTrackerId')->with($this->tracker_id)->willReturn([$this->rule_1, $this->rule_2]);
        $this->date_factory->method('getUsedDateFields')->willReturn([
            $this->planned_start_date,
            $this->actual_start_date,
            $this->planned_end_date,
            $this->actual_end_date,
        ]);

        $this->rule_42 = $this->createMock(\Tracker_Rule_Date::class);
        $this->rule_42->method('getId')->willReturn($this->rule_42_id);
        $this->rule_42->method('getSourceField')->willReturn($this->planned_start_date);
        $this->rule_42->method('getTargetField')->willReturn($this->actual_start_date);
        $this->rule_42->method('getComparator')->willReturn('<');

        $this->rule_66 = $this->createMock(\Tracker_Rule_Date::class);
        $this->rule_66->method('getId')->willReturn($this->rule_66_id);
        $this->rule_42->method('getSourceField')->willReturn($this->actual_start_date);
        $this->rule_42->method('getTargetField')->willReturn($this->planned_start_date);
        $this->rule_42->method('getComparator')->willReturn('>');

        $this->date_factory->method('getRule')->willReturnCallback(
            fn (Tracker $tracker, int $rule_id) => match ($rule_id) {
                $this->rule_1->getId() => $this->rule_1,
                $this->rule_2->getId() => $this->rule_2,
                $this->rule_42->getId() => $this->rule_42,
                $this->rule_66->getId() => $this->rule_66,
                default => null,
            }
        );

        $this->project_history_dao = $this->createMock(ProjectHistoryDao::class);
        $this->action              = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, $this->date_factory, $token, $this->project_history_dao);
    }

    private function setUpRule($id, TrackerField $source_field, $comparator, TrackerField $target_field): Tracker_Rule_Date
    {
        $rule = new Tracker_Rule_Date();
        $rule->setId($id);
        $rule->setSourceField($source_field);
        $rule->setComparator($comparator);
        $rule->setTargetField($target_field);
        return $rule;
    }

    protected function processRequestAndExpectRedirection(Codendi_Request $request): void
    {
        $GLOBALS['Response']->expects($this->once())->method('redirect');
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertEquals('', $content);
    }

    protected function processRequestAndExpectFormOutput(Codendi_Request $request): void
    {
        $GLOBALS['Response']->expects($this->never())->method('redirect');
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertNotEquals('', $content);
    }

    public function testItDoesNotDisplayErrorsIfNoActions(): void
    {
        $request = new Codendi_Request([], $this->createMock(ProjectManager::class));
        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with(Feedback::ERROR);
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDeletesARule(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['123']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->once())->method('deleteById')->with($this->tracker_id, 123)->willReturn(true);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDeletesMultipleRules(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['123', '456']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->exactly(2))
            ->method('deleteById')
            ->willReturnCallback(static fn (int $tracker_id, int $rule_id) => $rule_id === 123 || $rule_id === 456);
        $this->project_history_dao->expects($this->exactly(2))->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfRequestDoesNotContainAnArray(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => '123'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->never())->method('deleteById');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotFailIfRequestContainsIrrevelantId(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['invalid_id']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->once())->method('deleteById')->with($this->tracker_id, 0)->willReturn(true);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfRequestDoesNotContainRemoveParameter(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_SOURCE_FIELD => '21', self::PARAMETER_TARGET_FIELD => '14'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->never())->method('deleteById');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItProvidesFeedbackWhenDeletingARule(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['123']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->method('deleteById')->willReturn(true);
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotPrintMultipleTimesTheFeedbackWhenRemovingMoreThanOneRule(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['123', '456']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->method('deleteById')->willReturn(true);
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');
        $this->project_history_dao->expects($this->exactly(2))->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotPrintSuccessfullFeebackIfTheDeleteFailed(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['123']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->method('deleteById')->willReturn(false);
        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with('info');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotStopOnTheFirstFailedDelete(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => ['123', '456']],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->exactly(2))
            ->method('deleteById')
            ->willReturnCallback(static fn (int $tracker_id, int $rule_id) => match ($rule_id) {
                123 => false,
                456 => true,
            });
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    private function processIndexRequest(): string
    {
        $request = new Codendi_Request(
            [],
            $this->createMock(ProjectManager::class)
        );

        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        return ob_get_clean();
    }

    public function testItSelectTheSourceField(): void
    {
        $output = $this->processIndexRequest();
        $this->assertMatchesRegularExpression('/SELECTED>Planned Start Date</s', $output);
        $this->assertMatchesRegularExpression('/SELECTED>Actual Start Date</s', $output);
    }

    public function testItSelectTheTargetField(): void
    {
        $output = $this->processIndexRequest();
        $this->assertMatchesRegularExpression('/SELECTED>Planned End Date</s', $output);
        $this->assertMatchesRegularExpression('/SELECTED>Actual End Date</s', $output);
    }

    public function testItSelectTheComparator(): void
    {
        $output = $this->processIndexRequest();
        $this->assertMatchesRegularExpression('/SELECTED>=</s', $output);
        $this->assertMatchesRegularExpression('/SELECTED>&lt;</s', $output);
    }

    public function testItAddsARuleAndCheckFeedbackIsDisplayed(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->once())->method('create')->with($this->source_field_id, $this->target_field_id, $this->tracker_id, '>')->willReturn($this->rule_42);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheComparator(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with('info');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheSourceField(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheSourceFieldIsNotAnInt(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '%invalid_id%',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheSourceFieldIsNotAnGreaterThanZero(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '-1',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheSourceFieldIsNotChoosen(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '0',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheTargetField(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetFieldIsNotAnInt(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '%invalid_id%',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetFieldIsNotAnGreaterThanZero(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '-1',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetFieldIsNotChoosen(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '0',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainAValidComparator(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '%invalid_comparator%',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetAndSourceFieldsAreTheSame(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '44',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->never())->method('create');
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItProvidesFeedbackIfRuleSuccessfullyCreated(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');
        $this->date_factory->method('create')->willReturn($this->rule_42);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotAddDateRuleIfTheSourceFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '666',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotAddDateRuleIfTheTargetFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '666',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItUpdatesARule(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->once())->method('setSourceField')->with($this->planned_start_date);
        $this->rule_42->method('getSourceFieldId')->willReturn($this->planned_start_date->getId());
        $this->rule_42->expects($this->once())->method('setTargetField')->with($this->actual_start_date);
        $this->rule_42->method('getTargetFieldId')->willReturn($this->actual_start_date->getId());
        $this->rule_42->expects($this->once())->method('setComparator')->with('>');
        $this->date_factory->expects($this->once())->method('save')->with($this->rule_42);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItUpdatesMoreThanOneRule(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                    "$this->rule_66_id" => [
                        self::PARAMETER_SOURCE_FIELD => '22',
                        self::PARAMETER_TARGET_FIELD => '44',
                        self::PARAMETER_COMPARATOR   => '<',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->once())->method('setSourceField')->with($this->planned_start_date);
        $this->rule_42->method('setTargetField');
        $this->rule_42->method('getSourceFieldId')->willReturn($this->planned_start_date->getId());
        $this->rule_42->method('setComparator');
        $this->rule_42->method('getComparator');
        $this->rule_42->method('getSourceField');
        $this->rule_42->method('getTargetFieldId')->willReturn($this->planned_start_date->getId());

        $this->rule_66->expects($this->once())->method('setSourceField')->with($this->actual_start_date);
        $this->rule_66->method('getSourceFieldId')->willReturn($this->planned_start_date->getId());
        $this->rule_66->method('setTargetField');
        $this->rule_66->method('setComparator');
        $this->rule_66->method('getComparator');
        $this->rule_66->method('getSourceField');
        $this->rule_66->method('getTargetFieldId')->willReturn($this->planned_start_date->getId());

        $this->date_factory->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(fn (Tracker_Rule_Date $rule) => $rule === $this->rule_42 || $rule === $this->rule_66);
        $this->project_history_dao->expects($this->exactly(2))->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheNewSourceFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '666',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->never())->method('setSourceField');
        $this->date_factory->expects($this->never())->method('save')->with($this->rule_42);
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheNewTargetFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '666',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->never())->method('setTargetField');
        $this->date_factory->expects($this->never())->method('save')->with($this->rule_42);
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheNewComparatorIsNotValid(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR   => '%invalid_comparator%',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->never())->method('setComparator');
        $this->date_factory->expects($this->never())->method('save')->with($this->rule_42);
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfTheTargetFieldIsMissingFromTheRequest(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_COMPARATOR   => '<',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->never())->method('setComparator');
        $this->date_factory->expects($this->never())->method('save')->with($this->rule_42);
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfTheSourceFieldIsMissingFromTheRequest(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->rule_42->expects($this->never())->method('setComparator');
        $this->date_factory->expects($this->never())->method('save')->with($this->rule_42);
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfTheRuleDoesNotBelongToTracker(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    '%invalid_rule_id%' => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('save');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateIfTheRuleDoesNotChange(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => $this->rule_42->getSourceField()->getId(),
                        self::PARAMETER_TARGET_FIELD => $this->rule_42->getTargetField()->getId(),
                        self::PARAMETER_COMPARATOR => $this->rule_42->getComparator(),
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('save');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheTargetAndSourceFieldsAreTheSame(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '22',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('save');
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItProvidesFeedbackIfRulesSuccessfullyUpdated(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => [
                    "$this->rule_42_id" => [
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR   => '>',
                    ],
                    "$this->rule_66_id" => [
                        self::PARAMETER_SOURCE_FIELD => '22',
                        self::PARAMETER_TARGET_FIELD => '44',
                        self::PARAMETER_COMPARATOR   => '<',
                    ],
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->method('save')->willReturn(true);

        $this->rule_42->method('setSourceField');
        $this->rule_42->method('setTargetField');
        $this->rule_42->method('setComparator');
        $this->rule_42->method('getComparator');
        $this->rule_42->method('getSourceField');
        $this->rule_42->method('getSourceFieldId')->willReturn($this->planned_start_date->getId());
        $this->rule_42->method('getTargetFieldId')->willReturn($this->planned_start_date->getId());

        $this->rule_66->method('getSourceField');
        $this->rule_66->method('getSourceFieldId')->willReturn($this->planned_start_date->getId());
        $this->rule_66->method('getTargetFieldId')->willReturn($this->planned_start_date->getId());
        $this->rule_66->method('setSourceField');
        $this->rule_66->method('setTargetField');
        $this->rule_66->method('setComparator');
        $this->rule_66->method('getComparator');

        $this->project_history_dao->expects($this->exactly(2))->method('addHistory');
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');
        $this->processRequestAndExpectRedirection($request);
    }
}

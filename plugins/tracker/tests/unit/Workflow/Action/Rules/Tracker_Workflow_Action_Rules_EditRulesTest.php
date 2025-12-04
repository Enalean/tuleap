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
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
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
    use \Tuleap\TemporaryTestDirectory;

    private const string PARAMETER_ADD_RULE    = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_ADD_RULE;
    private const string PARAMETER_REMOVE_RULE = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_REMOVE_RULE;

    private const string PARAMETER_SOURCE_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_SOURCE_FIELD;
    private const string PARAMETER_TARGET_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_TARGET_FIELD;
    private const string PARAMETER_COMPARATOR   = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_COMPARATOR;

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
        $this->tracker->method('displayAdminItemHeaderBurningParrot');
        $this->tracker->method('displayFooter');
        $this->tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->tracker->method('getId')->willReturn($this->tracker_id);

        $token = \Tuleap\Test\Stubs\CSRFSynchronizerTokenStub::buildSelf();

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
        $this->action              = new Tracker_Workflow_Action_Rules_EditRules(
            $this->tracker,
            $this->date_factory,
            $token,
            $this->project_history_dao,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
        );
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

    protected function processRequestAndExpectRedirection(\Tuleap\HTTPRequest $request): void
    {
        $GLOBALS['Response']->expects($this->once())->method('redirect');
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertEquals('', $content);
    }

    protected function processRequestAndExpectFormOutput(\Tuleap\HTTPRequest $request): void
    {
        $GLOBALS['Response']->expects($this->never())->method('redirect');
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertNotEquals('', $content);
    }

    public function testItDoesNotDisplayErrorsIfNoActions(): void
    {
        $request = new \Tuleap\HTTPRequest([], $this->createMock(ProjectManager::class));
        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with(Feedback::ERROR);
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDeletesARule(): void
    {
        $request = new \Tuleap\HTTPRequest(
            [self::PARAMETER_REMOVE_RULE => '123'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->once())->method('deleteById')->with($this->tracker_id, 123)->willReturn(true);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDeletesMultipleRules(): void
    {
        $request = new \Tuleap\HTTPRequest(
            [self::PARAMETER_REMOVE_RULE => '123'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->once())
            ->method('deleteById')
            ->with($this->tracker_id, 123)
            ->willReturn(true);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfRequestContainsIrrevelantId(): void
    {
        $request = new \Tuleap\HTTPRequest(
            [self::PARAMETER_REMOVE_RULE => 'invalid_id'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->never())->method('deleteById');
        $this->project_history_dao->expects($this->never())->method('addHistory');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotFailIfRequestDoesNotContainRemoveParameter(): void
    {
        $request = new \Tuleap\HTTPRequest(
            [self::PARAMETER_SOURCE_FIELD => '21', self::PARAMETER_TARGET_FIELD => '14'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->expects($this->never())->method('deleteById');
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItProvidesFeedbackWhenDeletingARule(): void
    {
        $request = new \Tuleap\HTTPRequest(
            [self::PARAMETER_REMOVE_RULE => '123'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->method('deleteById')->willReturn(true);
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::SUCCESS);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotPrintSuccessfullFeebackIfTheDeleteFailed(): void
    {
        $request = new \Tuleap\HTTPRequest(
            [self::PARAMETER_REMOVE_RULE => '123'],
            $this->createMock(ProjectManager::class)
        );
        $this->date_factory->method('deleteById')->willReturn(false);
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::ERROR);
        $this->processRequestAndExpectRedirection($request);
    }

    private function processIndexRequest(): string
    {
        $request = new \Tuleap\HTTPRequest(
            [],
            $this->createMock(ProjectManager::class)
        );

        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        return ob_get_clean();
    }

    public function testItDisplaysTheExistingRules(): void
    {
        $output = $this->processIndexRequest();
        $this->assertMatchesRegularExpression('#<span>Planned Start Date</span>\s*<span>=</span>\s*<span>Planned End Date</span>#', $output);
        $this->assertMatchesRegularExpression('#<span>Actual Start Date</span>\s*<span>&lt;</span>\s*<span>Actual End Date</span>#', $output);
    }

    public function testItAddsARuleAndCheckFeedbackIsDisplayed(): void
    {
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );

        $this->date_factory->expects($this->never())->method('create');
        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with(Feedback::SUCCESS);
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheSourceField(): void
    {
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
            [
                self::PARAMETER_ADD_RULE => [
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>',
                ],
            ],
            $this->createMock(ProjectManager::class)
        );
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::SUCCESS);
        $this->date_factory->method('create')->willReturn($this->rule_42);
        $this->project_history_dao->expects($this->once())->method('addHistory');
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotAddDateRuleIfTheSourceFieldIsNotADateOne(): void
    {
        $request = new \Tuleap\HTTPRequest(
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
        $request = new \Tuleap\HTTPRequest(
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
}

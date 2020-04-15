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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Workflow_Action_Rules_EditRulesTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    private const PARAMETER_ADD_RULE     = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_ADD_RULE;
    private const PARAMETER_UPDATE_RULES = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_UPDATE_RULES;
    private const PARAMETER_REMOVE_RULES = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_REMOVE_RULES;

    private const PARAMETER_SOURCE_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_SOURCE_FIELD;
    private const PARAMETER_TARGET_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_TARGET_FIELD;
    private const PARAMETER_COMPARATOR   = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_COMPARATOR;

    private $tracker_id       = 42;
    private $date_factory;
    private $tracker;
    private $token;

    private $planned_start_date;
    private $actual_start_date;
    private $planned_end_date;
    private $actual_end_date;
    private $source_field_id        = 44;
    private $target_field_id        = 22;
    private $actual_source_field_id = 66;
    private $actual_target_field_id = 55;
    private $rule_42_id = 42;
    private $rule_42;
    private $rule_66_id = 66;
    private $rule_66;
    private $action;
    /**
     * @var Tracker_Rule_Date
     */
    private $rule_1;
    /**
     * @var Tracker_Rule_Date
     */
    private $rule_2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_IDisplayTrackerLayout
     */
    private $layout;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $GLOBALS['Language']->shouldReceive('getText')->andReturn('');
        $this->date_factory       = \Mockery::spy(\Tracker_Rule_Date_Factory::class);
        $this->tracker            = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->tracker_id)->getMock();
        $this->token              = \Mockery::spy(\CSRFSynchronizerToken::class);
        $this->planned_start_date = $this->setUpField($this->source_field_id, 'Planned Start Date');
        $this->actual_start_date  = $this->setUpField($this->target_field_id, 'Actual Start Date');
        $this->planned_end_date   = $this->setUpField($this->actual_source_field_id, 'Planned End Date');
        $this->actual_end_date    = $this->setUpField($this->actual_target_field_id, 'Actual End Date');
        $this->rule_1       = $this->setUpRule(123, $this->planned_start_date, Tracker_Rule_Date::COMPARATOR_EQUALS, $this->planned_end_date);
        $this->rule_2       = $this->setUpRule(456, $this->actual_start_date, Tracker_Rule_Date::COMPARATOR_LESS_THAN, $this->actual_end_date);
        $this->layout       = \Mockery::spy(\Tracker_IDisplayTrackerLayout::class);
        $this->user         = \Mockery::spy(\PFUser::class);
        $this->date_factory->shouldReceive('getRule')->with($this->tracker, 123)->andReturns($this->rule_1);
        $this->date_factory->shouldReceive('getRule')->with($this->tracker, 456)->andReturns($this->rule_2);
        $this->date_factory->shouldReceive('searchByTrackerId')->with($this->tracker_id)->andReturns(array($this->rule_1, $this->rule_2));
        $this->date_factory->shouldReceive('getUsedDateFields')->andReturns(array(
            $this->planned_start_date,
            $this->actual_start_date,
            $this->planned_end_date,
            $this->actual_end_date
        ));

        $this->rule_42 = \Mockery::spy(\Tracker_Rule_Date::class);
        $this->rule_42->shouldReceive('getId')->andReturns($this->rule_42_id);
        $this->rule_42->shouldReceive('getSourceField')->andReturns($this->planned_start_date);
        $this->rule_42->shouldReceive('getTargetField')->andReturns($this->actual_start_date);
        $this->rule_42->shouldReceive('getComparator')->andReturns('<');
        $this->date_factory->shouldReceive('getRule')->with($this->tracker, $this->rule_42_id)->andReturns($this->rule_42);

        $this->rule_66 = \Mockery::spy(\Tracker_Rule_Date::class);
        $this->rule_66->shouldReceive('getId')->andReturns($this->rule_66_id);
        $this->rule_42->shouldReceive('getSourceField')->andReturns($this->actual_start_date);
        $this->rule_42->shouldReceive('getTargetField')->andReturns($this->planned_start_date);
        $this->rule_42->shouldReceive('getComparator')->andReturns('>');
        $this->date_factory->shouldReceive('getRule')->with($this->tracker, $this->rule_66_id)->andReturns($this->rule_66);

        $this->action = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, $this->date_factory, $this->token);
    }

    private function setUpField($id, string $label)
    {
         $field = \Mockery::spy(\Tracker_FormElement_Field_Date::class)->shouldReceive('getLabel')->andReturns($label)->getMock();
         $field->shouldReceive('getId')->andReturns($id);
         $this->date_factory->shouldReceive('getUsedDateFieldById')->with($this->tracker, $id)->andReturns($field);
         return $field;
    }

    private function setUpRule($id, Tracker_FormElement_Field $source_field, $comparator, Tracker_FormElement_Field $target_field): Tracker_Rule_Date
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
        $GLOBALS['Response']->shouldReceive('redirect')->once();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertEquals('', $content);
    }

    protected function processRequestAndExpectFormOutput(Codendi_Request $request): void
    {
        $GLOBALS['Response']->shouldReceive('redirect')->never();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertNotEquals('', $content);
    }

    public function testItDoesNotDisplayErrorsIfNoActions(): void
    {
        $request = new Codendi_Request([], Mockery::mock(ProjectManager::class));
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', \Mockery::any())->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDeletesARule(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('123')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->with($this->tracker_id, 123)->andReturn(true)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDeletesMultipleRules(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('123','456')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->with($this->tracker_id, 123)->andReturn(true)->once();
        $this->date_factory->shouldReceive('deleteById')->with($this->tracker_id, 456)->andReturn(true)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfRequestDoesNotContainAnArray(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => '123'],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotFailIfRequestContainsIrrevelantId(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('invalid_id')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->with($this->tracker_id, 0)->andReturn(true)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfRequestDoesNotContainRemoveParameter(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_SOURCE_FIELD => '21', self::PARAMETER_TARGET_FIELD => '14'],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItProvidesFeedbackWhenDeletingARule(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('123')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->andReturns(true);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotPrintMultipleTimesTheFeedbackWhenRemovingMoreThanOneRule(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('123', '456')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->andReturns(true);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotPrintSuccessfullFeebackIfTheDeleteFailed(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('123')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->andReturns(false);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotStopOnTheFirstFailedDelete(): void
    {
        $request = new Codendi_Request(
            [self::PARAMETER_REMOVE_RULES => array('123', '456')],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('deleteById')->with($this->tracker_id, 123)->ordered()->andReturns(false);
        $this->date_factory->shouldReceive('deleteById')->with($this->tracker_id, 456)->ordered()->andReturns(true);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->once();
        $this->processRequestAndExpectRedirection($request);
    }

    private function processIndexRequest(): string
    {
        $request = new Codendi_Request(
            [],
            Mockery::mock(ProjectManager::class)
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

    public function testItAddsARule(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->with($this->source_field_id, $this->target_field_id, $this->tracker_id, '>')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheComparator(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $GLOBALS['Response']->shouldReceive('addFeedback')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheSourceField(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheSourceFieldIsNotAnInt(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '%invalid_id%',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheSourceFieldIsNotAnGreaterThanZero(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '-1',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheSourceFieldIsNotChoosen(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '0',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainTheTargetField(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetFieldIsNotAnInt(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '%invalid_id%',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetFieldIsNotAnGreaterThanZero(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '-1',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetFieldIsNotChoosen(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '0',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheRequestDoesNotContainAValidComparator(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '%invalid_comparator%'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotCreateTheRuleIfTheTargetAndSourceFieldsAreTheSame(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '44',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );
        $this->date_factory->shouldReceive('create')->never();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', \Mockery::any())->once();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItProvidesFeedbackIfRuleSuccessfullyCreated(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotAddDateRuleIfTheSourceFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '666',
                    self::PARAMETER_TARGET_FIELD => '22',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItDoesNotAddDateRuleIfTheTargetFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_ADD_RULE => array(
                    self::PARAMETER_SOURCE_FIELD => '44',
                    self::PARAMETER_TARGET_FIELD => '666',
                    self::PARAMETER_COMPARATOR   => '>'
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('create')->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function testItUpdatesARule(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setSourceField')->with($this->planned_start_date)->once();
        $this->rule_42->shouldReceive('setTargetField')->with($this->actual_start_date)->once();
        $this->rule_42->shouldReceive('setComparator')->with('>')->once();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItUpdatesMoreThanOneRule(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>'
                    ),
                    "$this->rule_66_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '22',
                        self::PARAMETER_TARGET_FIELD => '44',
                        self::PARAMETER_COMPARATOR   => '<'
                    ),
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setSourceField')->with($this->planned_start_date)->once();
        $this->rule_66->shouldReceive('setSourceField')->with($this->actual_start_date)->once();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->ordered();
        $this->date_factory->shouldReceive('save')->with($this->rule_66)->ordered();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheNewSourceFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '666',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setSourceField')->never();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheNewTargetFieldIsNotADateOne(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '666',
                        self::PARAMETER_COMPARATOR => '>'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setTargetField')->never();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheNewComparatorIsNotValid(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR   => '%invalid_comparator%'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setComparator')->never();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfTheTargetFieldIsMissingFromTheRequest(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_COMPARATOR   => '<'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setComparator')->never();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfTheSourceFieldIsMissingFromTheRequest(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->rule_42->shouldReceive('setComparator')->never();
        $this->date_factory->shouldReceive('save')->with($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotFailIfTheRuleDoesNotBelongToTracker(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "%invalid_rule_id%" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('save')->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateIfTheRuleDoesNotChange(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => $this->rule_42->getSourceField()->getId(),
                        self::PARAMETER_TARGET_FIELD => $this->rule_42->getTargetField()->getId(),
                        self::PARAMETER_COMPARATOR => $this->rule_42->getComparator()
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('save')->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItDoesNotUpdateTheRuleIfTheTargetAndSourceFieldsAreTheSame(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '22',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR => '>'
                    )
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('save')->never();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', \Mockery::any())->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function testItProvidesFeedbackIfRulesSuccessfullyUpdated(): void
    {
        $request = new Codendi_Request(
            [
                self::PARAMETER_UPDATE_RULES => array(
                    "$this->rule_42_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '44',
                        self::PARAMETER_TARGET_FIELD => '22',
                        self::PARAMETER_COMPARATOR   => '>'
                    ),
                    "$this->rule_66_id" => array(
                        self::PARAMETER_SOURCE_FIELD => '22',
                        self::PARAMETER_TARGET_FIELD => '44',
                        self::PARAMETER_COMPARATOR   => '<'
                    ),
                )
            ],
            Mockery::mock(ProjectManager::class)
        );

        $this->date_factory->shouldReceive('save')->andReturns(true);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->once();
        $this->processRequestAndExpectRedirection($request);
    }
}

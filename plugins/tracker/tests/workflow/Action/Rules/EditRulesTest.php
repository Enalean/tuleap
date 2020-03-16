<?php
/**
 * Copyright (c) Enalean, 2012 - 2019. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_Workflow_Action_Rules_EditRules_processTest extends TuleapTestCase
{

    public const PARAMETER_ADD_RULE     = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_ADD_RULE;
    public const PARAMETER_UPDATE_RULES = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_UPDATE_RULES;
    public const PARAMETER_REMOVE_RULES = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_REMOVE_RULES;

    public const PARAMETER_SOURCE_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_SOURCE_FIELD;
    public const PARAMETER_TARGET_FIELD = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_TARGET_FIELD;
    public const PARAMETER_COMPARATOR   = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_COMPARATOR;

    protected $tracker_id       = 42;
    protected $date_factory;
    protected $tracker;
    protected $token;

    protected $planned_start_date;
    protected $actual_start_date;
    protected $planned_end_date;
    protected $actual_end_date;
    protected $source_field_id        = 44;
    protected $target_field_id        = 22;
    protected $actual_source_field_id = 66;
    protected $actual_target_field_id = 55;
    private $backup_globals;

    public function setUp()
    {
        parent::setUp();
        $this->backup_globals     = array_merge([], $GLOBALS);
        $GLOBALS['Language']      = Mockery::mock(BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getText')->andReturn('');
        $this->date_factory       = mock('Tracker_Rule_Date_Factory');
        $this->tracker            = stub('Tracker')->getId()->returns($this->tracker_id);
        $this->token              = mock('CSRFSynchronizerToken');
        $this->planned_start_date = $this->setUpField($this->source_field_id, 'Planned Start Date');
        $this->actual_start_date  = $this->setUpField($this->target_field_id, 'Actual Start Date');
        $this->planned_end_date   = $this->setUpField($this->actual_source_field_id, 'Planned End Date');
        $this->actual_end_date    = $this->setUpField($this->actual_target_field_id, 'Actual End Date');
        $this->rule_1       = $this->setUpRule(123, $this->planned_start_date, Tracker_Rule_Date::COMPARATOR_EQUALS, $this->planned_end_date);
        $this->rule_2       = $this->setUpRule(456, $this->actual_start_date, Tracker_Rule_Date::COMPARATOR_LESS_THAN, $this->actual_end_date);
        $this->layout       = mock('Tracker_IDisplayTrackerLayout');
        $this->user         = mock('PFUser');
        stub($this->date_factory)->getRule($this->tracker, 123)->returns($this->rule_1);
        stub($this->date_factory)->getRule($this->tracker, 456)->returns($this->rule_2);
        stub($this->date_factory)->searchByTrackerId($this->tracker_id)->returns(array($this->rule_1, $this->rule_2));
        stub($this->date_factory)->getUsedDateFields()->returns(
            array(
                $this->planned_start_date,
                $this->actual_start_date,
                $this->planned_end_date,
                $this->actual_end_date
            )
        );
        $this->action = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, $this->date_factory, $this->token);
    }

    public function tearDown()
    {
        $GLOBALS = $this->backup_globals;
        parent::tearDown();
    }

    private function setUpField($id, $label)
    {
         $field = stub('Tracker_FormElement_Field_Date')->getLabel()->returns($label);
         stub($field)->getId()->returns($id);
         stub($this->date_factory)->getUsedDateFieldById($this->tracker, $id)->returns($field);
         return $field;
    }

    private function setUpRule($id, $source_field, $comparator, $target_field)
    {
        $rule = new Tracker_Rule_Date();
        $rule->setId($id);
        $rule->setSourceField($source_field);
        $rule->setComparator($comparator);
        $rule->setTargetField($target_field);
        return $rule;
    }

    protected function processRequestAndExpectRedirection($request)
    {
        expect($GLOBALS['Response'])->redirect()->once();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertEqual('', $content);
    }

    protected function processRequestAndExpectFormOutput($request)
    {
        expect($GLOBALS['Response'])->redirect()->never();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertNotEqual('', $content);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_noActionsTest extends Tracker_Workflow_Action_Rules_EditRules_processTest
{

    public function itDoesNotDisplayErrorsIfNoActions()
    {
        $request = aRequest()->build();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->never();
        $this->processRequestAndExpectFormOutput($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_deleteTest extends Tracker_Workflow_Action_Rules_EditRules_processTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->date_factory)->deleteById()->returns(true);
    }

    public function itDeletesARule()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('123'))->build();
        expect($this->date_factory)->deleteById($this->tracker_id, 123)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDeletesMultipleRules()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('123','456'))->build();
        expect($this->date_factory)->deleteById($this->tracker_id, 123)->at(0);
        expect($this->date_factory)->deleteById($this->tracker_id, 456)->at(1);
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfRequestDoesNotContainAnArray()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, '123')->build();
        expect($this->date_factory)->deleteById()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotFailIfRequestContainsIrrevelantId()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('invalid_id'))->build();
        expect($this->date_factory)->deleteById($this->tracker_id, 0)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfRequestDoesNotContainRemoveParameter()
    {
        $request = aRequest()->withParams(array(
            self::PARAMETER_SOURCE_FIELD => '21',
            self::PARAMETER_TARGET_FIELD => '14'
        ))->build();
        expect($this->date_factory)->deleteById()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itProvidesFeedbackWhenDeletingARule()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('123'))->build();
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotPrintMultipleTimesTheFeedbackWhenRemovingMoreThanOneRule()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('123', '456'))->build();
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_failedDeleteTest extends Tracker_Workflow_Action_Rules_EditRules_processTest
{

    public function itDoesNotPrintSuccessfullFeebackIfTheDeleteFailed()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('123'))->build();
        stub($this->date_factory)->deleteById()->returns(false);
        expect($GLOBALS['Response'])->addFeedback('info', '*')->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotStopOnTheFirstFailedDelete()
    {
        $request = aRequest()->with(self::PARAMETER_REMOVE_RULES, array('123', '456'))->build();
        stub($this->date_factory)->deleteById($this->tracker_id, 123)->at(0)->returns(false);
        stub($this->date_factory)->deleteById($this->tracker_id, 456)->at(1)->returns(true);
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_getRulesTest extends Tracker_Workflow_Action_Rules_EditRules_processTest
{

    public function setUp()
    {
        parent::setUp();
        $request = aRequest()->build();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $this->output = ob_get_clean();
    }

    public function itSelectTheSourceField()
    {
        $this->assertPattern('/SELECTED>Planned Start Date</s', $this->output);
        $this->assertPattern('/SELECTED>Actual Start Date</s', $this->output);
    }

    public function itSelectTheTargetField()
    {
        $this->assertPattern('/SELECTED>Planned End Date</s', $this->output);
        $this->assertPattern('/SELECTED>Actual End Date</s', $this->output);
    }

    public function itSelectTheComparator()
    {
        $this->assertPattern('/SELECTED>=</s', $this->output);
        $this->assertPattern('/SELECTED>&lt;</s', $this->output);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_addRuleTest extends Tracker_Workflow_Action_Rules_EditRules_processTest
{

    public function itAddsARule()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create($this->source_field_id, $this->target_field_id, $this->tracker_id, '>')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainTheComparator()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '22',
        ))->build();

        expect($this->date_factory)->create()->never();
        expect($GLOBALS['Response'])->addFeedback()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainTheSourceField()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheSourceFieldIsNotAnInt()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '%invalid_id%',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheSourceFieldIsNotAnGreaterThanZero()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '-1',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheSourceFieldIsNotChoosen()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '0',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainTheTargetField()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetFieldIsNotAnInt()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '%invalid_id%',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetFieldIsNotAnGreaterThanZero()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '-1',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetFieldIsNotChoosen()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '0',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainAValidComparator()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '%invalid_comparator%',
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetAndSourceFieldsAreTheSame()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '44',
            self::PARAMETER_COMPARATOR   => '>',
        ))->build();
        expect($this->date_factory)->create()->never();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itProvidesFeedbackIfRuleSuccessfullyCreated()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotAddDateRuleIfTheSourceFieldIsNotADateOne()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '666',
            self::PARAMETER_TARGET_FIELD => '22',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotAddDateRuleIfTheTargetFieldIsNotADateOne()
    {
        $request = aRequest()->with(self::PARAMETER_ADD_RULE, array(
            self::PARAMETER_SOURCE_FIELD => '44',
            self::PARAMETER_TARGET_FIELD => '666',
            self::PARAMETER_COMPARATOR   => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_updateRuleTest extends Tracker_Workflow_Action_Rules_EditRules_processTest
{

    private $rule_42_id = 42;
    private $rule_42;
    private $rule_66_id = 66;
    private $rule_66;

    public function setUp()
    {
        parent::setUp();
        $this->rule_42 = mock('Tracker_Rule_Date');
        stub($this->rule_42)->getId()->returns($this->rule_42_id);
        stub($this->rule_42)->getSourceField()->returns($this->planned_start_date);
        stub($this->rule_42)->getTargetField()->returns($this->actual_start_date);
        stub($this->rule_42)->getComparator()->returns('<');
        stub($this->date_factory)->getRule($this->tracker, $this->rule_42_id)->returns($this->rule_42);

        $this->rule_66 = mock('Tracker_Rule_Date');
        stub($this->rule_66)->getId()->returns($this->rule_66_id);
        stub($this->rule_42)->getSourceField()->returns($this->actual_start_date);
        stub($this->rule_42)->getTargetField()->returns($this->planned_start_date);
        stub($this->rule_42)->getComparator()->returns('>');
        stub($this->date_factory)->getRule($this->tracker, $this->rule_66_id)->returns($this->rule_66);
    }

    public function itUpdatesARule()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => '44',
                self::PARAMETER_TARGET_FIELD => '22',
                self::PARAMETER_COMPARATOR   => '>'
            ),
        ))->build();

        expect($this->rule_42)->setSourceField($this->planned_start_date)->once();
        expect($this->rule_42)->setTargetField($this->actual_start_date)->once();
        expect($this->rule_42)->setComparator('>')->once();
        expect($this->date_factory)->save($this->rule_42)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itUpdatesMoreThanOneRule()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
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
        ))->build();

        expect($this->rule_42)->setSourceField($this->planned_start_date)->once();
        expect($this->rule_66)->setSourceField($this->actual_start_date)->once();
        expect($this->date_factory)->save($this->rule_42)->at(0);
        expect($this->date_factory)->save($this->rule_66)->at(1);
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotUpdateTheRuleIfTheNewSourceFieldIsNotADateOne()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => '666',
                self::PARAMETER_TARGET_FIELD => '22',
                self::PARAMETER_COMPARATOR   => '>'
            ),
        ))->build();

        expect($this->rule_42)->setSourceField()->never();
        expect($this->date_factory)->save($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotUpdateTheRuleIfTheNewTargetFieldIsNotADateOne()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => '44',
                self::PARAMETER_TARGET_FIELD => '666',
                self::PARAMETER_COMPARATOR   => '>'
            ),
        ))->build();

        expect($this->rule_42)->setTargetField()->never();
        expect($this->date_factory)->save($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotUpdateTheRuleIfTheNewComparatorIsNotValid()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => '44',
                self::PARAMETER_TARGET_FIELD => '22',
                self::PARAMETER_COMPARATOR   => '%invalid_comparator%'
            ),
        ))->build();

        expect($this->rule_42)->setComparator()->never();
        expect($this->date_factory)->save($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfTheTargetFieldIsMissingFromTheRequest()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => '44',
                self::PARAMETER_COMPARATOR   => '<'
            ),
        ))->build();

        expect($this->rule_42)->setComparator()->never();
        expect($this->date_factory)->save($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfTheSourceFieldIsMissingFromTheRequest()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_TARGET_FIELD => '22',
                self::PARAMETER_COMPARATOR   => '<'
            ),
        ))->build();

        expect($this->rule_42)->setComparator()->never();
        expect($this->date_factory)->save($this->rule_42)->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfTheRuleDoesNotBelongToTracker()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "%invalid_rule_id%" => array(
                self::PARAMETER_SOURCE_FIELD => '44',
                self::PARAMETER_TARGET_FIELD => '22',
                self::PARAMETER_COMPARATOR   => '<'
            ),
        ))->build();

        expect($this->date_factory)->save()->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotUpdateIfTheRuleDoesNotChange()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => $this->rule_42->getSourceField()->getId(),
                self::PARAMETER_TARGET_FIELD => $this->rule_42->getTargetField()->getId(),
                self::PARAMETER_COMPARATOR   => $this->rule_42->getComparator()
            ),
        ))->build();

        expect($this->date_factory)->save()->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotUpdateTheRuleIfTheTargetAndSourceFieldsAreTheSame()
    {
         $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
            "$this->rule_42_id" => array(
                self::PARAMETER_SOURCE_FIELD => '22',
                self::PARAMETER_TARGET_FIELD => '22',
                self::PARAMETER_COMPARATOR   => '>'
            ),
         ))->build();

        expect($this->date_factory)->save()->never();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itProvidesFeedbackIfRulesSuccessfullyUpdated()
    {
        $request = aRequest()->with(self::PARAMETER_UPDATE_RULES, array(
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
        ))->build();

        stub($this->date_factory)->save()->returns(true);
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }
}

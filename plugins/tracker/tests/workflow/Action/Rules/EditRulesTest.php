<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once TRACKER_BASE_DIR .'/workflow/Action/Rules/EditRules.class.php';

class Tracker_Workflow_Action_Rules_EditRules_processTest extends TuleapTestCase {

    protected $remove_parameter = Tracker_Workflow_Action_Rules_EditRules::PARAMETER_REMOVE_RULES;
    protected $tracker_id       = 42;
    protected $element_factory;
    protected $tracker;

    protected $source_field_id        = 44;
    protected $target_field_id        = 22;
    protected $actual_source_field_id = 66;
    protected $actual_target_field_id = 55;

    public function setUp() {
        parent::setUp();
        $this->element_factory    = stub('Tracker_FormElementFactory')->getFormElementsByType()->returns(array());
        $this->tracker            = stub('Tracker')->getId()->returns($this->tracker_id);
        $planned_start_date = $this->setUpField($this->source_field_id, 'Planned Start Date');
        $actual_start_date  = $this->setUpField($this->target_field_id, 'Actual Start Date');
        $planned_end_date   = $this->setUpField($this->actual_source_field_id, 'Planned End Date');
        $actual_end_date    = $this->setUpField($this->actual_target_field_id, 'Actual End Date');
        $this->rule_1       = $this->setUpRule(123, $planned_start_date, Tracker_Rule_Date::COMPARATOR_LESS_THAN, $planned_end_date);
        $this->rule_2       = $this->setUpRule(456, $actual_start_date, Tracker_Rule_Date::COMPARATOR_LESS_THAN, $actual_end_date);
        $this->layout       = mock('Tracker_IDisplayTrackerLayout');
        $this->user         = mock('User');
        $this->date_factory = mock('Tracker_Rule_Date_Factory');
        stub($this->date_factory)->searchById(123)->returns($this->rule_1);
        stub($this->date_factory)->searchById(456)->returns($this->rule_2);
        stub($this->date_factory)->searchByTrackerId($this->tracker_id)->returns(array($this->rule_1, $this->rule_2));
        $this->action = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, $this->element_factory, $this->date_factory);
    }

    private function setUpField($id, $label) {
         $field = stub('Tracker_FormElement_Field_Date')->getLabel()->returns($label);
         stub($field)->getId()->returns($id);
         stub($this->element_factory)->getUsedFieldByIdAndType($this->tracker, $id, 'date')->returns($field);
         return $field;
    }

    private function setUpRule($id, $source_field, $comparator, $target_field) {
        $rule = new Tracker_Rule_Date();
        $rule->setId($id);
        $rule->setSourceField($source_field);
        $rule->setComparator($comparator);
        $rule->setTargetField($target_field);
        return $rule;
    }

    protected function processRequestAndExpectRedirection($request) {
        expect($GLOBALS['Response'])->redirect()->once();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertEqual('', $content);
    }

    protected function processRequestAndExpectFormOutput($request) {
        expect($GLOBALS['Response'])->redirect()->never();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $content = ob_get_clean();
        $this->assertNotEqual('', $content);
    }

    public function itDoesNotDisplayErrorsIfNoActions() {
        $request = aRequest()->build();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->never();
        $this->processRequestAndExpectFormOutput($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_deleteTest extends Tracker_Workflow_Action_Rules_EditRules_processTest {

    public function setUp() {
        parent::setUp();
        stub($this->date_factory)->deleteById()->returns(true);
    }

    public function itDeletesARule() {
        $request = aRequest()->with($this->remove_parameter, array('123'))->build();
        expect($this->date_factory)->deleteById($this->tracker_id, 123)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDeletesMultipleRules() {
        $request = aRequest()->with($this->remove_parameter, array('123','456'))->build();
        expect($this->date_factory)->deleteById($this->tracker_id, 123)->at(0);
        expect($this->date_factory)->deleteById($this->tracker_id, 456)->at(1);
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfRequestDoesNotContainAnArray() {
        $request = aRequest()->with($this->remove_parameter, '123')->build();
        expect($this->date_factory)->deleteById()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotFailIfRequestContainsIrrevelantId() {
        $request = aRequest()->with($this->remove_parameter, array('invalid_id'))->build();
        expect($this->date_factory)->deleteById($this->tracker_id, 0)->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfRequestDoesNotContainRemoveParameter() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '21',
            'target_date_field' => '14'
        ))->build();
        expect($this->date_factory)->deleteById()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itProvidesFeedbackWhenDeletingARule() {
        $request = aRequest()->with($this->remove_parameter, array('123'))->build();
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotPrintMultipleTimesTheFeedbackWhenRemovingMoreThanOneRule() {
        $request = aRequest()->with($this->remove_parameter, array('123', '456'))->build();
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_failedDeleteTest extends Tracker_Workflow_Action_Rules_EditRules_processTest {

    public function itDoesNotPrintSuccessfullFeebackIfTheDeleteFailed() {
        $request = aRequest()->with($this->remove_parameter, array('123'))->build();
        stub($this->date_factory)->deleteById()->returns(false);
        expect($GLOBALS['Response'])->addFeedback('info', '*')->never();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotStopOnTheFirstFailedDelete() {
        $request = aRequest()->with($this->remove_parameter, array('123', '456'))->build();
        stub($this->date_factory)->deleteById($this->tracker_id, 123)->at(0)->returns(false);
        stub($this->date_factory)->deleteById($this->tracker_id, 456)->at(1)->returns(true);
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->processRequestAndExpectRedirection($request);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_getRulesTest extends Tracker_Workflow_Action_Rules_EditRules_processTest {

    public function itRetrieveRules() {
        $request = aRequest()->build();
        ob_start();
        $this->action->process($this->layout, $request, $this->user);
        $output = ob_get_clean();

        $this->assertPattern('/Planned Start Date.*<.*Planned End Date/', $output);
        $this->assertPattern('/Actual Start Date.*<.*Actual End Date/', $output);
    }
}

class Tracker_Workflow_Action_Rules_EditRules_addRuleTest extends Tracker_Workflow_Action_Rules_EditRules_processTest {

    public function itAddsARule() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create($this->source_field_id, $this->target_field_id, $this->tracker_id, '>')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainTheComparator() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '22',
        ))->build();

        expect($this->date_factory)->create()->never();
        expect($GLOBALS['Response'])->addFeedback()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainTheSourceField() {
        $request = aRequest()->withParams(array(
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheSourceFieldIsNotAnInt() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '%invalid_id%',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheSourceFieldIsNotAnGreaterThanZero() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '-1',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheSourceFieldIsNotChoosen() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '0',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainTheTargetField() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetFieldIsNotAnInt() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '%invalid_id%',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetFieldIsNotAnGreaterThanZero() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '-1',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetFieldIsNotChoosen() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '0',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheRequestDoesNotContainAValidComparator() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '22',
            'comparator'        => '%invalid_comparator%',
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotCreateTheRuleIfTheTargetAndSourceFieldsAreTheSame() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '44',
            'comparator'        => '>',
        ))->build();
        expect($this->date_factory)->create()->never();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itProvidesFeedbackIfRuleSuccessfullyCreated() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();
        expect($GLOBALS['Response'])->addFeedback('info','*')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotAddDateRuleIfTheSourceFieldIsNotADateOne() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '666',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        stub($this->element_factory)->getUsedFieldByIdAndType($this->tracker, 666, 'date')->returns(null);
        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotAddDateRuleIfTheTargetFieldIsNotADateOne() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '666',
            'comparator'        => '>'
        ))->build();

        stub($this->element_factory)->getUsedFieldByIdAndType($this->tracker, 666, 'date')->returns(null);
        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

}
?>

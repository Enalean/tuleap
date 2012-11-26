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

    public function setUp() {
        parent::setUp();
        $this->rule_1       = $this->setUpRule(123, 'Planned Start Date', Tracker_Rule_Date::COMPARATOR_LESS_THAN, 'Planned End Date');
        $this->rule_2       = $this->setUpRule(456, 'Actual Start Date', Tracker_Rule_Date::COMPARATOR_LESS_THAN, 'Actual End Date');
        $tracker            = stub('Tracker')->getId()->returns($this->tracker_id);
        $this->element_factory    = stub('Tracker_FormElementFactory')->getFormElementsByType()->returns(array());
        $this->layout       = mock('Tracker_IDisplayTrackerLayout');
        $this->user         = mock('User');
        $this->date_factory = mock('Tracker_Rule_Date_Factory');
        stub($this->date_factory)->searchById(123)->returns($this->rule_1);
        stub($this->date_factory)->searchById(456)->returns($this->rule_2);
        stub($this->date_factory)->searchByTrackerId($this->tracker_id)->returns(array($this->rule_1, $this->rule_2));
        $this->action = new Tracker_Workflow_Action_Rules_EditRules($tracker, $this->element_factory, $this->date_factory);
    }

    public function setUpRule($id, $source_label, $comparator, $target_label) {
        $planned_start_date = stub('Tracker_FormElement_Field_Date')->getLabel()->returns($source_label);
        $planned_end_date   = stub('Tracker_FormElement_Field_Date')->getLabel()->returns($target_label);
        $rule = new Tracker_Rule_Date();
        $rule->setId($id);
        $rule->setSourceField($planned_start_date);
        $rule->setComparator($comparator);
        $rule->setTargetField($planned_end_date);
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
}

class Tracker_Workflow_Action_Rules_EditRules_deleteTest extends Tracker_Workflow_Action_Rules_EditRules_processTest {

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

    private $source_field_id = 44;
    private $target_field_id = 22;

    public function itAddsARules() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create($this->source_field_id, $this->target_field_id, $this->tracker_id, '>')->once();
        $this->processRequestAndExpectRedirection($request);
    }

    public function itDoesNotFailIfTheRequestDoesNotContainTheComparator() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'target_date_field' => '22',
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotFailIfTheRequestDoesNotContainTheSourceField() {
        $request = aRequest()->withParams(array(
            'target_date_field' => '22',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }

    public function itDoesNotFailIfTheRequestDoesNotContainTheTargetField() {
        $request = aRequest()->withParams(array(
            'source_date_field' => '44',
            'comparator'        => '>'
        ))->build();

        expect($this->date_factory)->create()->never();
        $this->processRequestAndExpectFormOutput($request);
    }
}
?>

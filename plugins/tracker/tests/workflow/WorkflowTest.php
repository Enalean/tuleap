<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../../include/workflow/Workflow.class.php');
require_once(dirname(__FILE__).'/../../include/workflow/Transition.class.php');
Mock::generate('Transition');
require_once(dirname(__FILE__).'/../../include/workflow/WorkflowManager.class.php');
require_once(dirname(__FILE__).'/../../include/workflow/WorkflowFactory.class.php');

Mock::generatePartial('Workflow', 'WorkflowTestVersion', array('getTransitions'));
require_once(dirname(__FILE__).'/../../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');
require_once(dirname(__FILE__).'/../../include/Tracker/FormElement/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');
Mock::generate('Tracker_FormElement_Field_List_Value');

Mock::generate('Tracker_Artifact_Changeset');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_Artifact_ChangesetValue_List');
Mock::generate('Tracker_Artifact_Changeset_Null');
class WorkflowTest extends UnitTestCase {
    
    public function testEmptyWorkflow() {
        $workflow = new WorkflowTestVersion(1, 2, 3, 1);
        $workflow->setReturnValue('getTransitions', array());
        $this->assertNotNull($workflow->getTransitions());
        $this->assertEqual(count($workflow->getTransitions()),0);
        
        $field_value_new = array('id' => 2066,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'New',
                                                           'description' => 'The bug has been submitted',
                                                           'rank' => '10');
        $field_value_analyzed = array('id' => 2067,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Analyzed',
                                                           'description' => 'The bug is analyzed',
                                                           'rank' => '20');
        // workflow is empty, no transition exists
        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        $this->assertFalse($workflow->hasTransitions());
    }
    
    public function testUseCaseBug() {
        $field_value_new = array('id' => 2066,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'New',
                                                           'description' => 'The bug has been submitted',
                                                           'rank' => '10');
        $field_value_analyzed = array('id' => 2067,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Analyzed',
                                                           'description' => 'The bug is analyzed',
                                                           'rank' => '20');
        $field_value_accepted = array('id' => 2068,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Accepted',
                                                           'description' => 'The bug is accepted',
                                                           'rank' => '30');
        $field_value_rejected = array('id' => 2069,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Rejected',
                                                           'description' => 'The bug is rejected',
                                                           'rank' => '40');
        $field_value_fixed = array('id' => 2070,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Fixed',
                                                           'description' => 'The bug was resolved',
                                                           'rank' => '50');
        $field_value_tested = array('id' => 2071,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Tested',
                                                           'description' => 'The bug is tested',
                                                           'rank' => '60');
        $field_value_deployed = array('id' => 2072,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Deployed',
                                                           'description' => 'The bug is deployed',
                                                           'rank' => '70');
        
        $t_new_analyzed = new Transition (1, 2, $field_value_new, $field_value_analyzed);
        $t_analyzed_accepted = new Transition (1, 2, $field_value_analyzed, $field_value_accepted);
        $t_analyzed_rejected = new Transition (1,  2, $field_value_analyzed, $field_value_rejected);
        $t_accepted_fixed = new Transition (1,  2, $field_value_accepted, $field_value_fixed);
        $t_fixed_tested = new Transition (1,  2, $field_value_fixed, $field_value_tested);
        $t_tested_deployed = new Transition (1,  2, $field_value_tested, $field_value_deployed);
        
        $transitions= array($t_new_analyzed, 
                                                 $t_analyzed_accepted,
                                                 $t_analyzed_rejected,
                                                 $t_accepted_fixed, 
                                                 $t_fixed_tested, 
                                                 $t_tested_deployed);
        
        $workflow=new Workflow(1, 2, 3, 1, $transitions);
        
        $this->assertNotNull($workflow->getTransitions());
        $this->assertTrue($workflow->hasTransitions());
        // Test existing transition
        $this->assertTrue($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        $this->assertTrue($workflow->isTransitionExist($field_value_analyzed, $field_value_accepted));
        $this->assertTrue($workflow->isTransitionExist($field_value_analyzed, $field_value_rejected));
        $this->assertTrue($workflow->isTransitionExist($field_value_accepted, $field_value_fixed));
        $this->assertTrue($workflow->isTransitionExist($field_value_fixed, $field_value_tested));
        $this->assertTrue($workflow->isTransitionExist($field_value_tested, $field_value_deployed));
        
        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_tested));
        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_rejected));
        $this->assertFalse($workflow->isTransitionExist($field_value_analyzed, $field_value_new));
        $this->assertFalse($workflow->isTransitionExist($field_value_accepted, $field_value_rejected));
        
    } 

    public function testExport() {
        $ft1 = new MockTracker_FormElement_Field_List();
        $ff2 = new MockTracker_FormElement_Field_List();
        $ft2 = new MockTracker_FormElement_Field_List();
        $ff3 = new MockTracker_FormElement_Field_List();
        $ft3 = new MockTracker_FormElement_Field_List();
        
        $ft1->setReturnValue('getId', 806);
        $ff2->setReturnValue('getId', 806);
        $ft2->setReturnValue('getId', 807);
        $ff3->setReturnValue('getId', 807);
        $ft3->setReturnValue('getId', 806);
        
        $t1 = new MockTransition();
        $t2 = new MockTransition();
        $t3 = new MockTransition();
        
        $t1->setReturnValue('getFieldValueFrom',  null);
        $t1->setReturnReference('getFieldValueTo',  $ft1);
        $t2->setReturnReference('getFieldValueFrom',  $ff2);
        $t2->setReturnReference('getFieldValueTo',  $ft2);
        $t3->setReturnReference('getFieldValueFrom',  $ff3);
        $t3->setReturnReference('getFieldValueTo',  $ft3);
        
        $transitions = array($t1, $t2, $t3);
        
        $w = new Workflow(1, 2, 103, 1, $transitions); 
        
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/importWorkflow.xml');
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker xmlns="http://codendi.org/tracker" />');
        
        $array_xml_mapping = array('F32' => 103,
                                   'values' => array(
                                       'F32-V0' => 806,
                                       'F32-V1' => 807)
                                   );
        $w->exportToXML($root, $array_xml_mapping);
        
        $this->assertEqual((string)$xml->field_id['REF'], (string)$root->field_id['REF']);
        $this->assertEqual((int)$xml->is_used, (int)$root->is_used);
        $this->assertEqual(count($xml->transitions), count($root->transitions));
    } 
    
    function testNonTransitionAlwaysExist() {
        $workflow = new WorkflowTestVersion(1, 2, 3, 1);
        $workflow->expectNever('getTransitions');
        $field_value = array();
        $this->assertTrue($workflow->isTransitionExist($field_value, $field_value));
    }
    
    function testBeforeShouldTriggerTransitionActions() {
        $f = new MockTracker_FormElement_Field_List();
        $f->setReturnValue('getId', 103);
        
        $v1 = new MockTracker_FormElement_Field_List_Value();
        $v2 = new MockTracker_FormElement_Field_List_Value();
        
        $v1->setReturnValue('getId', 801);
        $v2->setReturnValue('getId', 802);
        
        $t1 = new MockTransition();
        $t2 = new MockTransition();
        
        $t1->setReturnValue('getFieldValueFrom',     null);
        $t1->setReturnReference('getFieldValueTo',   $v1);
        $t2->setReturnReference('getFieldValueFrom', $v1);
        $t2->setReturnReference('getFieldValueTo',   $v2);
        
        $cvl = new MockTracker_Artifact_ChangesetValue_List();
        $cvl->setReturnValue('getValue', array(801));
        
        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValue', $cvl, array($f));
        
        $a = new MockTracker_Artifact();
        $a->setReturnValue('getLastChangeset', $changeset);
        
        $workflow_id = 1;
        $tracker_id  = 2;
        $field_id    = 103;
        $is_used     = 1;
        $transitions = array($t1, $t2);
        $workflow    = new Workflow($workflow_id, $tracker_id, $field_id, $is_used, $transitions);
        
        $workflow->setField($f);
        $workflow->setArtifact($a);
        
        $fields_data = array(
            '103' => '802',
        );
        $t1->expectNever('before');
        $t2->expectOnce('before');
        $workflow->before($fields_data);
    }
    
    function testBeforeShouldTriggerTransitionActionsForNewArtifact() {
        $f = new MockTracker_FormElement_Field_List();
        $f->setReturnValue('getId', 103);
        
        $v1 = new MockTracker_FormElement_Field_List_Value();
        $v2 = new MockTracker_FormElement_Field_List_Value();
        
        $v1->setReturnValue('getId', 801);
        $v2->setReturnValue('getId', 802);
        
        $t1 = new MockTransition();
        $t2 = new MockTransition();
        
        $t1->setReturnValue('getFieldValueFrom',     null);
        $t1->setReturnReference('getFieldValueTo',   $v1);
        $t2->setReturnReference('getFieldValueFrom', $v1);
        $t2->setReturnReference('getFieldValueTo',   $v2);
        
        $c = new MockTracker_Artifact_Changeset_Null();
        
        $a = new MockTracker_Artifact();
        $a->setReturnValue('getLastChangeset', $c);
        
        $workflow_id = 1;
        $tracker_id  = 2;
        $field_id    = 103;
        $is_used     = 1;
        $transitions = array($t1, $t2);
        $workflow    = new Workflow($workflow_id, $tracker_id, $field_id, $is_used, $transitions);
        
        $workflow->setField($f);
        $workflow->setArtifact($a);
        
        $fields_data = array(
            '103' => '801',
        );
        $t1->expectOnce('before');
        $t2->expectNever('before');
        $workflow->before($fields_data);
    }

}
?>
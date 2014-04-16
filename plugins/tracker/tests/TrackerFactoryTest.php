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
require_once('bootstrap.php');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('Tracker_SharedFormElementFactory');
Mock::generate('Tracker');
Mock::generatePartial('TrackerFactory',
                      'TrackerFactoryTestVersion',
                      array('getCannedResponseFactory',
                            'getFormElementFactory',
                            'getTooltipFactory',
                            'getReportFactory',
                      )
);
Mock::generatePartial('TrackerFactory',
                      'TrackerFactoryTestVersion2',
                      array('getDao',
                            'getProjectManager',
                            'getTrackerById',
                            'isNameExists',
                            'isShortNameExists',
                            'getReferenceManager'
                      )
);

Mock::generate('TrackerDao');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/reference/ReferenceManager.class.php');
Mock::generate('ReferenceManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
Mock::generate('Tracker_CannedResponseFactory');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_TooltipFactory');
Mock::generate('Tracker_ReportFactory');
require_once('common/include/Response.class.php');
Mock::generate('response');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class TrackerFactoryTest extends UnitTestCase {


    public function setUp() {
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }
    
    public function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }
    
    //testing tracker import
    public function testImport() {
        $tracker_factory = new TrackerFactoryTestVersion();
        $cf = new MockTracker_CannedResponseFactory();
        $tracker_factory->setReturnReference('getCannedResponseFactory', $cf);
        $ff = new MockTracker_FormElementFactory();
        $tracker_factory->setReturnReference('getFormElementFactory', $ff);
        $tf = new MockTracker_TooltipFactory();
        $tracker_factory->setReturnReference('getTooltipFactory', $tf);
        $rf = new MockTracker_ReportFactory();
        $tracker_factory->setReturnReference('getReportFactory', $rf);
        
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/TestTracker-1.xml');
        $tracker = $tracker_factory->getInstanceFromXML($xml, 0, '', '', '');
        
        //testing general properties
        $this->assertEqual($tracker->submit_instructions, 'some submit instructions');
        $this->assertEqual($tracker->browse_instructions, 'and some for browsing');

        $this->assertEqual($tracker->getColor(), 'inca_gray');

        //testing default values
        $this->assertEqual($tracker->allow_copy, 0);
        $this->assertEqual($tracker->instantiate_for_new_projects, 1);
        $this->assertEqual($tracker->stop_notification, 0);
    }
    
    public function testImpossibleToCreateTrackerWhenProjectHasAReferenceEqualsShortname() {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $dao = new MockTrackerDao();
        $dao->setReturnValue('duplicate', 999);
        $tracker_factory->setReturnReference('getDao', $dao);
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(456));
        $tracker_factory->setReturnReference('getProjectManager', $pm);
        $tracker = new MockTracker();
        $tracker_factory->setReturnReference('getTrackerById', $tracker, array(999));
        $tracker_factory->setReturnValue('isNameExists', false, array("My New Tracker", 123)); // name is not already used
        $tracker_factory->setReturnValue('isShortNameExists', false, array("existingreference", 123));// shortname is  not already used
        $rm = new MockReferenceManager();
        $rm->setReturnValue('_isKeywordExists', true, array("existingreference", 123)); // shortname already exist as a reference in the project
        $tracker_factory->setReturnReference('getReferenceManager', $rm);
        
        // check that an error is returned if we try to create a tracker 
        // with a shortname already used as a reference in the project
        $project_id = 123; 
        $group_id_template = 456; 
        $id_template = 789;
        $name = 'My New Tracker';
        $description = 'My New Tracker to manage my brand new artifacts';
        $itemname = 'existingreference';
        $this->assertFalse($tracker_factory->create($project_id,$group_id_template,$id_template,$name,$description,$itemname));
    }
    
    public function testImpossibleToCreateTrackerWithAlreadyUsedName() {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $dao = new MockTrackerDao();
        $dao->setReturnValue('duplicate', 999);
        $tracker_factory->setReturnReference('getDao', $dao);
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(456));
        $tracker_factory->setReturnReference('getProjectManager', $pm);
        
        $rm = new MockReferenceManager();
        $rm->setReturnValue('_isKeywordExists', false, array("mynewtracker", 123)); // keyword is not alreay used
        $tracker_factory->setReturnReference('getReferenceManager', $rm);
        $tracker = new MockTracker();
        $tracker_factory->setReturnReference('getTrackerById', $tracker, array(999));
        $tracker_factory->setReturnValue('isNameExists', true, array("My New Tracker With an existing name", 123)); // Name already exists
        $tracker_factory->setReturnValue('isShortNameExists', false, array("mynewtracker", 123));// shortname is  not already used
        // check that an error is returned if we try to create a tracker 
        // with a name (not shortname) already used
        $project_id = 123; 
        $group_id_template = 456; 
        $id_template = 789;
        $name = 'My New Tracker With an existing name';
        $description = 'My New Tracker to manage my brand new artifacts';
        $itemname = 'mynewtracker';
        $this->assertFalse($tracker_factory->create($project_id,$group_id_template,$id_template,$name,$description,$itemname));
    }
    
    public function testImpossibleToCreateTrackerWithAlreadyUsedShortName() {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $dao = new MockTrackerDao();
        $dao->setReturnValue('duplicate', 999);
        $tracker_factory->setReturnReference('getDao', $dao);
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(456));
        $tracker_factory->setReturnReference('getProjectManager', $pm);
        
        $rm = new MockReferenceManager();
        $rm->setReturnValue('_isKeywordExists', false, array("MyNewTracker", 123)); // keyword is not alreay used
        $tracker_factory->setReturnReference('getReferenceManager', $rm);
        $tracker = new MockTracker();
        $tracker_factory->setReturnReference('getTrackerById', $tracker, array(999));
        $tracker_factory->setReturnValue('isNameExists', false, array("My New Tracker", 123));// name is not already used
        $tracker_factory->setReturnValue('isShortNameExists', true, array("MyNewTracker", 123));// shortname is  already used
        // check that an error is returned if we try to create a tracker 
        // with a name (not shortname) already used
        $project_id = 123; 
        $group_id_template = 456; 
        $id_template = 789;
        $name = 'My New Tracker';
        $description = 'My New Tracker to manage my brand new artifacts';
        $itemname = 'MyNewTracker';
        $this->assertFalse($tracker_factory->create($project_id,$group_id_template,$id_template,$name,$description,$itemname));
    }

    
    public function testGetPossibleChildrenShouldNotContainSelf() {
        $current_tracker   = aTracker()->withId(1)->withName('Stories')->build();
        $expected_children = array(
            '2' => aTracker()->withId(2)->withName('Bugs')->build(),
            '3' => aTracker()->withId(3)->withName('Tasks')->build(),
        );
        $all_project_trackers      = $expected_children;
        $all_project_trackers['1'] = $current_tracker;
        
        $tracker_factory   = TestHelper::getPartialMock('TrackerFactory', array('getTrackersByGroupId'));
        $tracker_factory->setReturnValue('getTrackersByGroupId', $all_project_trackers);
        
        $possible_children = $tracker_factory->getPossibleChildren($current_tracker);
        
        $this->assertEqual($possible_children, $expected_children);
    }

}

class TrackerFactoryDuplicationTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->tracker_factory   = TestHelper::getPartialMock('TrackerFactory',
                      array('create',
                            'getTrackersByGroupId',
                            'getHierarchyFactory',
                            'getFormElementFactory',
                            'getTriggerRulesManager'
                      ));
        $this->hierarchy_factory     = new MockTracker_HierarchyFactory();
        $this->trigger_rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');
        $this->formelement_factory   = mock('Tracker_FormElementFactory');

        $this->tracker_factory->setReturnValue('getHierarchyFactory', $this->hierarchy_factory);
        $this->tracker_factory->setReturnValue('getFormElementFactory', $this->formelement_factory);
        $this->tracker_factory->setReturnValue('getTriggerRulesManager', $this->trigger_rules_manager);
        
    }
    
    
    public function testDuplicate_duplicatesAllTrackers_withHierarchy() {
        
        $t1 = $this->GivenADuplicatableTracker(1234);
        stub($t1)->getName()->returns('Bugs');
        stub($t1)->getDescription()->returns('Bug Tracker');
        stub($t1)->getItemname()->returns('bug');
        
        $trackers = array($t1);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));

        $t_new = stub('Tracker')->getId()->returns(555); 
        
        $this->tracker_factory->setReturnValue('create', array('tracker' => $t_new, 'field_mapping' => array())) ;
        
        $this->tracker_factory->expectOnce('create', array(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', null)); 
        
        $this->hierarchy_factory->expectOnce('duplicate');
        
        $this->tracker_factory->duplicate(100, 999, null);
    }
    
    public function testDuplicate_duplicatesSharedFields() {

        $t1 = $this->GivenADuplicatableTracker(123);
        $t2 = $this->GivenADuplicatableTracker(567);
       
        $trackers = array($t1, $t2);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));
        
        $t_new1 = stub('Tracker')->getId()->returns(1234);        
        $t_new2 = stub('Tracker')->getId()->returns(5678);
        
        $t_new1_field_mapping = array(array('from' => '11', 'to' => '111'),
                                      array('from' => '22', 'to' => '222'));
        $t_new2_field_mapping = array(array('from' => '33', 'to' => '333'),
                                      array('from' => '44', 'to' => '444'));
        $full_field_mapping = array_merge($t_new1_field_mapping, $t_new2_field_mapping);
        $to_project_id   = 999;
        $from_project_id = 100;
        $this->tracker_factory->setReturnValue('create', 
                                                array('tracker' => $t_new1, 'field_mapping' => $t_new1_field_mapping), 
                                                array($to_project_id, $from_project_id, 123, '*', '*', '*', null));
        $this->tracker_factory->setReturnValue('create', 
                                                array('tracker' => $t_new2, 'field_mapping' => $t_new2_field_mapping), 
                                                array($to_project_id, $from_project_id, 567, '*', '*', '*', null)) ;
        
        $this->formelement_factory->expectOnce('fixOriginalFieldIdsAfterDuplication', array($to_project_id, $from_project_id, $full_field_mapping));
        $this->tracker_factory->duplicate($from_project_id, $to_project_id, null);
    }
    
    public function testDuplicate_ignoresNonDuplicatableTrackers() {
        $t1 = new MockTracker();
        $t1->setReturnValue('mustBeInstantiatedForNewProjects', false);
        $t1->setReturnValue('getId', 5678);
        $trackers = array($t1);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));
        
        $this->tracker_factory->expectNever('create');
        
        $this->tracker_factory->duplicate(100, 999, null);
    }
        
    private function GivenADuplicatableTracker($tracker_id) {
        $t1 = new MockTracker();
        $t1->setReturnValue('mustBeInstantiatedForNewProjects', true);
        $t1->setReturnValue('getId', $tracker_id);
        return $t1;
    }

    public function testDuplicate_duplicatesAllTriggerRules() {

        $t1 = $this->GivenADuplicatableTracker(1234);
        stub($t1)->getName()->returns('Bugs');
        stub($t1)->getDescription()->returns('Bug Tracker');
        stub($t1)->getItemname()->returns('bug');

        $trackers = array($t1);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));

        $t_new = stub('Tracker')->getId()->returns(555);

        $this->tracker_factory->setReturnValue('create', array('tracker' => $t_new, 'field_mapping' => array())) ;

        $this->tracker_factory->expectOnce('create', array(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', null));

        $this->trigger_rules_manager->expectOnce('duplicate');

        $this->tracker_factory->duplicate(100, 999, null);
    }

}


class TrackerFactoryInstanceFromXMLTest extends UnitTestCase {

    public function testGetInstanceFromXmlGeneratesRulesFromDependencies() {
        
        $data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tracker />
XML;
        $xml = new SimpleXMLElement($data);
        $xml->addChild('cannedResponses');
        $xml->addChild('formElements');
        
        $groupId     = 15; 
        $name        = 'the tracker';
        $description = 'tracks stuff'; 
        $itemname    = 'the item';
        
        $mocked_tracker_factory_methods = array(
            'getInstanceFromRow',
            'getRuleFactory'
        );        
        $tracker_factory = partial_mock('TrackerFactory', $mocked_tracker_factory_methods);     
        $rule_factory = mock('Tracker_RuleFactory');
        $tracker      = mock('Tracker');
        
        stub($tracker_factory)->getInstanceFromRow()->returns($tracker);
        stub($tracker_factory)->getRuleFactory()->returns($rule_factory);
        
        //create data passed
        $dependencies = $xml->addChild('dependencies');
        $rule = $dependencies->addChild('rule');
        $rule->addChild('source_field')->addAttribute('REF', 'F1');
        $rule->addChild('target_field')->addAttribute('REF', 'F2');
        $rule->addChild('source_value')->addAttribute('REF', 'F3');
        $rule->addChild('target_value')->addAttribute('REF', 'F4');
        
        //create data expected
        $expected_xml = new SimpleXMLElement($data);
        $expected_rules = $expected_xml->addChild('rules');
        $list_rules = $expected_rules->addChild('list_rules');
        $expected_rule = $list_rules->addChild('rule');
        $expected_rule->addChild('source_field')->addAttribute('REF', 'F1');
        $expected_rule->addChild('target_field')->addAttribute('REF', 'F2');
        $expected_rule->addChild('source_value')->addAttribute('REF', 'F3');
        $expected_rule->addChild('target_value')->addAttribute('REF', 'F4');
       
        //this is where we check the data has been correctly transformed
        stub($rule_factory)->getInstanceFromXML($expected_rules, array(), $tracker)->once();
        
        $tracker_factory->getInstanceFromXML($xml,$groupId, $name, $description, $itemname);
    }
    
}
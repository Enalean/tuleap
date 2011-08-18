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
var_dump(get_include_path());
//require_once('common/dao/include/DataAccessObject.class.php');
//require_once(dirname(__FILE__).'/../include/Tracker/Tooltip/Tracker_Tooltip.class.php');
require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');
require_once(dirname(__FILE__).'/../include/Tracker/TrackerFactory.class.php');
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
Mock::generatePartial('TrackerFactory',
                      'TrackerFactoryTestVersion3',
                      array('create',
                            'getTrackersByGroupId'
                      )
);

require_once(dirname(__FILE__).'/../include/Tracker/dao/TrackerDao.class.php');
Mock::generate('TrackerDao');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/reference/ReferenceManager.class.php');
Mock::generate('ReferenceManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
require_once(dirname(__FILE__).'/../include/Tracker/CannedResponse/Tracker_CannedResponseFactory.class.php');
Mock::generate('Tracker_CannedResponseFactory');
require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElementFactory.class.php');
Mock::generate('Tracker_FormElementFactory');
require_once(dirname(__FILE__).'/../include/Tracker/Tooltip/Tracker_TooltipFactory.class.php');
Mock::generate('Tracker_TooltipFactory');
require_once(dirname(__FILE__).'/../include/Tracker/Report/Tracker_ReportFactory.class.php');
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
    
    public function testDuplicate() {
        $tracker_factory = new TrackerFactoryTestVersion3();
        $t1 = new MockTracker();
        $t1->setReturnValue('mustBeInstantiatedForNewProjects', true);
        $t1->setReturnValue('getId', 1234);
        $t1->setReturnValue('getName', 'Bugs');
        $t1->setReturnValue('getDescription', 'Bug Tracker');
        $t1->setReturnValue('getItemname', 'bug');
        $t2 = new MockTracker();
        $t2->setReturnValue('mustBeInstantiatedForNewProjects', false);
        $t2->setReturnValue('getId', 5678);
        $trackers = array($t1, $t2);
        $tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));
        
        $t_new = new MockTracker();
        $t_new->setReturnValue('getId', 555);
        
        $tracker_factory->setReturnReferenceAt(0, 'create', $t_new);
        
        // only one call is expected with following arguments:
        $tracker_factory->expectOnce('create', array(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', null, array())); // how to test that the args are 1234 and not 5678???
        
        $tracker_factory->duplicate(100, 999, null);
    }
}
?>
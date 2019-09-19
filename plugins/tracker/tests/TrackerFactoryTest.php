<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\TrackerColor;

require_once('bootstrap.php');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('Tracker_SharedFormElementFactory');
Mock::generate('Tracker');
Mock::generatePartial(
    'TrackerFactory',
    'TrackerFactoryTestVersion',
    array('getCannedResponseFactory',
                            'getFormElementFactory',
                            'getTooltipFactory',
                            'getReportFactory',
                      )
);
Mock::generatePartial(
    'TrackerFactory',
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
Mock::generate('ProjectManager');
Mock::generate('ReferenceManager');
Mock::generate('Project');
Mock::generate('Tracker_CannedResponseFactory');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_ReportFactory');
Mock::generate('response');
Mock::generate('BaseLanguage');

class TrackerFactoryTest extends TuleapTestCase
{


    public function setUp()
    {
        parent::setUp();
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }

    public function tearDown()
    {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testImpossibleToCreateTrackerWhenProjectHasAReferenceEqualsShortname()
    {
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
        $this->assertFalse($tracker_factory->create($project_id, $group_id_template, $id_template, $name, $description, $itemname));
    }

    public function testImpossibleToCreateTrackerWithAlreadyUsedName()
    {
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
        $this->assertFalse($tracker_factory->create($project_id, $group_id_template, $id_template, $name, $description, $itemname));
    }

    public function testImpossibleToCreateTrackerWithAlreadyUsedShortName()
    {
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
        $this->assertFalse($tracker_factory->create($project_id, $group_id_template, $id_template, $name, $description, $itemname));
    }


    public function testGetPossibleChildrenShouldNotContainSelf()
    {
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

class TrackerFactoryDuplicationTest extends TuleapTestCase
{

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function setUp()
    {
        parent::setUp();
        $this->tracker_factory   = TestHelper::getPartialMock(
            'TrackerFactory',
            array('create',
                            'getTrackersByGroupId',
                            'getHierarchyFactory',
                            'getFormElementFactory',
                            'getTriggerRulesManager',
            )
        );
        $this->hierarchy_factory     = new MockTracker_HierarchyFactory();
        $this->trigger_rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');
        $this->formelement_factory   = mock('Tracker_FormElementFactory');

        $this->tracker_factory->setReturnValue('getHierarchyFactory', $this->hierarchy_factory);
        $this->tracker_factory->setReturnValue('getFormElementFactory', $this->formelement_factory);
        $this->tracker_factory->setReturnValue('getTriggerRulesManager', $this->trigger_rules_manager);
    }


    public function testDuplicate_duplicatesAllTrackers_withHierarchy()
    {
        $t1 = $this->GivenADuplicatableTracker(1234);
        stub($t1)->getName()->returns('Bugs');
        stub($t1)->getDescription()->returns('Bug Tracker');
        stub($t1)->getItemname()->returns('bug');

        $trackers = array($t1);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));

        $t_new = stub('Tracker')->getId()->returns(555);

        $this->tracker_factory->setReturnValue('create', array('tracker' => $t_new, 'field_mapping' => array(), 'report_mapping' => array())) ;

        $this->tracker_factory->expectOnce('create', array(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', null));

        $this->hierarchy_factory->expectOnce('duplicate');

        $this->tracker_factory->duplicate(100, 999, null);
    }

    public function testDuplicate_duplicatesSharedFields()
    {
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
        $this->tracker_factory->setReturnValue(
            'create',
            array('tracker' => $t_new1, 'field_mapping' => $t_new1_field_mapping, 'report_mapping' => array()),
            array($to_project_id, $from_project_id, 123, '*', '*', '*', null)
        );
        $this->tracker_factory->setReturnValue(
            'create',
            array('tracker' => $t_new2, 'field_mapping' => $t_new2_field_mapping, 'report_mapping' => array()),
            array($to_project_id, $from_project_id, 567, '*', '*', '*', null)
        ) ;

        $this->formelement_factory->expectOnce('fixOriginalFieldIdsAfterDuplication', array($to_project_id, $from_project_id, $full_field_mapping));
        $this->tracker_factory->duplicate($from_project_id, $to_project_id, null);
    }

    public function testDuplicate_ignoresNonDuplicatableTrackers()
    {
        $t1 = new MockTracker();
        $t1->setReturnValue('mustBeInstantiatedForNewProjects', false);
        $t1->setReturnValue('getId', 5678);
        $trackers = array($t1);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));

        $this->tracker_factory->expectNever('create');

        $this->tracker_factory->duplicate(100, 999, null);
    }

    private function GivenADuplicatableTracker($tracker_id)
    {
        $t1 = new MockTracker();
        $t1->setReturnValue('mustBeInstantiatedForNewProjects', true);
        $t1->setReturnValue('getId', $tracker_id);
        return $t1;
    }

    public function testDuplicate_duplicatesAllTriggerRules()
    {
        $t1 = $this->GivenADuplicatableTracker(1234);
        stub($t1)->getName()->returns('Bugs');
        stub($t1)->getDescription()->returns('Bug Tracker');
        stub($t1)->getItemname()->returns('bug');

        $trackers = array($t1);
        $this->tracker_factory->setReturnReference('getTrackersByGroupId', $trackers, array(100));

        $t_new = stub('Tracker')->getId()->returns(555);

        $this->tracker_factory->setReturnValue('create', array('tracker' => $t_new, 'field_mapping' => array(), 'report_mapping' => array())) ;

        $this->tracker_factory->expectOnce('create', array(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', null));

        $this->trigger_rules_manager->expectOnce('duplicate');

        $this->tracker_factory->duplicate(100, 999, null);
    }
}

class TrackerFactoryCollectErrorWithoutImportingTest extends TuleapTestCase
{
    public const PROJECT_ID = 123;

    public function itDoesNotFindErrorsWhenTrackerInformationsAreValid()
    {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $tracker_factory->setReturnValue('isNameExists', false, array('My New Tracker', self::PROJECT_ID));
        $tracker_factory->setReturnValue('isShortNameExists', false, array('ref', self::PROJECT_ID));
        $reference_manager = new MockReferenceManager();
        $reference_manager->setReturnValue('_isKeywordExists', false, array('ref', self::PROJECT_ID));
        $tracker_factory->setReturnReference('getReferenceManager', $reference_manager);

        $tracker = $this->getTracker(
            'My New Tracker',
            'My New Tracker to manage my brand new artifacts',
            'ref'
        );

        $trackers_name_error = $tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo(
            array($tracker),
            self::PROJECT_ID
        );
        $this->assertEqual($trackers_name_error, array());
    }

    public function itFindsErrorsWhenProjectHasAReferenceEqualsToTrackerShortname()
    {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $tracker_factory->setReturnValue('isNameExists', false, array('My New Tracker', self::PROJECT_ID));
        $tracker_factory->setReturnValue('isShortNameExists', false, array('existingreference', self::PROJECT_ID));
        $reference_manager = new MockReferenceManager();
        $reference_manager->setReturnValue('_isKeywordExists', true, array('existingreference', self::PROJECT_ID));
        $tracker_factory->setReturnReference('getReferenceManager', $reference_manager);

        $tracker = $this->getTracker(
            'My New Tracker',
            'My New Tracker to manage my brand new artifacts',
            'existingreference'
        );

        $trackers_name_error = $tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo(
            array($tracker),
            self::PROJECT_ID
        );
        $this->assertEqual($trackers_name_error, array('My New Tracker'));
    }

    public function itFindsErrorsWhenTrackerTryToUseAnAlreadyUsedShortName()
    {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $tracker_factory->setReturnValue('isNameExists', false, array('My New Tracker', self::PROJECT_ID));
        $tracker_factory->setReturnValue('isShortNameExists', true, array('ref', self::PROJECT_ID));
        $reference_manager = new MockReferenceManager();
        $reference_manager->setReturnValue('_isKeywordExists', false, array('ref', self::PROJECT_ID));
        $tracker_factory->setReturnReference('getReferenceManager', $reference_manager);

        $tracker = $this->getTracker(
            'My New Tracker',
            'My New Tracker to manage my brand new artifacts',
            'ref'
        );

        $trackers_name_error = $tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo(
            array($tracker),
            self::PROJECT_ID
        );
        $this->assertEqual($trackers_name_error, array('My New Tracker'));
    }

    public function itFindsErrorsWhenTrackerTryToUseAnAlreadyUsedName()
    {
        $tracker_factory = new TrackerFactoryTestVersion2();
        $tracker_factory->setReturnValue('isNameExists', true, array('My New Tracker', self::PROJECT_ID));
        $tracker_factory->setReturnValue('isShortNameExists', false, array('ref', self::PROJECT_ID));
        $reference_manager = new MockReferenceManager();
        $reference_manager->setReturnValue('_isKeywordExists', false, array('ref', self::PROJECT_ID));
        $tracker_factory->setReturnReference('getReferenceManager', $reference_manager);

        $tracker = $this->getTracker(
            'My New Tracker',
            'My New Tracker to manage my brand new artifacts',
            'ref'
        );

        $trackers_name_error = $tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo(
            array($tracker),
            self::PROJECT_ID
        );
        $this->assertEqual($trackers_name_error, array('My New Tracker'));
    }

    /**
     * @return Tracker
     */
    private function getTracker($name, $description, $shortname)
    {
        return new Tracker(1, self::PROJECT_ID, $name, $description, $shortname, 0, '', '', '', '', 0, 0, 0, TrackerColor::default(), 0);
    }
}

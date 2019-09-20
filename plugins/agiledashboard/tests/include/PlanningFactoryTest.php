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

use Tuleap\Tracker\Hierarchy\HierarchyDAO;

require_once dirname(__FILE__).'/../bootstrap.php';

Mock::generate('Planning');
Mock::generate('PlanningDao');
Mock::generate('Tracker');

abstract class PlanningFactoryTest extends TuleapTestCase
{

    /**
     * @var PFUser
     */
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->user = aUser()->build();
    }
}

class PlanningFactoryTest_getPlanningTest extends PlanningFactoryTest
{

    public function itCanRetrieveBothAPlanningAndItsTrackers()
    {
        $group_id            = 42;
        $planning_id         = 17;
        $planning_tracker_id = 54;
        $backlog_tracker_id  = 89;

        $planning_dao                 = mock('PlanningDao');
        $tracker_factory              = mock('TrackerFactory');
        $planning_tracker             = mock('Tracker');
        $backlog_tracker              = mock('Tracker');
        $planning_permissions_manager = stub('PlanningPermissionsManager')->savePlanningPermissionForUgroups()->returns(true);
        $planning_factory             = aPlanningFactory()->withDao($planning_dao)
                                                          ->withTrackerFactory($tracker_factory)
                                                          ->withPlanningPermissionsManager($planning_permissions_manager)
                                                          ->build();

        $planning_rows = mock('DataAccessResult');
        $planning_row  = array('id'                  => $planning_id,
                               'name'                => 'Foo',
                               'group_id'            => $group_id,
                               'planning_tracker_id' => $planning_tracker_id,
                               'backlog_title'       => 'Release Backlog',
                               'plan_title'          => 'Sprint Plan');

        stub($tracker_factory)->getTrackerById($planning_tracker_id)->returns($planning_tracker);
        stub($tracker_factory)->getTrackerById($backlog_tracker_id)->returns($backlog_tracker);

        stub($planning_dao)->searchById($planning_id)->returns($planning_rows);
        stub($planning_rows)->getRow()->returns($planning_row);

        stub($planning_dao)->searchBacklogTrackersById($planning_id)->returnsDar(array('tracker_id' => $backlog_tracker_id));

        $planning = $planning_factory->getPlanning($planning_id);

        $this->assertIsA($planning, 'Planning');
        $this->assertEqual($planning->getPlanningTracker(), $planning_tracker);
        $this->assertEqual($planning->getBacklogTrackers(), array($backlog_tracker));
    }
}

class PlanningFactory_duplicationTest extends PlanningFactoryTest
{

    public function itDuplicatesPlannings()
    {
        $dao     = new MockPlanningDao();
        $factory = partial_mock('PlanningFactory', array('getPlanning'), array(
            $dao,
            mock('TrackerFactory'),
            mock('PlanningPermissionsManager')
        ));

        $group_id = 123;

        $sprint_tracker_id      = 1;
        $story_tracker_id       = 2;
        $bug_tracker_id         = 3;
        $faq_tracker_id         = 4;
        $sprint_tracker_copy_id = 5;
        $story_tracker_copy_id  = 6;
        $bug_tracker_copy_id    = 7;
        $faq_tracker_copy_id    = 8;

        stub($factory)->getPlanning(1)->returns(aPlanning()->withId(1)->withGroupId($group_id)->build());

        $tracker_mapping = array($sprint_tracker_id => $sprint_tracker_copy_id,
                                 $story_tracker_id  => $story_tracker_copy_id,
                                 $bug_tracker_id    => $bug_tracker_copy_id,
                                 $faq_tracker_id    => $faq_tracker_copy_id);

        $sprint_planning_name = 'Sprint Planning';

        $rows = TestHelper::arrayToDar(
            array('id'                  => 1,
                  'name'                => $sprint_planning_name,
                  'group_id'            => 101,
                  'backlog_title'       => 'Backlog',
                  'plan_title'          => 'Plan',
                  'planning_tracker_id' => $sprint_tracker_id)
        );

        stub($dao)->searchByPlanningTrackerIds(array_keys($tracker_mapping))->returns($rows);

        stub($dao)->searchBacklogTrackersById(1)->returnsDar(array(
            'planning_id' => 1,
            'tracker_id'  => $story_tracker_id
        ));

        stub($dao)->searchById(1)->returnsDar(array(
            'id'                  => 1,
            'name'                => $sprint_planning_name,
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => array($story_tracker_copy_id)
        ));

        $expected_paramters = PlanningParameters::fromArray(array(
            'id'                  => 1,
            'name'                => $sprint_planning_name,
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => array($story_tracker_copy_id)
        ));

        $dao->expectOnce('createPlanning', array($group_id,
                                                 $expected_paramters));

        $factory->duplicatePlannings($group_id, $tracker_mapping, array());
    }

    public function itDoesNothingIfThereAreNoTrackerMappings()
    {
        $dao     = new MockPlanningDao();
        $factory = aPlanningFactory()->withDao($dao)->build();
        $group_id = 123;
        $empty_tracker_mapping = array();

        $dao->expectNever('createPlanning');

        $factory->duplicatePlannings($group_id, $empty_tracker_mapping, array());
    }

    public function itTranslatesUgroupsIdsFromUgroupsMapping()
    {
        $dao                          = new MockPlanningDao();
        $planning_permissions_manager = stub('PlanningPermissionsManager')->getGroupIdsWhoHasPermissionOnPlanning()->returns(array(4,103,104));
        $factory                      = partial_mock('PlanningFactory', array('getPlanning'), array(
            $dao,
            mock('TrackerFactory'),
            $planning_permissions_manager
        ));

        $group_id = 123;

        $sprint_tracker_id      = 1;
        $story_tracker_id       = 2;
        $bug_tracker_id         = 3;
        $faq_tracker_id         = 4;
        $sprint_tracker_copy_id = 5;
        $story_tracker_copy_id  = 6;
        $bug_tracker_copy_id    = 7;
        $faq_tracker_copy_id    = 8;

        stub($factory)->getPlanning(1)->returns(aPlanning()->withId(1)->withGroupId($group_id)->build());

        $tracker_mapping = array($sprint_tracker_id => $sprint_tracker_copy_id,
                                 $story_tracker_id  => $story_tracker_copy_id,
                                 $bug_tracker_id    => $bug_tracker_copy_id,
                                 $faq_tracker_id    => $faq_tracker_copy_id);

        $sprint_planning_name = 'Sprint Planning';

        $rows = TestHelper::arrayToDar(
            array('id'                  => 1,
                  'name'                => $sprint_planning_name,
                  'group_id'            => 101,
                  'backlog_title'       => 'Backlog',
                  'plan_title'          => 'Plan',
                  'planning_tracker_id' => $sprint_tracker_id)
        );

        stub($dao)->searchByPlanningTrackerIds(array_keys($tracker_mapping))->returns($rows);

        stub($dao)->searchBacklogTrackersById(1)->returnsDar(array(
            'planning_id' => 1,
            'tracker_id'  => $story_tracker_id
        ));

        stub($dao)->searchById(1)->returnsDar(array(
            'id'                  => 1,
            'name'                => $sprint_planning_name,
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => array($story_tracker_copy_id)
        ));

        $expected_paramters = PlanningParameters::fromArray(array(
            'id'                  => 1,
            'name'                => $sprint_planning_name,
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => array($story_tracker_copy_id)
        ));

        $expected_ugroups = array(4, 113, 114);
        expect($planning_permissions_manager)->savePlanningPermissionForUgroups('*', '*', PlanningPermissionsManager::PERM_PRIORITY_CHANGE, $expected_ugroups)->once();

        $ugroups_mapping = array(
            103 => 113,
            104 => 114
        );

        $factory->duplicatePlannings($group_id, $tracker_mapping, $ugroups_mapping);
    }
}

class PlanningFactoryTest_getPlanningByPlanningTrackerTest extends PlanningFactoryTest
{

    public function setUp()
    {
        parent::setUp();
        $this->tracker   = aMockTracker()->withId(99)->build();
        $dao             = stub('PlanningDao')->searchByPlanningTrackerId()->returnsDar(
            array(
                'id' => 1,
                'name' => 'Release Planning',
                'group_id' => 102,
                'planning_tracker_id' => 103,
                'backlog_title' => 'Release Backlog',
                'plan_title' => 'Sprint Plan'
            )
        );
        stub($dao)->searchBacklogTrackersById(1)->returnsDar(
            array(
                'planning_id' => 1,
                'tracker_id'  => 104,
            )
        );

        $this->planning_tracker = aMockTracker()->withId(103)->build();
        $this->backlog_tracker  = aMockTracker()->withId(104)->build();
        $tracker_factory  = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById(103)->returns($this->planning_tracker);
        stub($tracker_factory)->getTrackerById(104)->returns($this->backlog_tracker);

        $this->factory   = aPlanningFactory()->withDao($dao)->withTrackerFactory($tracker_factory)->build();
    }

    public function itReturnsNothingIfThereIsNoAssociatedPlanning()
    {
        $tracker   = aMockTracker()->withId(99)->build();
        $empty_dar = TestHelper::arrayToDar();
        $dao       = stub('PlanningDao')->searchByPlanningTrackerId()->returns($empty_dar);
        $factory   = aPlanningFactory()->withDao($dao)->build();
        $this->assertNull($factory->getPlanningByPlanningTracker($tracker));
    }

    public function itReturnsAPlanning()
    {
        $planning  = new Planning(1, 'Release Planning', 102, 'Release Backlog', 'Sprint Plan', array());
        $planning->setPlanningTracker($this->planning_tracker);
        $planning->setBacklogTrackers(array($this->backlog_tracker));

        $this->assertEqual($planning, $this->factory->getPlanningByPlanningTracker($this->tracker));
    }

    public function itAddsThePlanningAndTheBacklogTrackers()
    {
        $actual_planning = $this->factory->getPlanningByPlanningTracker($this->tracker);
        $this->assertEqual($this->planning_tracker, $actual_planning->getPlanningTracker());
        $this->assertEqual(array($this->backlog_tracker), $actual_planning->getBacklogTrackers());
    }
}

class PlanningFactoryTest_getPlanningsTest extends PlanningFactoryTest
{

    private $project_id                  = 123;
    private $project_id_without_planning = 124;

    public function setUp()
    {
        parent::setUp();
        $tracker_factory      = TrackerFactory::instance();
        $hierarchy_dao        = Mockery::spy(HierarchyDAO::class);
        $child_link_retriever = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever');
        $hierarchy_factory    = new Tracker_HierarchyFactory(
            $hierarchy_dao,
            $tracker_factory,
            mock('Tracker_ArtifactFactory'),
            $child_link_retriever
        );

        $tracker_factory->setHierarchyFactory($hierarchy_factory);

        $this->setUpTrackers($tracker_factory, $hierarchy_dao);
        $this->setUpPlannings($tracker_factory);
    }

    public function tearDown()
    {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    private function setUpTrackers(TrackerFactory $tracker_factory, HierarchyDAO $hierarchy_dao)
    {
        $this->epic_tracker  = aMockTracker()->withId(104)->build();
        $this->story_tracker = aMockTracker()->withId(103)->build();
        $this->release_tracker  = aMockTracker()->withId(107)->build();
        $this->sprint_tracker = aMockTracker()->withId(108)->build();

        $tracker_factory->setCachedInstances(array(
            104 => $this->epic_tracker,
            103 => $this->story_tracker,
            107 => $this->release_tracker,
            108 => $this->sprint_tracker,
        ));

        stub($hierarchy_dao)->searchTrackerHierarchy(array(103, 104))->returns(
            [['parent_id' => '104', 'child_id' => '103']]
        );
        stub($hierarchy_dao)->searchTrackerHierarchy(array(108, 107))->returns(
            [['parent_id' => '107', 'child_id' => '108']]
        );
        stub($hierarchy_dao)->searchTrackerHierarchy()->returns([]);
    }

    private function setUpPlannings(TrackerFactory $tracker_factory)
    {
        $dao = mock('PlanningDao');
        stub($dao)->searchPlannings($this->project_id)->returnsDar(
            array(
                'id'                  => 1,
                'name'                => 'Sprint Planning',
                'group_id'            => 123,
                'planning_tracker_id' => 108,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan'
            ),
            array(
                'id'                  => 2,
                'name'                => 'Release Planning',
                'group_id'            => 123,
                'planning_tracker_id' => 107,
                'backlog_title'       => 'Product Backlog',
                'plan_title'          => 'Release Plan'
            )
        );

        stub($dao)->searchBacklogTrackersById(1)->returnsDar(array('tracker_id' => 103));
        stub($dao)->searchBacklogTrackersById(2)->returnsDar(array('tracker_id' => 104));
        stub($dao)->searchPlannings($this->project_id_without_planning)->returnsEmptyDar();

        $this->factory = aPlanningFactory()
            ->withDao($dao)
            ->withTrackerFactory($tracker_factory)
            ->build();

        $this->release_planning = new Planning(2, 'Release Planning', 123, 'Product Backlog', 'Release Plan');
        $this->release_planning->setBacklogTrackers(array($this->epic_tracker));
        $this->release_planning->setPlanningTracker($this->release_tracker);
        $this->sprint_planning  = new Planning(1, 'Sprint Planning', 123, 'Release Backlog', 'Sprint Plan');
        $this->sprint_planning->setBacklogTrackers(array($this->story_tracker));
        $this->sprint_planning->setPlanningTracker($this->sprint_tracker);
    }

    public function itReturnsAnEmptyArrayIfThereIsNoPlanningDefinedForAProject()
    {
        $this->assertEqual(array(), $this->factory->getPlannings($this->user, $this->project_id_without_planning));
    }

    public function itReturnsAllDefinedPlanningsForAProjectInTheOrderDefinedByTheHierarchy()
    {
        stub($this->release_tracker)->userCanView($this->user)->returns(true);
        stub($this->sprint_tracker)->userCanView($this->user)->returns(true);

        $this->assertEqual(
            $this->factory->getPlannings($this->user, $this->project_id),
            array($this->release_planning, $this->sprint_planning)
        );
    }

    public function itReturnsOnlyPlanningsWhereTheUserCanViewTrackers()
    {
        stub($this->release_tracker)->userCanView($this->user)->returns(true);
        stub($this->sprint_tracker)->userCanView($this->user)->returns(false);

        $this->assertEqual(
            $this->factory->getPlannings($this->user, $this->project_id),
            array($this->release_planning)
        );
    }
}

class PlanningFactoryTest_getPlanningTrackerIdsByGroupIdTest extends PlanningFactoryTest
{

    public function itDelegatesRetrievalOfPlanningTrackerIdsByGroupIdToDao()
    {
        $group_id     = 456;
        $expected_ids = array(1, 2, 3);
        $dao          = mock('PlanningDao');
        $factory      = aPlanningFactory()->withDao($dao)->build();

        stub($dao)->searchPlanningTrackerIdsByGroupId($group_id)->returns($expected_ids);

        $actual_ids = $factory->getPlanningTrackerIdsByGroupId($group_id);
        $this->assertEqual($actual_ids, $expected_ids);
    }
}

class PlanningFactoryTest_getAvailablePlanningTrackersTest extends PlanningFactoryTest
{

    public function itRetrievesAvailablePlanningTrackersIncludingTheCurrentPlanningTracker()
    {
        $group_id                     = 789;
        $planning_dao                 = mock('PlanningDao');
        $tracker_factory              = mock('TrackerFactory');
        $planning_permissions_manager = mock('PlanningPermissionsManager');
        $planning_factory = partial_mock(
            'PlanningFactory',
            array(
                'getPotentialPlanningTrackerIds',
                'getPlanningTrackerIdsByGroupId'
            ),
            array(
                $planning_dao,
                $tracker_factory,
                $planning_permissions_manager
            )
        );

        stub($planning_factory)->getPotentialPlanningTrackerIds()->returns(array(1, 2, 3));
        stub($planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(1, 3));

        $releases_tracker = aTracker()->withId(2)->withName('Releases')->build();

        stub($tracker_factory)->getTrackerById(2)->returns($releases_tracker);

        $actual_trackers = $planning_factory->getAvailablePlanningTrackers(aUser()->build(), $group_id);
        $this->assertEqual($actual_trackers, array($releases_tracker));
    }
}

class PlanningFactoryTest_getVirtualTopPlanningTest extends TuleapTestCase
{

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var PlanningDao */
    private $planning_dao;

    public function setUp()
    {
        parent::setUp();
        $this->user                         = mock('PFUser');
        $this->planning_dao                 = mock('PlanningDao');
        $this->tracker_factory              = mock('TrackerFactory');
        $this->planning_permissions_manager = mock('PlanningPermissionsManager');

        $this->planning_factory = partial_mock(
            'PlanningFactory',
            array('getRootPlanning'),
            array($this->planning_dao, $this->tracker_factory, $this->planning_permissions_manager)
        );
    }
    public function itThrowsAnExceptionIfNoPlanningsExistForProject()
    {
        $this->expectException('Planning_NoPlanningsException');

        $this->planning_factory->getVirtualTopPlanning($this->user, 112);
    }

    public function itCreatesNewPlanningWithValidBacklogAndPlanningTrackers()
    {
        $backlog_tracker  = mock('Tracker');
        $planning_tracker = mock('Tracker');
        $form_element_factory = mock('Tracker_FormElementFactory');

        stub($backlog_tracker)->getId()->returns(78);
        stub($planning_tracker)->getId()->returns(45);

        $my_planning = new Planning(null, null, null, null, null, array(78), 45);
        $my_planning->setBacklogTrackers(array($backlog_tracker))
                ->setPlanningTracker($planning_tracker);

        stub($this->planning_factory)->getRootPlanning()->returns($my_planning);
        stub($this->tracker_factory)->getTrackerById(45)->returns($backlog_tracker);
        stub($this->tracker_factory)->getTrackerById(78)->returns($planning_tracker);

        $planning = $this->planning_factory->getVirtualTopPlanning($this->user, 56);

        $this->assertIsA($planning, 'Planning');
        $this->assertIsA($planning->getPlanningTracker(), 'Tracker');
        $backlog_trackers = $planning->getBacklogTrackers();
        $this->assertIsA($backlog_trackers[0], 'Tracker');
    }
}

class PlanningFactory_getNonLastLevelPlanningsTest extends PlanningFactoryTest
{

    /**
     * @var PlanningDao
     */
    private $planning_dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function setUp()
    {
        parent::setUp();

        $this->planning_dao                 = mock('PlanningDao');
        $this->tracker_factory              = mock('TrackerFactory');
        $this->planning_permissions_manager = mock('PlanningPermissionsManager');
    }

    public function itReturnsAnEmptyArrayIfNoPlanningsExist()
    {
        $factory = partial_mock('PlanningFactory', array('getPlannings'));
        stub($factory)->getPlannings()->returns(array());

        $plannings = $factory->getNonLastLevelPlannings($this->user, 14);

        $this->assertCount($plannings, 0);
    }

    public function itDoesNotReturnLastLevelPlannings()
    {
        $factory = partial_mock(
            'PlanningFactory',
            array('getPlannings'),
            array($this->planning_dao, $this->tracker_factory, $this->planning_permissions_manager)
        );

        $planning_1 = mock('Planning');
        $planning_2 = mock('Planning');
        $planning_3 = mock('Planning');

        stub($planning_1)->getPlanningTrackerId()->returns(11);
        stub($planning_2)->getPlanningTrackerId()->returns(22);
        stub($planning_3)->getPlanningTrackerId()->returns(33);

        stub($factory)->getPlannings()->returns(array($planning_3, $planning_2, $planning_1));

        $hierarchy = mock('Tracker_Hierarchy');
        stub($hierarchy)->getLastLevelTrackerIds()->returns(array(11));
        stub($hierarchy)->sortTrackerIds(array(33, 22))->returns(array(22, 33));
        stub($this->tracker_factory)->getHierarchy()->returns($hierarchy);

        $plannings = $factory->getNonLastLevelPlannings($this->user, 14);

        $this->assertCount($plannings, 2);

        $first_planning  = $plannings[0];
        $second_planning = $plannings[1];

        $this->assertEqual($first_planning->getPlanningTrackerId(), 22);
        $this->assertEqual($second_planning->getPlanningTrackerId(), 33);
    }
}

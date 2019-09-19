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

use Tuleap\Tracker\Hierarchy\HierarchyController;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

require_once __DIR__.'/../../bootstrap.php';


class Tracker_Hierarchy_ControllerTest extends TuleapTestCase
{
    /**
     * @var \Mockery\MockInterface|HierarchyDAO
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface|Tracker_Workflow_Trigger_RulesDao
     */
    private $trigger_rules_dao;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->tracker_id           = 3;
        $project                    = mockery_stub(\Project::class)->getId()->returns(101);
        $this->tracker              = aTracker()->withId($this->tracker_id)->withName('Stories')->withProject($project)->build();
        $this->hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($this->tracker, array());
        $this->request              = aRequest()->withUser(\Mockery::spy(\PFUser::class))->build();
        $this->tracker_factory      = \Mockery::spy(\TrackerFactory::class);
        $this->dao                  = \Mockery::spy(HierarchyDAO::class);
        $this->type_dao             = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $this->factory              = \Mockery::spy(\Tracker_Hierarchy_HierarchicalTrackerFactory::class);
        $this->redirect_url         = TRACKER_BASE_URL."/?tracker=$this->tracker_id&func=admin-hierarchy";
        $this->trigger_rules_dao    = Mockery::spy(Tracker_Workflow_Trigger_RulesDao::class);
        $this->trigger_rules_dao->shouldReceive('searchTriggeringTrackersByTargetTrackerID')->andReturn([]);

        stub($GLOBALS['Language'])->getText()->returns('');
    }

    public function testEditListsAllChildren()
    {
        $possible_children = array('1' => aTracker()->withId(1)->withName('Bugs')->build(),
                                   '2' => aTracker()->withId(2)->withName('Tasks')->build());

        $this->factory->shouldReceive('getPossibleChildren')->with($this->hierarchical_tracker)->andReturns($possible_children);
        $this->factory->shouldReceive('getHierarchy')->andReturns($this->getHierarchyAsTreeNode(array()));

        $content = $this->WhenICaptureTheOutputOfEditAction();

        $this->assertContainsAll(array('value="1".*Bugs', 'value="2".*Tasks'), $content);
    }

    public function testEditDisplaysTheWholeHierarchy()
    {
        $hierarchy = array(
            array('name' => 'Sprints', 'id' => '', 'current_class' => '', 'children' => array(
                array('name' => 'Stories', 'id' => '', 'current_class' => 'current', 'children' => array(
                    array('name' => 'Tasks', 'id' => '', 'current_class' => '', 'children' => array()),
                    array('name' => 'Bugs', 'id' => '', 'current_class' => '', 'children' => array()),
                )),
            ))
        );
        $this->factory->shouldReceive('getPossibleChildren')->andReturns(array());
        $this->factory->shouldReceive('getHierarchy')->with($this->tracker)->once()->andReturns($this->getHierarchyAsTreeNode($hierarchy));

        $content = $this->WhenICaptureTheOutputOfEditAction();

        $this->assertContainsAll(array('Sprint', 'Stories', 'Tasks', 'Bugs'), $content);
        $this->assertPattern('%div class="tree-blank" >[^<]*</div><div class="tree-last"%', $content);
    }

    private function getHierarchyAsTreeNode($hierarchy)
    {
        $node = new TreeNode();
        if (isset($hierarchy['children'])) {
            $node->setData(array('name' => $hierarchy['name'], 'id' => $hierarchy['id'], 'current_class' => ''));
            $node->setId($hierarchy['id']);
            $hierarchy = $hierarchy['children'];
        } else {
            $node->setId('root');
        }
        foreach ($hierarchy as $item) {
            $node->addChild($this->getHierarchyAsTreeNode($item));
        }
        return $node;
    }

    public function testEditProvidesUrlsToTheTrackersInTheHierarchy()
    {
        $sprints_id = 666;
        $stories_id = 999;
        $hierarchy = array(
            array('name' => 'Sprints', 'id' => $sprints_id, 'current_class' => '', 'children' => array(
                array('name' => 'Stories', 'id' => $stories_id, 'current_class' => '', 'children' => array())
            ))
        );
        $this->factory->shouldReceive('getPossibleChildren')->andReturns(array());
        $this->factory->shouldReceive('getHierarchy')->with($this->tracker)->once()->andReturns($this->getHierarchyAsTreeNode($hierarchy));

        $content = $this->WhenICaptureTheOutputOfEditAction();

        $this->assertPattern("%".TRACKER_BASE_URL."/\?tracker=$sprints_id&func=admin-hierarchy%", $content);
        $this->assertPattern("%".TRACKER_BASE_URL."/\?tracker=$stories_id&func=admin-hierarchy%", $content);
    }

    private function WhenICaptureTheOutputOfEditAction()
    {
        ob_start();
        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao,
            $this->trigger_rules_dao
        );
        $controller->edit();
        $content = ob_get_clean();
        return $content;
    }

    public function testUpdateHappyPathShouldCallDaoToSaveHierarchy()
    {
        $children_ids = array('1', '2');

        $this->request->set('children', $children_ids);
        $this->dao->shouldReceive('updateChildren')->with($this->tracker_id, $children_ids)->once();

        $this->expectRedirectTo($this->redirect_url);

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao,
            $this->trigger_rules_dao
        );
        $controller->update();
    }

    public function testWeCanDeleteAllChildrenByNOTprovidingAnArrayOfIds()
    {
        $this->dao->shouldReceive('updateChildren')->with($this->tracker_id, Mockery::any())->once();

        $this->expectRedirectTo($this->redirect_url);

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao,
            $this->trigger_rules_dao
        );

        $controller->update();
    }

    public function testUpdateWithNastyRequestShouldThrowErrors()
    {
        $this->request->set('children', array('DROP DATABASE http://xkcd.com/327/'));
        $this->dao->shouldReceive('updateChildren')->never();

        $this->expectFeedback('error', '*');
        $this->expectRedirectTo($this->redirect_url);

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao,
            $this->trigger_rules_dao
        );
        $controller->update();
    }

    private function assertContainsAll($expected_strings, $actual_text)
    {
        foreach ($expected_strings as $string) {
            $this->assertPattern('/'.$string.'/', $actual_text);
        }
    }

    public function itCreatesHierarchyFromXmlProjectImportProcess()
    {
        $mapping    = array(111,222,333,444);
        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao,
            $this->trigger_rules_dao
        );
        $this->dao->shouldReceive('updateChildren')->once();

        $controller->updateFromXmlProjectImportProcess($mapping);
    }

    public function itDoesNotUpdateHierarchyIfIsChildTypeIsDisabled()
    {
        stub($this->type_dao)->isProjectUsingArtifactLinkTypes()->returns(true);
        stub($this->type_dao)->isTypeDisabledInProject()->returns(true);

        expect($this->dao)->updateChildren()->never();
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo($this->redirect_url);

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao,
            $this->trigger_rules_dao
        );

        $controller->update();
    }
}

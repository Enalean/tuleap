<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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
require_once __DIR__.'/../../bootstrap.php';
Mock::generate('Tracker');
Mock::generate('Tracker_Hierarchy_HierarchicalTrackerFactory');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_Hierarchy_Dao');


class Tracker_Hierarchy_ControllerTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();

        $this->tracker_id           = 3;
        $project                    = stub('Project')->getId()->returns(101);
        $this->tracker              = aTracker()->withId($this->tracker_id)->withName('Stories')->withProject($project)->build();
        $this->hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($this->tracker, array());
        $this->request              = aRequest()->withUser(mock('PFUser'))->build();
        $this->tracker_factory      = new MockTrackerFactory();
        $this->dao                  = new MockTracker_Hierarchy_Dao();
        $this->type_dao             = mock('Tuleap\Tracker\Admin\ArtifactLinksUsageDao');
        $this->factory              = new MockTracker_Hierarchy_HierarchicalTrackerFactory($this->tracker_factory, $this->dao);
        $this->redirect_url         = TRACKER_BASE_URL."/?tracker=$this->tracker_id&func=admin-hierarchy";

        stub($GLOBALS['Language'])->getText()->returns('');
    }

    public function testEditListsAllChildren() {
        $possible_children = array('1' => aTracker()->withId(1)->withName('Bugs')->build(), 
                                   '2' => aTracker()->withId(2)->withName('Tasks')->build());
        
        $this->factory->setReturnValue('getPossibleChildren', $possible_children, array($this->hierarchical_tracker));
        $this->factory->setReturnValue('getHierarchy', $this->getHierarchyAsTreeNode(array()));
        
        $content = $this->WhenICaptureTheOutputOfEditAction();
        
        $this->assertContainsAll(array('value="1".*Bugs', 'value="2".*Tasks'), $content);
    }
    
    public function testEditDisplaysTheWholeHierarchy() {
        $hierarchy = array(
            array('name' => 'Sprints', 'id' => '', 'current_class' => '', 'children' => array(
                array('name' => 'Stories', 'id' => '', 'current_class' => 'current', 'children' => array(
                    array('name' => 'Tasks', 'id' => '', 'current_class' => '', 'children' => array()),
                    array('name' => 'Bugs', 'id' => '', 'current_class' => '', 'children' => array()),
                )),
            ))
        );
        $this->factory->setReturnValue('getPossibleChildren', array());
        $this->factory->expectOnce('getHierarchy', array($this->tracker));
        $this->factory->setReturnValue('getHierarchy', $this->getHierarchyAsTreeNode($hierarchy));
        
        $content = $this->WhenICaptureTheOutputOfEditAction();
        
        $this->assertContainsAll(array('Sprint', 'Stories', 'Tasks', 'Bugs'), $content);
        $this->assertPattern('%div class="tree-blank" >[^<]*</div><div class="tree-last"%', $content);
    }
    
    private function getHierarchyAsTreeNode($hierarchy) {
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
    
    public function testEditProvidesUrlsToTheTrackersInTheHierarchy() {
        $sprints_id = 666;
        $stories_id = 999;
        $hierarchy = array(
            array('name' => 'Sprints', 'id' => $sprints_id, 'current_class' => '', 'children' => array(
                array('name' => 'Stories', 'id' => $stories_id, 'current_class' => '', 'children' => array())
            ))
        );
        $this->factory->setReturnValue('getPossibleChildren', array());
        $this->factory->expectOnce('getHierarchy', array($this->tracker));
        $this->factory->setReturnValue('getHierarchy', $this->getHierarchyAsTreeNode($hierarchy));
        
        $content = $this->WhenICaptureTheOutputOfEditAction();
        
        $this->assertPattern("%".TRACKER_BASE_URL."/\?tracker=$sprints_id&func=admin-hierarchy%", $content);
        $this->assertPattern("%".TRACKER_BASE_URL."/\?tracker=$stories_id&func=admin-hierarchy%", $content);
    }
    
    private function WhenICaptureTheOutputOfEditAction() {
        ob_start();
        $controller = new Tracker_Hierarchy_Controller(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao
        );
        $controller->edit();
        $content = ob_get_clean();
        return $content;
    }
    
    public function testUpdateHappyPathShouldCallDaoToSaveHierarchy() {
        $children_ids = array('1', '2');
        
        $this->request->set('children', $children_ids);
        $this->dao->expectOnce('updateChildren', array($this->tracker_id, $children_ids));
        
        $this->expectRedirectTo($this->redirect_url);

        $controller = new Tracker_Hierarchy_Controller(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao
        );
        $controller->update();
    }
    
    public function testWeCanDeleteAllChildrenByNOTprovidingAnArrayOfIds() {
        $this->dao->expectOnce('deleteAllChildrenWithNature', array($this->tracker_id));
        
        $this->expectRedirectTo($this->redirect_url);
        
        $controller = new Tracker_Hierarchy_Controller(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao
        );

        $controller->update();
    }
    
    public function testUpdateWithNastyRequestShouldThrowErrors() {
        $this->request->set('children', array('DROP DATABASE http://xkcd.com/327/'));
        $this->dao->expectNever('updateChildren');
        
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo($this->redirect_url);

        $controller = new Tracker_Hierarchy_Controller(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao
        );
        $controller->update();
    }
    
    private function assertContainsAll($expected_strings, $actual_text) {
        foreach($expected_strings as $string) {
            $this->assertPattern('/'.$string.'/', $actual_text);
        }
    }

    public function itCreatesHierarchyFromXmlProjectImportProcess() {
        $mapping    = array(111,222,333,444);
        $controller = new Tracker_Hierarchy_Controller(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao
        );
        $this->dao->expectCallCount('updateChildren',1);

        $controller->updateFromXmlProjectImportProcess($mapping);
    }

    public function itDoesNotUpdateHierarchyIfIsChildTypeIsDisabled()
    {
        stub($this->type_dao)->isProjectUsingArtifactLinkTypes()->returns(true);
        stub($this->type_dao)->isTypeDisabledInProject()->returns(true);

        expect($this->dao)->updateChildren()->never();
        expect($this->dao)->deleteAllChildrenWithNature()->never();
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo($this->redirect_url);

        $controller = new Tracker_Hierarchy_Controller(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->type_dao
        );

        $controller->update();
    }
}

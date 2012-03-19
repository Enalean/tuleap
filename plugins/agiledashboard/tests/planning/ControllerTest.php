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

require_once(dirname(__FILE__).'/../../include/Planning/Controller.class.php');
require_once(dirname(__FILE__).'/../../include/Planning/Planning.class.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_Builder.php');
require_once(dirname(__FILE__).'/../builders/planning.php');

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('PlanningFactory');
Mock::generate('Planning');

class Planning_ControllerTest extends TuleapTestCase {
    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $id = 987;
        $title = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfEditActionForAnEmptyArtifact($id, $title);
        $this->assertPattern('/No items yet/', $content);
    }
    
    public function itDisplaysTheArtifactTitleAndId() {
        $id = 987;
        $title = "screen hangs with macos and some escapable characters #<";
        $content = $this->WhenICaptureTheOutputOfEditActionForAnEmptyArtifact($id, $title);
        $this->assertPattern("/art-$id/", $content);
        $this->assertPattern("/$title/", $content);
    }

    public function itListsAllLinkedItems() {
        $id = 987;
        $linked_items = array(
            $this->GivenAnArtifact(123, 'Tutu'),
            $this->GivenAnArtifact(123, 'Tata')
        );
        
        $artifact = $this->GivenAnArtifact($id, 'Toto');
        $artifact->setReturnValue('getLinkedArtifacts', $linked_items);
        $factory = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        $request = new Codendi_Request(array('aid' => $id));

        $content = $this->WhenICaptureTheOutputOfEditAction($request, $factory);
        $this->assertPattern('/Tutu/', $content);
        $this->assertPattern('/Tata/', $content);
    }
    
    private function GivenAnArtifact($id, $title) {
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('fetchTitle', "#$id $title");
        $artifact->setReturnValue('getId', $id);
        return $artifact;
    }
    private function WhenICaptureTheOutputOfEditActionForAnEmptyArtifact($id, $title) {
        $request = new Codendi_Request(array('aid' => $id));
        
        $artifact = $this->GivenAnArtifact($id, $title);
        
        $factory = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        return $this->WhenICaptureTheOutputOfEditAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfEditAction($request, $factory) {
        ob_start();
        $controller = new Planning_Controller($request, $factory, new MockPlanningFactory());
        $controller->display();
        $content = ob_get_clean();
        return $content;
    }
}

abstract class Planning_ControllerIndexTest extends TuleapTestCase {
    function setUp() {
        parent::setUp();
        
        $this->group_id         = '123';
        $this->request          = new Codendi_Request(array('group_id' => $this->group_id));
        $this->artifact_factory = new MockTracker_ArtifactFactory();
        $this->planning_factory = new MockPlanningFactory();
        $this->controller       = new Planning_Controller($this->request, $this->artifact_factory, $this->planning_factory);
    }
    
    protected function renderIndex() {
        $this->planning_factory->expectOnce('getPlannings', array($this->group_id));
        $this->planning_factory->setReturnValue('getPlannings', $this->plannings);
        
        ob_start();
        $this->controller->index();
        $this->output = ob_get_clean();
    }
    
    public function itHasALinkToCreateANewPlanning() {
        $this->assertPattern('/func=create/', $this->output);
    }
}

class Planning_ControllerEmptyIndexTest extends Planning_ControllerIndexTest {
    function setUp() {
        parent::setUp();
        $this->plannings = array();
        $this->renderIndex();
    }
    
    public function itListsNothing() {
        $this->assertNoPattern('/<ul>/', $this->output);
    }
}

class Planning_ControllerNonEmptyIndexTest extends Planning_ControllerIndexTest {
    function setUp() {
        parent::setUp();
        
        $this->plannings = array(
            aPlanning()->withId(1)->withName('Release Planning')->build(),
            aPlanning()->withId(2)->withName('Sprint Planning')->build(),
        );
        
        $this->renderIndex();
    }
    
    public function itListsExistingPlannings() {
        foreach($this->plannings as $planning) {
            $this->assertPattern('/'.$planning->getName().'/', $this->output);
            $this->assertPattern('/href=".*?planning_id='.$planning->getId().'.*"/', $this->output);
        }
    }
}

?>

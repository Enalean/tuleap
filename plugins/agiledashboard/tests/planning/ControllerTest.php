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
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_Builder.php');
if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');

class Planning_ControllerTest extends TuleapTestCase {
    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $id = 987;
        $title = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfEditActionForAnEmptyArtifact($id, $title);
        $this->assertPattern('/No items yet/', $content);
    }
    
    public function itDisplaysTheArtifactTitleAndId() {
        $id = 987;
        $title = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfEditActionForAnEmptyArtifact($id, $title);
        $this->assertPattern("/art-$id/", $content);
        $this->assertPattern("/$title/", $content);
    }
//    public function itListsAllLinkedItems() {
//        $request = new Codendi_Request(array('aid' => 987));
//        $content = $this->WhenICaptureTheOutputOfEditAction($request);
//        $this->assertPattern('/title 1/', $content);
//    }
    
    private function WhenICaptureTheOutputOfEditActionForAnEmptyArtifact($id, $title) {
        $request = new Codendi_Request(array('aid' => $id));
        
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('getId', $id);
        
        $factory = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        return $this->WhenICaptureTheOutputOfEditAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfEditAction($request, $factory) {
        ob_start();
        $controller = new Planning_Controller($request, $factory);
        $controller->display();
        $content = ob_get_clean();
        return $content;
    }
}


?>

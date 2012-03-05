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

require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Presenter.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Controller.class.php');
require_once(dirname(__FILE__).'/../../Test_Tracker_Builder.php');
Mock::generate('Tracker');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_Hierarchy_Dao');

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

class Tracker_Hierarchy_ControllerTest extends TuleapTestCase {

    public function testEditObtainsTheChildrenFromTheFactory() {
        $request = new Codendi_Request(array());
        $tracker = aTracker()->withName('Stories')->build();
        
        $possible_children = array('1' => aTracker()->withId(1)->withName('Bugs')->build(), 
                                   '2' => aTracker()->withId(2)->withName('Tasks')->build());
        
        $factory = new MockTrackerFactory();
        $factory->setReturnValue('getPossibleChildren', $possible_children, array($tracker));
        
        $dao = new MockTracker_Hierarchy_Dao();

        ob_start();
        $controller = new Tracker_Hierarchy_Controller($request, $tracker, $factory, $dao);
        $controller->edit();
        $content = ob_get_clean();
        
        $this->assertContainsAll(array('value="1".*Bugs', 'value="2".*Tasks'), $content);
    }
    
    private function assertContainsAll($expected_strings, $actual_text) {
        foreach($expected_strings as $string) {
            $this->assertPattern('/'.$string.'/', $actual_text);
        }
    }
    
    public function testUpdateHappyPathShouldCallDaoToSaveHierarchy() {
        $tracker_id   = 3;
        $tracker      = aTracker()->withId($tracker_id)->build();
        $factory      = new MockTrackerFactory();
        $children_ids = array('1', '2');
        $request      = new Codendi_Request(array('children' => $children_ids));
        
        $dao = new MockTracker_Hierarchy_Dao();
        $dao->expectOnce('updateChildren', array($tracker_id, $children_ids));
        
        $redirect_url = TRACKER_BASE_URL."/?tracker=$tracker_id&func=admin-hierarchy";
        $GLOBALS['Response']->expectOnce('redirect', array($redirect_url));
        
        $controller = new Tracker_Hierarchy_Controller($request, $tracker, $factory, $dao);
        $controller->update();
    }
    
    public function testUpdateWithNastyRequestShouldThrowErrors() {
        $tracker      = aTracker()->withId(2)->build();
        $factory      = new MockTrackerFactory();
        $children_ids = array('DROP DATABASE http://xkcd.com/327/');
        $request      = new Codendi_Request(array('children' => $children_ids));
        
        $dao = new MockTracker_Hierarchy_Dao();
        $dao->expectNever('updateChildren');
        
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', '*'));
        
        $redirect_url = TRACKER_BASE_URL.'/?tracker=2&func=admin-hierarchy';
        $GLOBALS['Response']->expectOnce('redirect', array($redirect_url));
        
        $controller = new Tracker_Hierarchy_Controller($request, $tracker, $factory, $dao);
        $controller->update();
    }
}

?>

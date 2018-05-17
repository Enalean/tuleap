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
require_once __DIR__.'/../bootstrap.php';
require_once 'common/include/Codendi_Request.class.php';

Mock::generate('Project');

class Tracker_HomeNavPresenterTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->project = new MockProject();
        $this->project->setReturnValue('getId', '104');
    }
    
    public function itHasNavItemsWithLabelAndUrl() {
        $presenter = new Tracker_HomeNavPresenter($this->project);
        $nav_items = $presenter->getNavItems();
        
        $this->assertNotEmpty($nav_items);
        $this->assertRowsIncludeKeys($nav_items, array('label', 'url'));
    }
    
    public function itKnowsWhichNavItemIsTheCurrentOne() {
        $presenter = new Tracker_HomeNavPresenter($this->project, '');
        $nav_items = $presenter->getNavItems();
        $this->assertCurrentItem($nav_items, 0);
        
        $presenter = new Tracker_HomeNavPresenter($this->project, 'cross-search');
        $nav_items = $presenter->getNavItems();
        $this->assertCurrentItem($nav_items, 1);
    }
    
    /***** Assertions *********************************************************/
    
    protected function assertNotEmpty($array) {
        $this->assertTrue(count($array) > 0);
    }
    
    private function assertRowsIncludeKeys($array, $expected_keys) {
        foreach($array as $row) {
            foreach($expected_keys as $expected_key) {
                $this->assertTrue(array_key_exists($expected_key, $row));
            }
        }
    }
    
    private function assertCurrentItem($nav_items, $expected_current_index) {
        for($index=0; $index < count($nav_items); $index++) {
            $current = $nav_items[$index]['current'];
            
            if($index == $expected_current_index) {
                $this->assertEqual($current, 'current');
            } else {
                $this->assertNull($current);
            }
        }
    }
}
?>

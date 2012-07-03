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

class Cardwall_SwimLineFactoryTest extends TuleapTestCase {
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsAndNoNodes() {
        $factory = new Cardwall_SwimlineFactory();
        $columns = array();
        $nodes   = array();
        $swimlines = $factory->getCellsOfSwimline($columns, $nodes);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsButSomeNodes() {
        $factory = new Cardwall_SwimlineFactory();
        $columns = array();
        $nodes   = array(aNode()->build());
        $swimlines = $factory->getCellsOfSwimline($columns, $nodes);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsANestedArrayOfPresenterNodesIfThereAreColumnsButNoNodes() {
        $factory = new Cardwall_SwimlineFactory();
        $id = $label = $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column($id, $label, $bgcolor, $fgcolor));
        $nodes   = array();
        $swimlines = $factory->getCellsOfSwimline($columns, $nodes);
        var_dump($swimlines);
        $expected = array(
                        array('presenter_nodes' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itReturnsANestedArrayOfPresenterNodes() {
        $factory = new Cardwall_SwimlineFactory();
        $label   = 'ongoing';
        $id = $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column($id, $label, $bgcolor, $fgcolor));
        $artifact = stub('Tracker_Artifact')->getStatus()->returns($label);
        $node     = stub('Cardwall_CardInCellPresenterNode')->getArtifact()->returns($artifact);
        
        $swimlines = $factory->getCellsOfSwimline($columns, array($node));
        $expected = array(
                        array('presenter_nodes' => array($node)));
        $this->assertIdentical($expected, $swimlines);
    }
    
    
    
    public function whatAboutIfThereAreNoNodes() {
    }
}
?>

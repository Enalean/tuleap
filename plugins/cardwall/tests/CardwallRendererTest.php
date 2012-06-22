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


require_once dirname(__FILE__).'/../include/Cardwall_Renderer.class.php';

class CardwallRenderer_getForestsOfArtifactsTest extends TuleapTestCase {
    
    public function itCreatesTwoLevelsEvenIfNoArtifactIdsAreGiven() {
        $plugin = $id = $report = $name = $description = $rank = $field_id = $enable_qr_code = null;
        $renderer = new Cardwall_Renderer($plugin, $id, $report, $name, $description, $rank, $field_id, $enable_qr_code);
        $artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($artifact_factory);
        stub($artifact_factory)->getArtifactById()->returns(mock('Tracker_Artifact'));
        
        $root_node = $renderer->getForestsOfArtifacts(array(), $artifact_factory);
        
        $this->assertTrue($root_node->hasChildren());
        $this->assertFalse($root_node->getChild(0)->hasChildren());
        
    }
    
    public function itCreatesATreeOfArtifactNodes() {
        $plugin = $id = $report = $name = $description = $rank = $field_id = $enable_qr_code = null;
        $renderer = new Cardwall_Renderer($plugin, $id, $report, $name, $description, $rank, $field_id, $enable_qr_code);
        $artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($artifact_factory);
        stub($artifact_factory)->getArtifactById()->returns(mock('Tracker_Artifact'));
        
        $root_node = $renderer->getForestsOfArtifacts(array(4, 5, 6), $artifact_factory);
        
        $this->assertTrue($root_node->hasChildren());
        $this->assertTrue($root_node->getChild(0)->hasChildren());
        $tasks = $root_node->getChild(0)->getChildren();
        $this->assertEqual(3, count($tasks));
        foreach ($tasks as $task) {
            $id = $task->getId();
            $this->assertBetweenClosedInterval($id, 4, 6);
//            $data = $task->getData();
//            $this->assertIsA($data['artifact'], 'Tracker_Artifact');
        }
        
    }

    /**
     * Passes if var is inside or equal to either of the two bounds
     * 
     * @param type $var
     * @param type $lower_bound
     * @param type $higher_bound
     */
    protected function assertBetweenClosedInterval($var, $lower_bound, $higher_bound) {
        $this->assertTrue($var <= $higher_bound, "$var should be lesser than or equal to $higher_bound");
        $this->assertTrue($var >= $lower_bound,  "$var should be greater than or equal to $lower_bound");
    }
}
?>

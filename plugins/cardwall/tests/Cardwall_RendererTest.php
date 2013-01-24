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

require_once dirname(__FILE__) .'/bootstrap.php';
require_once dirname(__FILE__).'/../../../tests/simpletest/common/include/builders/aTreeNode.php';
require_once dirname(__FILE__).'/../include/Cardwall_Renderer.class.php';
require_once dirname(__FILE__).'/../../tracker/tests/builders/aMockArtifact.php';
require_once 'common/plugin/Plugin.class.php';


class Cardwall_Renderer_getForestsOfArtifactsTest extends TuleapTestCase {
    
    public function itIntegratesWithThe_ArtifactNodeTreeProvider() {
        $plugin = $id = $report = $name = $description = $rank = $field_id = $enable_qr_code = null;
        $renderer = new Cardwall_Renderer(mock('Plugin'), mock('Cardwall_OnTop_Config'), 
                                          $id, $report, $name, $description, $rank, $field_id, $enable_qr_code);
        $artifact_factory = mock('Tracker_ArtifactFactory');
        
        $artifact4 = aMockArtifact()->withId(4)->build();
        $artifact5 = aMockArtifact()->withId(5)->build();
        $artifact6 = aMockArtifact()->withId(6)->build();
        
        stub($artifact_factory)->getArtifactById(4)->returns($artifact4);
        stub($artifact_factory)->getArtifactById(5)->returns($artifact5);
        stub($artifact_factory)->getArtifactById(6)->returns($artifact6);
        
        $root_node = $renderer->getForestsOfArtifacts(array(4, 5, 6), $artifact_factory);
        
        $this->assertTrue($root_node->hasChildren());
        $this->assertTrue($root_node->getChild(0)->hasChildren());
        $tasks = $root_node->getChild(0)->getChildren();
        $this->assertEqual(3, count($tasks));
        foreach ($tasks as $task) {
            $id = $task->getId();
            $this->assertBetweenClosedInterval($id, 4, 6);
            $artifact = $task->getArtifact();
            $this->assertBetweenClosedInterval($artifact->getId(), 4, 6);
            $this->assertIsA($artifact, 'Tracker_Artifact');
        }

    }
}
?>

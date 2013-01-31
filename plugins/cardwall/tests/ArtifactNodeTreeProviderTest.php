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
require_once 'common/TreeNode/TreeNodeMapper.class.php';

class Cardwall_ArtifactNodeTreeProviderTest extends TuleapTestCase {
        
    public function itCreatesTwoLevelsEvenIfNoArtifactIdsAreGiven() {
        $provider  = new Cardwall_ArtifactNodeTreeProvider();
        
        $root_node = $provider->wrapInAThreeLevelArtifactTree(array());
        
        $this->assertTrue($root_node->hasChildren());
        $this->assertFalse($root_node->getChild(0)->hasChildren());
        
    }
    
    public function itCreatesAThreeLevelTreeBecauseItMustLookLikeTheNodeTreeFromAMilestone() {
        $provider  = new Cardwall_ArtifactNodeTreeProvider();
        $artifact4 = aMockArtifact()->withId(4)->build();
        
        $root_node = $provider->wrapInAThreeLevelArtifactTree(array(new ArtifactNode($artifact4)));

        $this->assertTrue($root_node->hasChildren());
        $this->assertTrue($root_node->getChild(0)->hasChildren());
        $cards = $root_node->getChild(0)->getChildren();

        $this->assertEqual(1, count($cards));
        $card = $cards[0];
        $id   = $card->getId();
        $this->assertEqual($id, 4);
        $artifact = $card->getArtifact();
        $this->assertIdentical($artifact, $artifact4);
        
    }
    
    public function itCreatesAnArtifactNodeForEveryArtifactId() {
        $provider = new Cardwall_ArtifactNodeTreeProvider();
        $artifact_factory = mock('Tracker_ArtifactFactory');
        
        $artifact4 = aMockArtifact()->withId(4)->build();
        $artifact5 = aMockArtifact()->withId(5)->build();
        $artifact6 = aMockArtifact()->withId(6)->build();
        
        stub($artifact_factory)->getArtifactById(4)->returns($artifact4);
        stub($artifact_factory)->getArtifactById(5)->returns($artifact5);
        stub($artifact_factory)->getArtifactById(6)->returns($artifact6);
        
        $cards = $provider->getCards(array(4, 5, 6), $artifact_factory);

        $this->assertEqual(3, count($cards));
        foreach ($cards as $card) {
            $id = $card->getId();
            $this->assertBetweenClosedInterval($id, 4, 6);
            $artifact = $card->getArtifact();
            $this->assertBetweenClosedInterval($artifact->getId(), 4, 6);
            $this->assertIsA($artifact, 'Tracker_Artifact');
        }
        
    }
    
}
?>

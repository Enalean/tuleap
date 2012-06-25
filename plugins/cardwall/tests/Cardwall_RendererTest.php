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


require_once dirname(__FILE__).'/../../../tests/simpletest/common/include/builders/aTreeNode.php';
require_once dirname(__FILE__).'/../include/Cardwall_Renderer.class.php';
require_once dirname(__FILE__).'/../../tracker/tests/builders/aMockArtifact.php';

class Cardwall_Renderer_getForestsOfArtifactsTest extends TuleapTestCase {
    
    public function itCreatesTwoLevelsEvenIfNoArtifactIdsAreGiven() {
        $provider = new CardwallCardProvider();
        
        $root_node = $provider->wrapInAThreeLevelArtifactTree(array());
        
        $this->assertTrue($root_node->hasChildren());
        $this->assertFalse($root_node->getChild(0)->hasChildren());
        
    }
    
    public function itCreatesAThreeLevelTreeBecauseItMustLookLikeTheNodeTreeFromAMilestone() {
        $provider = new CardwallCardProvider();
        
        $artifact4 = aMockArtifact()->withId(4)->build();
        
        $root_node = $provider->wrapInAThreeLevelArtifactTree(array(new ArtifactNode($artifact4)));
        $this->assertTrue($root_node->hasChildren());
        $this->assertTrue($root_node->getChild(0)->hasChildren());
        $cards = $root_node->getChild(0)->getChildren();

        $this->assertEqual(1, count($cards));
        $card = $cards[0];
        $id = $card->getId();
        $this->assertEqual($id, 4);
        $artifact = $card->getArtifact();
        $this->assertIdentical($artifact, $artifact4);
        
    }
    
    public function itCreatesAnArtifactNodeForEveryArtifactId() {
        $provider = new CardwallCardProvider();
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
    
    public function itCallsTheArtifactVisitor() {
        $provider = new CardwallCardProvider();
        $artifact = aMockArtifact()->withId(4)->build();

        $artifact_factory = mock('Tracker_ArtifactFactory');
        stub($artifact_factory)->getArtifactById(4)->returns($artifact);
        $visitor = mock('Cardwall_ArtifactTreeNodeVisitor');
//        $visitor = stub('ArtifactTreeNodeVisitor')->visit($root)->returns(clone $root);
        $result = $provider->provide(array(4), $artifact_factory, $visitor);
        $root = aNode()->withChild(
                    aNode()->withChild(
                        anArtifactNode()->withArtifact($artifact)))
                ->build();

        $visitor->expectOnce('visit', array($root));
        $this->assertIdentical($root->getChild(0)->getChild(0), $result->getChild(0)->getChild(0));
    }
    
    public function itTheVisitorCreatesACardwallPresenterForEveryArtifactNode() {
//        $this->assert
    }

    
    public function itIntegrates() {
        $plugin = $id = $report = $name = $description = $rank = $field_id = $enable_qr_code = null;
        $renderer = new Cardwall_Renderer($plugin, $id, $report, $name, $description, $rank, $field_id, $enable_qr_code);
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
            $presenter = $task->getObject();
            $artifact = $presenter->getArtifact();
            $this->assertBetweenClosedInterval($artifact->getId(), 4, 6);
            $this->assertIsA($artifact, 'Tracker_Artifact');
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

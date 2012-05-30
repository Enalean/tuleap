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

require_once dirname(__FILE__). '/../../../include/Tracker/CrossSearch/Query.class.php';

class QueryTest extends TuleapTestCase {
    public function itCanBeInstantiatedWithoutSemanticQuery() {
        $criteria = new Tracker_CrossSearch_Query(array());
        $this->assertIdentical('', $criteria->getStatus());
        $this->assertIdentical('', $criteria->getTitle());

        $criteria = new Tracker_CrossSearch_Query(array(), array());
        $this->assertIdentical('', $criteria->getStatus());
        $this->assertIdentical('', $criteria->getTitle());
    }
    
    
}

class Query_ArtifactTest extends TuleapTestCase {
    
    public function itReturnsAnEmptyArrayIfNoListIsGiven() {
        $criteria = new Tracker_CrossSearch_Query();
        $this->assertIdentical(array(), $criteria->listArtifactIds());
    }
    
    public function itFlattensTheNestedArrayOfArtifactIds() {
        
        $criteria = $this->givenAnArtifactQuery(array(132 => array(1, 55, 1001)));
        $this->assertEqual(array(1, 55, 1001), $criteria->listArtifactIds());

        $criteria = $this->givenAnArtifactQuery(array(132 => array(1, 55, 1001),
                                           138 => array(99, 2)));
        $this->assertEqual(array(1, 55, 1001, 99, 2), $criteria->listArtifactIds());

    }
    
    private function givenAnArtifactQuery($artifacts) {
        return new Tracker_CrossSearch_Query(array(), array(), $artifacts);
    }
    
    public function itCanReturnTheListOfArtifactsFromATrackerId() {
        $criteria = $this->givenAnArtifactQuery(array(132 => array(1, 55), 456 => array(2, 55)));
        
        $artifacts132 = $criteria->getArtifactsOfTracker(132);
        $artifacts456 = $criteria->getArtifactsOfTracker(456);
        
        $this->assertEqual(1, $artifacts132[0]->getId());
        $this->assertEqual(55, $artifacts132[1]->getId());
        
        $this->assertEqual(2, $artifacts456[0]->getId());
        $this->assertEqual(55, $artifacts456[1]->getId());
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoCorrespondingTrackerIds() {
        $criteria = $this->givenAnArtifactQuery(array(132 => array()));
    
        $this->assertEqual(array(), $criteria->getArtifactsOfTracker(132));
        $this->assertEqual(array(), $criteria->getArtifactsOfTracker(456));
    }
}
?>

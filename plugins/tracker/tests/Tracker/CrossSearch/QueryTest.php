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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';
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
    
    public function itDoesntRemoveArtifactIdsThatAreInTheBlessedList() {
        $query = $this->givenAnArtifactQuery(array('132' => array('1', '2'), '456' => array('3', '4')));
        
        $query->purgeArtifactIdsNotInList(array('1' => 0, '2' => 1, '3' => 4, '4' => 5));
        
        $this->assertEqual(array('1', '2', '3', '4'), $query->listArtifactIds());
    }
    
    public function itRemovesArtifactIdsThatAreNotInTheBlessedList() {
        $query = $this->givenAnArtifactQuery(array('132' => array('1', '2'), '456' => array('3', '4')));
        
        $query->purgeArtifactIdsNotInList(array('6' => 0));
        
        $this->assertEqual(array(), $query->listArtifactIds());
    }
}

class Query_SharedFieldsTest extends TuleapTestCase {
    
    public function itDoesntRemoveSharedFieldsThatAreInTheBlessedList() {
        $shared_field_request = array('220' => array('values' => array('350')));
        $query = new Tracker_CrossSearch_Query($shared_field_request);
        
        $query->purgeSharedFieldNotInList(array(220 => true));
        
        $this->assertEqual($query->getSharedFields(), $shared_field_request);
    }
    
    public function itRemovesSharedFieldsThatAreNotInTheBlessedList() {
        $shared_field_request = array('220' => array('values' => array('350')));
        $query = new Tracker_CrossSearch_Query($shared_field_request);
        
        $query->purgeSharedFieldNotInList(array(330 => true));
        
        $this->assertEqual($query->getSharedFields(), array());
    }
}

class Query_EmptyTest extends TuleapTestCase {

    public function itIsEmptyWhenThereAreNoInputData() {
        $query        = new Tracker_CrossSearch_Query(array(), array(), array());

        $this->assertTrue($query->isEmpty());
    }

    public function itIsEmptyWhenThereAreNoArtifactLinkSelected() {
        $artifact_ids = array(142 => array(), 143 => array());
        $query        = new Tracker_CrossSearch_Query(array(), array(), $artifact_ids);

        $this->assertTrue($query->isEmpty());
    }

    public function itIsNotEmptyWhenThereAreArtifactLinkSelected() {
        $artifact_ids = array(142 => array('898'), 143 => array());
        $query        = new Tracker_CrossSearch_Query(array(), array(), $artifact_ids);

        $this->assertFalse($query->isEmpty());
    }

    public function itIsEmptyWhenThereOnlyEmptyTitle() {
        $sementic_criteria = array('title' => '');
        $query        = new Tracker_CrossSearch_Query(array(), $sementic_criteria, array());

        $this->assertTrue($query->isEmpty());
    }

    public function itIsEmptyWhenThereAreNoSementicStatusSelected() {
        $sementic_criteria = array('title' => '', 'status' => 'any');
        $query             = new Tracker_CrossSearch_Query(array(), $sementic_criteria, array());

        $this->assertTrue($query->isEmpty());
    }

    public function itIsNotEmptyWhenThereIsASementicStatusSelected() {
        $sementic_criteria = array('title' => '', 'status' => 'open');
        $query             = new Tracker_CrossSearch_Query(array(), $sementic_criteria, array());

        $this->assertFalse($query->isEmpty());
    }

    public function itIsEmptyWhenThereAreNoSementicTitleSelected() {
        $sementic_criteria = array('title' => '', 'status' => '');
        $query             = new Tracker_CrossSearch_Query(array(), $sementic_criteria, array());

        $this->assertTrue($query->isEmpty());
    }

    public function itIsNotEmptyWhenThereIsASementicTitleSelected() {
        $sementic_criteria = array('title' => 'zer', 'status' => '');
        $query             = new Tracker_CrossSearch_Query(array(), $sementic_criteria, array());

        $this->assertFalse($query->isEmpty());
    }

    public function itIsEmptyWhenThereAreNoSharedFieldSelected() {
        $shared_fields_criteria = array(1084 => array('values' => array(0 => '')));
        $query                  = new Tracker_CrossSearch_Query($shared_fields_criteria, array(), array());

        $this->assertTrue($query->isEmpty());
    }

    public function itIsNotEmptyWhenThereIsASharedFieldSelected() {
        $shared_fields_criteria = array(1084 => array('values' => array(0 => '1080')));
        $query                  = new Tracker_CrossSearch_Query($shared_fields_criteria, array(), array());

        $this->assertFalse($query->isEmpty());
    }
}

?>

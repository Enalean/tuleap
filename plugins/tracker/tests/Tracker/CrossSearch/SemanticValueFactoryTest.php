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

require_once dirname(__FILE__).'/../../../include/Tracker/CrossSearch/SemanticValueFactory.class.php';

Mock::generate('Tracker');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Semantic_Title');
Mock::generate('Tracker_Semantic_TitleFactory');
Mock::generate('Tracker_Semantic_Status');
Mock::generate('Tracker_Semantic_StatusFactory');

class MockArtifactBuilder {
    public function __construct() {
        $this->id      = 123;
        $this->tracker = new MockTracker();
        
        $this->tracker->setReturnValue('getId', 456);
    }
    
    public function build() {
        $artifact = new MockTracker_Artifact();
        
        $artifact->setReturnValue('getId', $this->id);
        $artifact->setReturnValue('getTracker', $this->tracker);
        
        return $artifact;
    }
}

class MockArtifactFactoryBuilder {
    public function withArtifact($artifact) {
        $this->artifact = $artifact;
        return $this;
    }
    
    public function build() {
        $factory = new MockTracker_ArtifactFactory();
        
        $expected_params = array($this->artifact->getId());
        $factory->expectOnce('getArtifactById', $expected_params);
        $factory->setReturnValue('getArtifactById', $this->artifact, $expected_params);
        
        return $factory;
    }
}

class MockSemanticTitleFactoryBuilder {
    public function __construct() {
        $this->semantic_titles = array();
    }
    
    public function withNoFieldForTracker($tracker) {
        $semantic_title = new MockTracker_Semantic_Title();
        $semantic_title->setReturnValue('getField', null);
        
        $this->semantic_titles[$tracker->getId()] = $semantic_title;
        
        return $this;
    }
    
    public function build() {
        $factory = new MockTracker_Semantic_TitleFactory();
        
        foreach($this->semantic_titles as $tracker => $semantic_title) {
            $expected_params = array($tracker);
//            $factory->expectOnce('getByTracker', $expected_params);
            $factory->setReturnValue('getByTracker', $semantic_title);
        }
        
        return $factory;
    }
}

class MockSemanticStatusFactoryBuilder {
    public function __construct() {
        $this->semantic_statuses = array();
    }
    
    public function withNoFieldForTracker($tracker) {
        $semantic_status = new MockTracker_Semantic_Status();
        $semantic_status->setReturnValue('getField', null);
        
        $this->semantic_statuses[$tracker->getId()] = $semantic_status;
        
        return $this;
    }
    
    public function build() {
        $factory = new MockTracker_Semantic_StatusFactory();
        
        foreach($this->semantic_statuses as $tracker => $semantic_status) {
            $expected_params = array($tracker);
//            $factory->expectOnce('getByTracker', $expected_params);
            $factory->setReturnValue('getByTracker', $semantic_status);
        }
        
        return $factory;
    }
}

function aMockArtifact()              { return new MockArtifactBuilder(); }
function aMockArtifactFactory()       { return new MockArtifactFactoryBuilder(); }
function aMockSemanticTitleFactory()  { return new MockSemanticTitleFactoryBuilder(); }
function aMockSemanticStatusFactory() { return new MockSemanticStatusFactoryBuilder(); }

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticTitleTest extends TuleapTestCase {
    public function itReturnsAnEmptyTitle() {
        $artifact                = aMockArtifact()->build();
        $artifact_factory        = aMockArtifactFactory()->withArtifact($artifact)->build();
        $semantic_title_factory  = aMockSemanticTitleFactory()->withNoFieldForTracker($artifact->getTracker())->build();
        $semantic_status_factory = aMockSemanticStatusFactory()->build();
        
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory, $semantic_status_factory);
        
        $artifact_id  = $artifact->getId();
        $changeset_id = 'whatever';
        
        $this->assertEqual($semantic_value_factory->getTitle($artifact_id, $changeset_id), '');
    }
}

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticStatusTest extends TuleapTestCase {
    public function itReturnsAnEmptyStatus() {
        $artifact                = aMockArtifact()->build();
        $artifact_factory        = aMockArtifactFactory()->withArtifact($artifact)->build();
        $semantic_title_factory  = aMockSemanticTitleFactory()->build();
        $semantic_status_factory = aMockSemanticStatusFactory()->withNoFieldForTracker($artifact->getTracker())->build();
        
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory, $semantic_status_factory);
        
        $artifact_id  = $artifact->getId();
        $changeset_id = 'whatever';
        
        $this->assertEqual($semantic_value_factory->getStatus($artifact_id, $changeset_id), '');
    }
}

?>

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
//Mock::generate('Tracker_Semantic_StatusFactory');

class MockArtifactBuilder {
    public function __construct() {
        $this->id      = 0;
        $this->tracker = new MockTracker();
        
        $this->tracker->setReturnValue('getId', 0);
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
        
        $factory->setReturnValue('getArtifactById', $this->artifact, array($this->artifact->getId()));
        
        return $factory;
    }
}

class MockSemanticTitleFactoryBuilder {
    public function __construct() {
        $this->semantic_titles = array();
    }
    
    public function withNoSemanticForTracker($tracker) {
        $semantic_title = new MockTracker_Semantic_Title();
        $semantic_title->setReturnValue('getField', null);
        
        $this->semantic_titles[$tracker->getId()] = $semantic_title;
        
        return $this;
    }
    
    public function build() {
        $factory = new MockTracker_Semantic_TitleFactory();
        
        foreach($this->semantic_titles as $tracker => $semantic_title) {
            $factory->setReturnValue('getByTracker', $semantic_title, array($tracker));
        }
        
        return $factory;
    }
}

function aMockArtifact()             { return new MockArtifactBuilder(); }
function aMockArtifactFactory()      { return new MockArtifactFactoryBuilder(); }
function aMockSemanticTitleFactory() { return new MockSemanticTitleFactoryBuilder(); }

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticTitleTest extends TuleapTestCase {
    public function itReturnsAnEmptyTitle() {
        $artifact               = aMockArtifact()->build();
        $artifact_factory       = aMockArtifactFactory()->withArtifact($artifact)->build();
        $semantic_title_factory = aMockSemanticTitleFactory()->withNoSemanticForTracker($artifact->getTracker())->build();
        
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory);
        
        $artifact_id  = $artifact->getId();
        $changeset_id = 'whatever';
        
        $this->assertEqual($semantic_value_factory->getTitle($artifact_id, $changeset_id), '');
    }
}

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticStatusTest extends TuleapTestCase {
    public function itReturnsAnEmptyStatus() {}
}

?>

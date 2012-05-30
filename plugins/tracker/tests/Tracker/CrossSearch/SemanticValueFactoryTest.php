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
require_once dirname(__FILE__).'/../../builders/aMockArtifact.php';
require_once dirname(__FILE__).'/../../builders/aMockArtifactFactory.php';
require_once dirname(__FILE__).'/../../builders/aMockSemanticTitleFactory.php';
require_once dirname(__FILE__).'/../../builders/aMockSemanticStatusFactory.php';
require_once dirname(__FILE__).'/../../builders/aMockField.php';

abstract class Tracker_CrossSearch_SemanticValueFactory_NoSemanticTest extends TuleapTestCase {

    protected $artifact_id  = 123;

    protected $changeset_id = 'whatever';

    public function setUp() {
        $this->artifact = aMockArtifact()->withId($this->artifact_id)->build();
    }

    protected function buildSemanticValueFactory($semantic_title_factory, $semantic_status_factory) {
        $artifact_factory       = aMockArtifactFactory()->withArtifact($this->artifact)->build();
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory, $semantic_status_factory);
        return $semantic_value_factory;
    }
}

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticTitleTest extends Tracker_CrossSearch_SemanticValueFactory_NoSemanticTest {

    public function itReturnsAnEmptyTitle() {
        $semantic_value_factory = $this->GivenThereIsNoSemanticTitleDefined();
        $this->assertEqual($semantic_value_factory->getTitle($this->artifact_id, $this->changeset_id), '');
    }

    private function GivenThereIsNoSemanticTitleDefined() {
        $semantic_title_factory  = aMockSemanticTitleFactory()->withNoFieldForTracker($this->artifact->getTracker())->build();
        $semantic_status_factory = aMockSemanticStatusFactory()->build();
        return $this->buildSemanticValueFactory($semantic_title_factory, $semantic_status_factory);
    }
}

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticStatusTest extends Tracker_CrossSearch_SemanticValueFactory_NoSemanticTest {

    public function itReturnsAnEmptyStatus() {
        $semantic_value_factory = $this->GivenThereIsNoSemanticStatusDefined();
        $this->assertEqual($semantic_value_factory->getStatus($this->artifact_id, $this->changeset_id), '');
    }

    private function GivenThereIsNoSemanticStatusDefined() {
        $semantic_title_factory  = aMockSemanticTitleFactory()->build();
        $semantic_status_factory = aMockSemanticStatusFactory()->withNoFieldForTracker($this->artifact->getTracker())->build();
        return $this->buildSemanticValueFactory($semantic_title_factory, $semantic_status_factory);
    }
}

class Tracker_CrossSearch_SemanticValueFactory_WhenSemanticStatusIsSet_Test extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        
        $this->artifact_id  = 123;
        $this->changeset_id = 456;
        $this->value_id     = 789;
        
        $this->artifact                = aMockArtifact()->withId($this->artifact_id)->build();
        $this->tracker                 = $this->artifact->getTracker();
        $this->artifact_factory        = aMockArtifactFactory()->withArtifact($this->artifact)->build();
        $this->semantic_title_factory  = aMockSemanticTitleFactory()->build();
        $this->field                   = aMockField()->withValueForChangesetId($this->value_id, $this->changeset_id)->build();
        
        $this->setText('Open', array('plugin_tracker_crosssearch', 'semantic_status_open'));
        $this->setText('Closed', array('plugin_tracker_crosssearch', 'semantic_status_closed'));
    }
    
    public function itReturnsOpenWhenStatusMatches() {
        $this->semantic_status_factory = aMockSemanticStatusFactory()->withFieldForTracker($this->field, $this->tracker)
                                                                     ->withOpenValueForTracker($this->tracker, $this->value_id)->build();
        
        $this->assertSemanticValueFactoryReturnsStatus('Open');
    }
    
    public function itReturnsClosedWhenStatusMatches() {
        $this->semantic_status_factory = aMockSemanticStatusFactory()->withFieldForTracker($this->field, $this->tracker)
                                                                     ->withClosedValueForTracker($this->tracker, $this->value_id)->build();
        
        $this->assertSemanticValueFactoryReturnsStatus('Closed');
    }
    
    private function assertSemanticValueFactoryReturnsStatus($expected_status) {
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($this->artifact_factory,
                                                                                $this->semantic_title_factory,
                                                                                $this->semantic_status_factory);
        $status = $semantic_value_factory->getStatus($this->artifact_id,
                                                     $this->changeset_id);
        $this->assertEqual($status, $expected_status);
    }
}

?>

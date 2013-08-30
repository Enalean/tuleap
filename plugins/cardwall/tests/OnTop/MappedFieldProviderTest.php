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
require_once dirname(__FILE__) .'/../bootstrap.php';

class Cardwall_OnTop_MappedFieldProviderTest extends TuleapTestCase {
    
    public function itProvidesTheStatusFieldIfNoMapping() {
        $tracker  = mock('Tracker');
        $artifact = aMockArtifact()->withTracker($tracker)->build();
        
        $status_field = mock('Tracker_FormElement_Field_OpenList');
        $status_retriever = stub('Cardwall_FieldProviders_SemanticStatusFieldRetriever')->getField()->returns($status_field);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider(mock('Cardwall_OnTop_Config'), $status_retriever);
        
        $this->assertEqual($status_field, $provider->getField($tracker));
    }
    
    public function itProvidesTheMappedFieldIfThereIsAMapping() {
        $tracker  = aTracker()->build();
        $artifact = aMockArtifact()->withTracker($tracker)->build();
        
        $mapped_field = mock('Tracker_FormElement_Field_OpenList');
        $status_retriever = mock('Cardwall_FieldProviders_SemanticStatusFieldRetriever');
        $mapping = stub('Cardwall_OnTop_Config_TrackerMapping')->getField()->returns($mapped_field);
        $config = stub('Cardwall_OnTop_Config')->getMappingFor($tracker)->returns($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);
        
        $this->assertEqual($mapped_field, $provider->getField($tracker));
    }
    
    public function itReturnsNullIfThereIsACustomMappingButNoFieldChoosenYet() {
        $tracker  = aTracker()->build();
        $artifact = aMockArtifact()->withTracker($tracker)->build();
        
        $status_field = mock('Tracker_FormElement_Field_OpenList');
        $status_retriever = stub('Cardwall_FieldProviders_SemanticStatusFieldRetriever')->getField()->returns($status_field);
        $mapping = stub('Cardwall_OnTop_Config_TrackerMapping')->getField()->returns(null);
        $config = stub('Cardwall_OnTop_Config')->getMappingFor($tracker)->returns($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);
        
        $this->assertEqual(null, $provider->getField($tracker));
    }
    
}


?>

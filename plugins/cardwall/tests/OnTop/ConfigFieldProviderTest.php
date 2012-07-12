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

require_once CARDWALL_BASE_DIR .'/FieldProviders/IProvideFieldGivenAnArtifact.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerMapping.class.php';
class Cardwall_OnTop_ConfigFieldProvider implements Cardwall_FieldProviders_IProvideFieldGivenAnArtifact {

    /**
     * @var Cardwall_FieldProviders_SemanticStatusFieldRetriever 
     */
    private $semantic_status_provider;
    
    /**
     * @var Cardwall_OnTop_Config
     */
    private $config;
    
    public function __construct(Cardwall_OnTop_Config                         $config, 
                         Cardwall_FieldProviders_SemanticStatusFieldRetriever $semantic_status_provider) {
        
        $this->semantic_status_provider = $semantic_status_provider;
        $this->config                   = $config;
    }

    public function getField(Tracker_Artifact $artifact) {
        $mapping = $this->config->getMappingFor($artifact->getTracker());
        if ($mapping) {
            return $mapping->getField();
        }
        return $this->semantic_status_provider->getField($artifact);
    }

}

class Cardwall_OnTop_ConfigFieldProviderTest extends TuleapTestCase {
    
    public function itProvidesTheStatusFieldIfNoMapping() {
        $artifact = aMockArtifact()->build();
        
        $status_field = mock('Tracker_FormElement_Field_OpenList');
        $status_retriever = stub('Cardwall_FieldProviders_SemanticStatusFieldRetriever')->getField()->returns($status_field);
        $provider = new Cardwall_OnTop_ConfigFieldProvider(mock('Cardwall_OnTop_Config'), $status_retriever);
        
        $this->assertEqual($status_field, $provider->getField($artifact));
    }
    
    public function itProvidesTheMappedFieldIfThereIsAMapping() {
        $tracker  = aTracker()->build();
        $artifact = aMockArtifact()->withTracker($tracker)->build();
        
        $mapped_field = mock('Tracker_FormElement_Field_OpenList');
        $status_retriever = mock('Cardwall_FieldProviders_SemanticStatusFieldRetriever');
        $mapping = stub('Cardwall_OnTop_Config_TrackerMapping')->getField()->returns($mapped_field);
        $config = stub('Cardwall_OnTop_Config')->getMappingFor($tracker)->returns($mapping);
        $provider = new Cardwall_OnTop_ConfigFieldProvider($config, $status_retriever);
        
        $this->assertEqual($mapped_field, $provider->getField($artifact));
    }
    
    public function itReturnsNullIfThereIsACustomMappingButNoFieldChoosenYet() {
        $tracker  = aTracker()->build();
        $artifact = aMockArtifact()->withTracker($tracker)->build();
        
        $status_field = mock('Tracker_FormElement_Field_OpenList');
        $status_retriever = stub('Cardwall_FieldProviders_SemanticStatusFieldRetriever')->getField()->returns($status_field);
        $mapping = stub('Cardwall_OnTop_Config_TrackerMapping')->getField()->returns(null);
        $config = stub('Cardwall_OnTop_Config')->getMappingFor($tracker)->returns($mapping);
        $provider = new Cardwall_OnTop_ConfigFieldProvider($config, $status_retriever);
        
        $this->assertEqual(null, $provider->getField($artifact));
    }
    
}


?>

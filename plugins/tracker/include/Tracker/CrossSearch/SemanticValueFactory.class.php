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

/**
 * This factory provides a simple way to retrieve semantic values (e.g. title,
 * status...) given some artifact and changeset ids.
 * 
 * This didn't seem to be the point of the various existing factories in
 * Tracker/Semantic, that's why this class was written.
 * 
 * It was placed in the Tracker/CrossSearch namespace because it's the only
 * place where it is used for now.
 * 
 * Grouping the title and status retrieval in the same class is probably not the
 * best design, but it was the easier to start with.
 */

require_once dirname(__FILE__).'/../Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../Semantic/Tracker_Semantic_TitleFactory.class.php';
require_once dirname(__FILE__).'/../Semantic/Tracker_Semantic_StatusFactory.class.php';

class Tracker_CrossSearch_SemanticValueFactory {
    
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    
    /**
     * @var Tracker_Semantic_TitleFactory
     */
    private $semantic_title_factory;
    
    /**
     * @var Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;
    
    public function __construct(Tracker_ArtifactFactory        $artifact_factory,
                                Tracker_Semantic_TitleFactory  $semantic_title_factory,
                                Tracker_Semantic_StatusFactory $semantic_status_factory) {
        
        $this->artifact_factory        = $artifact_factory;
        $this->semantic_title_factory  = $semantic_title_factory;
        $this->semantic_status_factory = $semantic_status_factory;
    }
    
    public function getTitle($artifact_id, $changeset_id) {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        $tracker  = $artifact->getTracker();
        $semantic = $this->semantic_title_factory->getByTracker($tracker);
        $field    = $semantic->getField();
        
        if ($field == null) { return ''; }
        
        $changeset = $artifact->getChangeset($changeset_id);
        $value     = $changeset->getValue($field);
        
        return $value->getText();
    }
    
    public function getStatus($artifact_id, $changeset_id) {
        $artifact         = $this->artifact_factory->getArtifactById($artifact_id);
        $tracker          = $artifact->getTracker();
        $semantic         = $this->semantic_status_factory->getByTracker($tracker);
        $field            = $semantic->getField();
        
        if ($field == null) { return ''; }
        
        $value = $field->fetchChangesetValue($artifact_id, $changeset_id, null);
        
        return $value;
    }
}
?>

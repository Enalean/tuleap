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
                                Tracker_Semantic_StatusFactory $semantic_status_factory,
                                TrackerFactory                 $tracker_factory) {
        
        $this->artifact_factory        = $artifact_factory;
        $this->semantic_title_factory  = $semantic_title_factory;
        $this->semantic_status_factory = $semantic_status_factory;
        $this->tracker_factory         = $tracker_factory;
    }
    
    public function getTitle($artifact_id, $changeset_id) {
        $artifact = $this->getArtifact($artifact_id);
        $field    = $this->getField($artifact, $this->semantic_title_factory);
        
        if ($field == null || !$field->userCanRead()) { 
            return ''; 
        }
        
        $changeset = $artifact->getChangeset($changeset_id);
        $value     = $changeset->getValue($field);
        
        return $value->getText();
    }
    
    public function getStatus($artifact_id, $changeset_id) {
        $artifact = $this->getArtifact($artifact_id);
        $field    = $this->getField($artifact, $this->semantic_status_factory);
        
        if ($field == null) { 
            return ''; 
        }
        
        $value  = $this->getValue($field, $changeset_id);
        $status = $this->isOpenValue($value, $artifact) ? $this->getOpenLabel() : $this->getClosedLabel();
        
        return $status;
    }
    
    private function getValue($field, $changeset_id) {
        $values = $field->getBind()->getChangesetValues($changeset_id);
        $value  = empty($values) ? null : $values[0]['id'];
        
        return $value;
    }
    
    private function isOpenValue($value, $artifact) {
        $semantic    = $this->semantic_status_factory->getByTracker($artifact->getTracker());
        $open_values = $semantic->getOpenValues();
        
        return in_array($value, $open_values);
    }
    
    private function getOpenLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_status_open');
    }
    
    private function getClosedLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_status_closed');
    }
    
    /**
     * @param Tracker_Artifact $artifact
     * @param mixed $semantic_factory
     * @return mixed
     */
    private function getField(Tracker_Artifact $artifact, $semantic_factory) {
        return $semantic_factory->getByTracker($artifact->getTracker())->getField();
    }
    
    /**
     * @param int $artifact_id
     * @return Tracker_Artifact
     */
    private function getArtifact($artifact_id) {
        return $this->artifact_factory->getArtifactById($artifact_id);
    }
    
    public function allSemanticFieldsAreReadable(User $user, Project $project, Tracker_Semantic_IRetrieveSemantic $factory) {
        $trackers = $this->tracker_factory->getTrackersByGroupId($project->getId());
        foreach ($trackers as $tracker) {
            $field = $factory->getByTracker($tracker)->getField();
            if ($field && ! $field->userCanRead($user)) {
                return false;
            }
        }
        return true;
    }
    
    public function allTitlesAreReadable(User $user, Project $project) {
        return $this->allSemanticFieldsAreReadable($user, $project, $this->semantic_title_factory);
    }
    
    public function allStatusesAreReadable(User $user, Project $project) {
        return $this->allSemanticFieldsAreReadable($user, $project, $this->semantic_status_factory);
    }
}
?>

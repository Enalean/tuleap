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
 * The criteria for a cross-tracker search.
 * Includes both semantic field criteria (e.g. title, status) and shared field
 * ones.
 */
class Tracker_CrossSearch_Criteria {
    /**
     * @var array of array
     */
    private $shared_fields_criteria;
    
    /**
     * @var array of string
     */
    private $semantic_criteria;

    private $artifact_ids;
    
    /**
     * @param array of array $shared_fields_criteria
     * @param array of string $semantic_criteria
     * @param $artifact_ids array(tracker_id_1 => array(artifact_id_1, artifact_id_2), tracker_id_2 => array(artifact_id_3))
     */
    public function __construct($shared_fields_criteria=array(), $semantic_criteria = null, $artifact_ids = null) {
        $this->shared_fields_criteria = $shared_fields_criteria;
        $this->semantic_criteria      = $semantic_criteria ? $semantic_criteria : array('title' => '', 'status' => '');
        $this->artifact_ids           = $artifact_ids;
    }
    
    public function getSharedFields() {
        return $this->shared_fields_criteria;
    }
    
    public function getSemanticCriteria() {
        return $this->semantic_criteria;
    }
    
    public function getTitle() {
        return $this->semantic_criteria['title'];
    }
    
    public function getStatus() {
        return $this->semantic_criteria['status'];
    }

    /**
     * @return the flattened list of artifact_ids
     */
    public function listArtifactIds() {
        if (!$this->artifact_ids) {
            return array();
        }
        
        $id_list = array();
        foreach ($this->artifact_ids as $artifact_ids) {
            $id_list = array_merge($id_list, $artifact_ids);
        }
        
        return $id_list;
    }    
}
?>

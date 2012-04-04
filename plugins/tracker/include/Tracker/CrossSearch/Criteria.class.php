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

    /**
     * @param array of array $shared_fields_criteria
     * @param array of string $semantic_criteria
     */
    public function __construct($shared_fields_criteria = array(), $semantic_criteria = null) {
        $this->shared_fields_criteria = $shared_fields_criteria;
        $this->semantic_criteria      = $semantic_criteria ? $semantic_criteria : array('title' => '', 'status' => 'open');
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
}

?>

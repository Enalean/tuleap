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

class Tracker_CrossSearch_ArtifactReportField implements Tracker_Report_Field {
    
    protected $artifact_ids;
    
    /**
     * @var Tracker
     */
    protected $tracker;
    
    public function __construct(Tracker $tracker, $artifact_ids) {
        $this->artifact_ids = $artifact_ids;
        $this->tracker      = $tracker;
    }
    
    
    public function getId() {
        
    }
    
    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
    }
    
    public function isUsed() {
    }
    
    public function getLabel() {
        return $this->tracker->getName();
    }
        
    /**
    * Display the field as a Changeset value.
    * Used in report table
    * @param int $artifact_id the corresponding artifact id
    * @param int $changeset_id the corresponding changeset
    * @param mixed $value the value of the field
    * @return string
    */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        
        
    }
}
?>

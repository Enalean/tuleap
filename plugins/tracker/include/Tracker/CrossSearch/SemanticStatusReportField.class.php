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
 * A report field to render the searched/current status of an artifact (as
 * defined in the semantic admininistration UI).
 */
class Tracker_CrossSearch_SemanticStatusReportField implements Tracker_Report_Field {

    const STATUS_ANY    = 'any';
    const STATUS_OPEN   = 'open';
    const STATUS_CLOSED = 'closed';
    
    private function getAllStatus() {
        return array(self::STATUS_ANY, self::STATUS_OPEN, self::STATUS_CLOSED);
    }
    
    /**
     * @var string
     */
    private $id = 'status';
    
    /**
     * @var string
     */
    private $searched_status;
    
    /**
     * @var Tracker_CrossSearch_SemanticValueFactory
     */
    private $semantic_value_factory;
    
    public function __construct($searched_status, Tracker_CrossSearch_SemanticValueFactory $semantic_value_factory) {
        $this->searched_status        = $searched_status;
        $this->semantic_value_factory = $semantic_value_factory;
    }
    
    public function isUsed() {
        return true;
    }
    
    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
        $html  = '';
        $html .= '<label>'. $this->getLabel() .'</label>
                  <br/>
                  <select name="semantic_criteria[status]">';
        foreach($this->getAllStatus() as $status) {
            $html .= '<option value="'. $status .'" '. $this->getSelected($status) .'>'. $this->getValueLabel($status) .'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public function fetchCriteriaWithoutExpandFunctionnality(Tracker_Report_Criteria $criteria) {
        return $this->fetchCriteria($criteria);
    }

    private function getSelected($status) {
        return $this->searched_status === $status ? 'selected="selected"' : '';
    }
    
    private function getValueLabel($status) {
        $semantic_status_value_key = "semantic_status_$status";
        return $GLOBALS['Language']->getText('plugin_tracker_crosssearch', $semantic_status_value_key);
    }
    
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_status_label');
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        return $this->semantic_value_factory->getStatus($artifact_id, $changeset_id);
    }
    
}

?>

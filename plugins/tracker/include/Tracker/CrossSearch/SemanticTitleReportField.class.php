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
 * A report field to render the searched/current title of an artifact (as
 * defined in the semantic admininistration UI).
 */
class Tracker_CrossSearch_SemanticTitleReportField implements Tracker_Report_Field {

    /**
     * @var string
     */
    private $id = 'title';
    
    /**
     * @var string
     */
    private $searched_title;
    
    /**
     * @var Tracker_CrossSearch_SemanticValueFactory
     */
    private $semantic_value_factory;
    
    public function __construct($searched_title, Tracker_CrossSearch_SemanticValueFactory $semantic_value_factory) {
        $this->searched_title         = $searched_title;
        $this->semantic_value_factory = $semantic_value_factory;
    }
    
    public function isUsed() {
        return true;
    }
    
    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
        $html  = '';
        $html .= '<label for="tracker_report_criteria_semantic_title">'. $this->getLabel().'</label>';
        $html .= '<br />';
        $html .= '<input type="text" name="semantic_criteria[title]" id="tracker_report_criteria_semantic_title" value="'.$this->searched_title.'" />';
        
        return $html;
    }
    
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_title_label');
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        return $this->semantic_value_factory->getTitle($artifact_id, $changeset_id);
    }
}

?>

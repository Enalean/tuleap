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

require_once dirname(__FILE__).'/../Tracker_Report_Field.class.php';
require_once dirname(__FILE__).'/../Report/Tracker_Report_Criteria.class.php';

class Tracker_CrossSearch_SemanticTitleReportField implements Tracker_Report_Field {

    /**
     * @var string
     */
    private $id = 'title';
    
    /**
     * @var string
     */
    private $title;
    
    public function __construct($title) {
        $this->title = $title;
    }
    
    public function isUsed() {
        return true;
    }
    
    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
        $html  = '';
        $html .= '<label for="tracker_report_criteria_semantic_title" title="#">'. $this->getLabel().'</label>';
        $html .= '<br />';
        $html .= '<input type="text" name="semantic_criteria[title]" id="tracker_report_criteria_semantic_title" value="'.$this->title.'" />';
        
        return $html;
    }
    
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_title_label');
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $artifact         = $artifact_factory->getArtifactById($artifact_id);
        $changeset        = $artifact->getChangeset($changeset_id);
        $tracker          = $artifact->getTracker();
        $semantic         = Tracker_Semantic_Title::load($tracker);
        $field            = $semantic->getField();
        $value            = $changeset->getValue($field);
        
        return $value->getText();
    }
    
}

?>

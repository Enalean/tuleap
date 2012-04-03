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
require_once 'Criteria.class.php';

class Tracker_CrossSearch_SemanticStatusReportField implements Tracker_Report_Field {

    const STATUS_OPEN   = "Open";
    const STATUS_CLOSED = "Closed";
    const STATUS_ANY    = "Any";
    
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
        $selected        = 'selected="selected"';
        $not_selected    = '';
        $selectionOpen   = $this->searched_status === self::STATUS_OPEN   ? $selected : $not_selected;
        $selectionClosed = $this->searched_status === self::STATUS_CLOSED ? $selected : $not_selected;
        $selectionAny    = $this->searched_status === self::STATUS_ANY    ? $selected : $not_selected;
        
        $label = $this->getLabel();
        return '
            <label>'.$label.'</label>
            <br/>
            <select name="semantic_criteria[status]">
                <option value="'.self::STATUS_ANY.'"    '.$selectionAny.'>'   . $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_status_any')    . '</option>
                <option value="'.self::STATUS_OPEN.'"   '.$selectionOpen.'>'  . $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_status_open')   . '</option>
                <option value="'.self::STATUS_CLOSED.'" '.$selectionClosed.'>'. $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'semantic_status_closed') . '</option>
            </select>
        ';
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

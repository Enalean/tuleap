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

    const STATUS_OPEN = "Open";

    /**
     * @var string
     */
    private $id = 'status';
    
    private $status;
    
    public function __construct($status) {
        $this->status                = $status;
    }
    
    public function isUsed() {
        return true;
    }
    
    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
        $selected = 'selected="selected"';
        $not_selected = '';
        $selectionOpen   = $this->status === 'Open'   ? $selected : $not_selected;
        $selectionClosed = $this->status === 'Closed' ? $selected : $not_selected;
        $selectionAny    = $this->status === 'Any'    ? $selected : $not_selected;
        
        $label = $this->getLabel();
        return <<<HTML
            <label>$label</label>
            <select name="semantic_criteria[status]">
                <option value="Any"    $selectionAny>Any</option>
                <option value="Open"   $selectionOpen>Open</option>
                <option value="Closed" $selectionClosed>Closed</option>
            </select>
HTML;
    }
    
    public function getLabel() {
        return 'Status';
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        
        $tracker         = $artifact_factory->getArtifactById($artifact_id)->getTracker();
        $value           = Tracker_Semantic_Status::load($tracker)->getField()->fetchChangesetValue($artifact_id, $changeset_id, null);
        
        return $value;
    }
    
}

?>

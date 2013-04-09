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
    
    protected $artifacts;
    protected $id = 'artifact_of_tracker';
    /**
     * @var Tracker
     */
    protected $tracker;
    
    ///TODO: change $tracker to a name more explicit like 'stuff'
    public function __construct(Tracker $tracker, array $artifacts) {
        $this->artifacts    = $artifacts;
        $this->tracker      = $tracker;
    }
    
    
    public function getId() {
        return $this->id.'['.$this->tracker->getId().']';
    }
    
    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
        $trackerId         = $this->tracker->getId();
        $html              = '';
        $html             .= '<label for="'.$this->id.'_'.$trackerId.'" title="#'.$trackerId.'">'.$this->tracker->getName().'</label>';
        $html             .= <<<MARKUP
        <div class="tracker_report_criteria">
        <input type="hidden" name="artifact_criteria[$trackerId]">
        <select id="tracker_report_criteria_adv_$trackerId" multiple="multiple" size="7" name="artifact_criteria[$trackerId][]">
MARKUP;
        
        $anySelected  = ' selected="selected"';
        $options      = '';
//         <option value="100">None</option>
        foreach ($this->artifacts as $artifact) {
            $artifactId = $artifact->getId();
            if ($artifact->isSelected) {
                $anySelected = '';
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $options .= '<option'.$selected.' value="'.$artifactId.'">'.$artifact->getTitle().'</option>';
        }
        $html        .= '<option'.$anySelected.'  value="">Any</option>';
        $html        .= $options;
        
        $html.= <<<MARKUP
        </select>
        </input>
        </div>       
MARKUP;
        
        /*
        <option style="" value="571">New</option>

         */
        return $html;
    }

    public function fetchCriteriaWithoutExpandFunctionnality(Tracker_Report_Criteria $criteria) {
        return $this->fetchCriteria($criteria);
    }
    
    public function isUsed() {
        return true;
    }
    
    public function getLabel() {
        return $this->tracker->getName();
    }

    public function getTracker() {
        return $this->tracker;
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
    
    /**
     * Return an index name based on artifact link field id
     * 
     * @param Tracker_FormElementFactory $form_element_factory
     * 
     * @return String
     */
    public function getArtifactLinkFieldName(Tracker_FormElementFactory $form_element_factory) {
        $fields = $form_element_factory->getUsedArtifactLinkFields($this->getTracker());
        if (empty($fields)) return;
        return 'art_link_' . $fields[0]->getId();
    }
}

?>

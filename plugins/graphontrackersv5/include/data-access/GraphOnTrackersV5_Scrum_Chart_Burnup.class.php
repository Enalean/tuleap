<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


require_once('GraphOnTrackersV5_Scrum_Burnup_Engine.class.php');
require_once('GraphOnTrackersV5_Scrum_Burnup_DataBuilder.class.php');
/**
 * Base class to provide a Scrum Burnup Chart
 */
class GraphOnTrackersV5_Scrum_Chart_Burnup extends GraphOnTrackersV5_Chart {
    
    /**
     * The date (timestamp) the sprint start
     */
    protected $start_date;
    public function getStartDate() { return $this->start_date; }
    public function setStartDate($start_date) { return $this->start_date = $start_date; }
    
    /**
     * The duration of the sprint
     */
    protected $duration;
    public function getDuration() { return $this->duration; }
    public function setDuration($duration) { return $this->duration = $duration; }
    
    /**
     * The remaining effort field
     */
    protected $remaining_field;
    public function getRemainingField() { return $this->remaining_field; }
    public function setRemainingField($remaining_field) { return $this->remaining_field = $remaining_field; }
    
    /**
     * The done field
     */
    protected $done_field;
    public function getDoneField() { return $this->done_field; }
    public function setDoneField($done_field) { return $this->done_field = $done_field; }
    
    /**
     * class constructor
     * Use parent one...
     */    

    /**
     * Create from DB
     */
    public function loadFromDb() {
        $sql = "SELECT * FROM plugin_graphontrackersv5_scrum_burnup WHERE id = ". db_ei($id);
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        $this->remaining_field = $arr['remaining_field_id'];
        $this->done_field      = $arr['done_field_id'];
        $this->start_date      = $arr['start_date'];
        $this->duration        = $arr['duration'];
    }

    public function loadFromSession() { // TODO: not implemented...
        $this->loadFromDb();
    }

    /**
     * Create an instance of the chart
     * @return GraphOnTrackersV5_Chart
     */
    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height) {
        $sql = sprintf("INSERT INTO plugin_graphontrackersv5_scrum_burnup
                       (id,remaining_field_id,done_field_id,start_date,duration)
                       VALUES(%d,0,0,'". strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME'])) ."','30')",
                       db_ei($id));
        //TODO smart default field selection
        $res = db_query($sql);
        return db_affected_rows($res) ? new GraphOnTrackersV5_Scrum_Chart_Burnup($graphic_report, $id, $rank, $title, $description, $width, $height) : null;
    }
    
    /**
     * Return the specific properties as a row
     * array('prop1' => 'value', 'prop2' => 'value', ...)
     * @return array
     */
    public function getSpecificRow() {
        return array(
            'remaining_field' => $this->getRemainingField(),
            'done_field'      => $this->getDoneField(),
            'start_date'      => $this->getStartDate(), 
            'duration'        => $this->getDuration(),
        );
    }
    
    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    public function getChartType() {
        return "graphontrackersv5_scrum_burnup";
    }
    
    /**
     * Delete the chart from its report
     */
    public function delete() {
        $sql = "DELETE FROM plugin_graphontrackersv5_scrum_burnup WHERE id = ". db_ei($this->id);
        $res = db_query($sql);
    }
    
    
    /**
     * @return GraphOnTracker_Engine The engine associated to the concrete chart
     */
    protected function getEngine() {
        return new GraphOnTrackersV5_Scrum_Burnup_Engine();
    }
    
    /**
     * @return ChartDataBuilder The data builder associated to the concrete chart
     */
    protected function getChartDataBuilder($artifacts) {
        return new GraphOnTrackersV5_Scrum_Burnup_DataBuilder($this,$artifacts);
    }
    
    /**
     * Allow update of the specific properties of the concrete chart
     * @return boolean true if the update is successful
     */
    protected function updateSpecificProperties($row) {
        $db_update_needed = false;
        foreach(array('remaining_field', 'done_field', 'start_date', 'duration') as $prop) {
            if (isset($row[$prop]) && $this->$prop != $row[$prop]) {
                if ($prop == 'start_date' && strtotime($row[$prop])) {
                    $this->$prop = strtotime($row[$prop]);
                } else {
                    $this->$prop = $row[$prop];
                }
                $db_update_needed = true;
            }
        }
        if ($db_update_needed) {
            $sql = sprintf("UPDATE plugin_graphontrackersv5_scrum_burnup SET
                          remaining_field_id = %s,
                          done_field_id = %s,
                          start_date = '%s',
                          duration = '%s'
                       WHERE id = %d",
                       db_ei($this->remaining_field),  
                       db_ei($this->done_field),   
                       db_es($this->start_date),
                       db_es($this->duration),
                       db_ei($this->id));
            $res = db_query($sql);
            return db_affected_rows($res);
        }
        return false;
    }
    
    /**
     * User as permission to visualize the chart
     */
    public function userCanVisualize() {
     return true;
    }
    
    /**
     * @return array of HTML_Element for properties
     */
    public function getProperties() {
        return array_merge(parent::getProperties(),
            array(
                new HTML_Element_Selectbox_TrackerFields_Int_TextFields($this->getTracker(), 'Remaining effort field', 'chart[remaining_field]', $this->getRemainingField()),
                new HTML_Element_Selectbox_TrackerFields_Int_TextFields($this->getTracker(), 'Done field', 'chart[done_field]', $this->getDoneField()),
                new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackersv5_scrum','burnup_property_start_date'), 'chart[start_date]', $this->getStartDate()),
                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_scrum','burnup_property_duration'), 'chart[duration]', $this->getDuration(), 4)
        ));
    }

    
    /**
     * Duplicate the chart
     */
    public function duplicate($from_chart, $field_mapping) {
        $sql = "INSERT INTO plugin_graphontrackersv5_scrum_burnup (id, remaining_field_id, done_field_id, start_date, duration)
                SELECT ". db_ei($this->id) .", remaining_field_id, done_field_id, start_date, duration
                FROM plugin_graphontrackersv5_scrum_burnup
                WHERE id = ". db_ei($from_chart->id);
        $res = db_query($sql);
        //TODO field mapping
    }
    
    /**
     * Sets the specific properties of the concrete chart from XML
     * 
     * @param SimpleXMLElement $xml characterising the chart
     * @param array $formsMapping associating xml IDs to real fields
     */
    public function setSpecificPropertiesFromXML($xml, $formsMapping) {
        if ($xml['start_date']) {
            $this->setStartDate((int)$propAtt['start_date']);
        }
        if ($xml['duration']) {
            $this->setDuration((int)$propAtt['duration']);
        }
        if (isset($formsMapping[(string)$xml['remaining_field']])) {
            $this->setRemainingField($formsMapping[(string)$xml['remaining_field']]);
        }
        if (isset($formsMapping[(string)$xml['done_field']])) {
            $this->setDoneField($formsMapping[(string)$xml['done_field']]);
        }
    }
    
    /**
     * Creates an array of specific properties of this chart
     * 
     * @return array containing the properties
     */
    public function arrayOfSpecificProperties() {
         return array('remaining_field' => $this->getRemainingField()->id,
                      'done_field' => $this->getDoneField()->id,
                      'start_date' => $this->getStartDate(),
                      'duration' => $this->getDuration());  
    }
    
    public function exportToXml(SimpleXMLElement $root, $formsMapping) {
        parent::exportToXML($root, $formsMapping);
        if ($this->start_date) {
            $root->addAttribute('start_date', $this->start_date);
        }
        if ($this->duration) {
            $root->addAttribute('duration', $this->duration);
        }
        if ($this->remaining_field) {
            $root->addAttribute('remaining_field', array_search($this->remaining_field, $formsMapping));
        }
        if ($this->done_field) {
            $root->addAttribute('done_field', array_search($this->done_field, $formsMapping));
        }
    }
    
    protected function getDao() {
        //return new GraphOnTrackers_Chart_BarDao();
    }

}
?>

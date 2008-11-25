<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('GraphOnTrackers_Scrum_Burndown_Engine.class.php');
require_once('GraphOnTrackers_Scrum_Burndown_DataBuilder.class.php');
/**
 * Base class to provide a Scrum Burndown Chart
 */
class GraphOnTrackers_Scrum_Chart_Burndown extends GraphOnTrackers_Chart {
    
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
     * class constructor
     *
     */    
    function __construct($graphic_report, $id, $rank, $title, $description, $width, $height) {
        parent::__construct($graphic_report, $id, $rank, $title, $description, $width, $height);
        $sql = "SELECT * FROM plugin_graphontrackers_scrum_burndown WHERE id = ". db_ei($id);
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        $this->start_date = $arr['start_date'];
        $this->duration   = $arr['duration'];
    }
    
    /**
     * Create an instance of the chart
     * @return GraphOnTrackers_Chart
     */
    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height) {
        $sql = sprintf("INSERT INTO plugin_graphontrackers_scrum_burndown
                       (id,start_date,duration)
                       VALUES(%d,'". strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME'])) ."','30')",
                       db_ei($id));

        $res = db_query($sql);
        return db_affected_rows($res) ? new GraphOnTrackers_Scrum_Chart_Burndown($graphic_report, $id, $rank, $title, $description, $width, $height) : null;
    }
    
    /**
     * Return the specific properties as a row
     * array('prop1' => 'value', 'prop2' => 'value', ...)
     * @return array
     */
    public function getSpecificRow() {
        return array(
            'start_date' => $this->getStartDate(), 
            'duration'   => $this->getDuration(),
        );
    }
    
    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    public function getChartType() {
        return "graphontrackers_scrum_burndown";
    }
    
    /**
     * Delete the chart from its report
     */
    public function delete() {
        $sql = "DELETE FROM plugin_graphontrackers_scrum_burndown WHERE id = ". db_ei($this->id);
        $res = db_query($sql);
    }
    
    
    /**
     * @return GraphOnTracker_Engine The engine associated to the concrete chart
     */
    protected function getEngine() {
        return new GraphOnTrackers_Scrum_Burndown_Engine();
    }
    
    /**
     * @return ChartDataBuilder The data builder associated to the concrete chart
     */
    protected function getChartDataBuilder($artifacts) {
        return new GraphOnTrackers_Scrum_Burndown_DataBuilder($this,$artifacts);
    }
    
    /**
     * Allow update of the specific properties of the concrete chart
     * @return boolean true if the update is successful
     */
    protected function updateSpecificProperties($row) {
        $db_update_needed = false;
        foreach(array('start_date', 'duration') as $prop) {
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
            $sql = sprintf("UPDATE plugin_graphontrackers_scrum_burndown SET
                          start_date = '%s',
                          duration = '%s'
                       WHERE id = %d",
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
                new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackers_scrum','burndown_property_start_date'), 'chart[start_date]', $this->getStartDate()),
                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackers_scrum','burndown_property_duration'), 'chart[duration]', $this->getDuration(), 4)
        ));
    }


}
?>

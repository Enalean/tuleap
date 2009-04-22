<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('GraphOnTrackers_Chart_LineDataBuilder.class.php');
require_once('GraphOnTrackers_Line_Engine.class.php');

require_once('HTML_Element_Method_Selectbox.class.php');
require_once('HTML_Element_TrackerFields_State.class.php');
require_once('HTML_Element_TrackerFields_StateField.class.php');
require_once('common/html/HTML_Element_Selectbox_TrackerFields_Selectboxes.class.php');

class GraphOnTrackers_Line_Chart extends GraphOnTrackers_Chart {
    
    protected $field_base;
    protected $state_source;
    protected $state_target;
    protected $date_min;
    protected $date_max;
    protected $date_reference;
    protected $method;

    /**
     * class constructor
     *
     */ 
    function __construct($graphic_report, $id, $rank, $title, $description, $width, $height) {
        parent::__construct($graphic_report, $id, $rank, $title, $description, $width, $height);
        $sql = "SELECT * FROM plugin_graphontrackers_line_chart WHERE id = ". db_ei($id);
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        $this->field_base     = $arr['field_base'];
        $this->state_source   = $arr['state_source'];
        $this->state_target   = $arr['state_target'];
        $this->date_min       = $arr['date_min'];
        $this->date_max       = $arr['date_max'];
        $this->date_reference = $arr['date_reference'];
        $this->method         = $arr['method'];
    }
    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height) {
        $sql = sprintf("INSERT INTO plugin_graphontrackers_line_chart
                       (id,field_base,state_source,state_target,date_min,date_max,date_reference,method)
                       VALUES(%d,'status_id',1,3,UNIX_TIMESTAMP(TIMESTAMPADD(MONTH,-1,now())),UNIX_TIMESTAMP(now()),UNIX_TIMESTAMP(now()),'age')",
                       db_ei($id));
        $res = db_query($sql);
        return db_affected_rows($res) ? new GraphOnTrackers_Line_Chart($graphic_report, $id, $rank, $title, $description, $width, $height) : null;
    }
    
    public function getField_base() { return $this->field_base; }
    public function setField_base($field_base) { return $this->field_base = $field_base; }
    public function getHeight() { return $this->height; }
    public function setHeight($height) { return $this->height = $height; }
    public function getWidth() { return $this->width; }
    public function setWidth($width) { return $this->width = $width; }
    public function getState_source() { return $this->state_source; }
    public function setState_source($state_source) { return $this->state_source = $state_source; }
    public function getState_target() { return $this->state_target; }
    public function setState_target($state_target) { return $this->state_target = $state_target; }
    public function getDate_reference() { return $this->date_reference; }
    public function setDate_reference($date_reference) { return $this->date_reference = $date_reference; }
    public function getDate_min() { return $this->date_min; }
    public function setDate_min($date_min) { return $this->date_min = $date_min; }
    public function getDate_max() { return $this->date_max; }
    public function setDate_max($date_max) { return $this->date_max = $date_max; }
    public function getMethod() { return $this->method; }
    public function setMethod($method) { return $this->method = $method; }
    
    protected function getEngine() {
        return new GraphOnTrackers_Line_Engine();
    }
    protected function getChartDataBuilder($artifacts) {
        return new GraphOnTrackers_Chart_LineDataBuilder($this,$artifacts);
    }
    public function delete() {
        $sql = "DELTE FROM plugin_graphontrackers_line_chart WHERE id = ". db_ei($this->id);
        $res = db_query($sql);
    }
    
    public function getProperties() {
        return array_merge(parent::getProperties(),
            array(
                new HTML_Element_TrackerFields_StateField($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'line_field_base'), 'chart[field_base]', $this->getField_base(),
                    new HTML_Element_TrackerFields_State($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'state_source'),'chart[state_source]', $this->getState_source(),$this->getField_base()),
                    new HTML_Element_TrackerFields_State($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'state_target'),'chart[state_target]', $this->getState_target(),$this->getField_base(),true)
                ),
                new HTML_Element_Columns(
                    new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'date_reference'),'chart[date_reference]', $this->getDate_reference()),
                    new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'date_min'), 'chart[date_min]', $this->getDate_min()),
                    new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'date_max'), 'chart[date_max]', $this->getDate_max())
                ),
                new HTML_Element_Method_Selectbox($GLOBALS['Language']->getText('plugin_graphontrackers_line_property', 'method'), 'chart[method]', $this->getMethod()),
        ));
    }
    protected function updateSpecificProperties($row) {
        $db_update_needed = false;
        foreach(array('field_base', 'state_source', 'state_target', 'date_min', 'date_max', 'date_reference', 'method') as $prop) {
            if (isset($row[$prop]) && $this->$prop != $row[$prop]) {
                if (in_array($prop, array('date_min', 'date_max', 'date_reference'))) {
                    $this->$prop = strtotime($row[$prop]);
                } else {
                    $this->$prop = $row[$prop];
                }
                $db_update_needed = true;
            }
        }
        if ($db_update_needed) {
            $sql = sprintf("UPDATE plugin_graphontrackers_line_chart SET
                       field_base = '%s',
                       state_source = '%s',
                       state_target = '%s',
                       date_min = %d,
                       date_max = %d,
                       date_reference = %d,
                       method = '%s'
                       WHERE id = %d",
                       db_es($this->field_base),
                       db_es($this->state_source),
                       db_es($this->state_target),
                       db_ei($this->date_min),
                       db_ei($this->date_max),
                       db_ei($this->date_reference),
                       db_es($this->method),
                       db_ei($this->id));
            $res = db_query($sql);
            return db_affected_rows($res);
        }
        return false;
    }
    
    public function getHelp() {
        return trim(file_get_contents($GLOBALS['Language']->getContent('line_doc', null, 'graphontrackers_line', '.html')));
    }
    
    public function userCanVisualize(){
        $artifact_field_base=new ArtifactField();
        $artifact_field_base->fetchData($GLOBALS['ath']->getID(),$this->field_base);
        return $artifact_field_base->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid());
    }
    
    public function getChartType() {
        return 'line';
    }
    
    public function getSpecificRow() {
        return array(
            'field_base'     => $this->getField_base(), 
            'state_source'   => $this->getState_source(), 
            'state_target'   => $this->getState_target(), 
            'date_min'       => $this->getDate_min(), 
            'date_max'       => $this->getDate_max(), 
            'date_reference' => $this->getDate_reference(), 
            'method'         => $this->getMethod()
        );
    }
}
?>

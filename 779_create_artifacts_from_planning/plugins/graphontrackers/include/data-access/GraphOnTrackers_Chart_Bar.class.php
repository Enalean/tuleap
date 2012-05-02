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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('GraphOnTrackers_Chart.class.php');
require_once(dirname(__FILE__).'/../data-transformation/GraphOnTrackers_Chart_BarDataBuilder.class.php');
require_once(dirname(__FILE__).'/../graphic-library/GraphOnTrackers_Engine_Bar.class.php');
require_once('common/html/HTML_Element_Selectbox_TrackerFields_Selectboxes.class.php');
        
class GraphOnTrackers_Chart_Bar extends GraphOnTrackers_Chart {
    
    protected $field_base;
    protected $field_group;
    
    /**
     * class constructor
     *
     */    
    function __construct($graphic_report, $id, $rank, $title, $description, $width, $height) {
        parent::__construct($graphic_report, $id, $rank, $title, $description, $width, $height);
        $sql = "SELECT * FROM plugin_graphontrackers_bar_chart WHERE id = ". db_ei($id);
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        $this->field_base          = $arr['field_base'];
        $this->field_group         = $arr['field_group'];
    }
    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height) {
        $sql = sprintf("INSERT INTO plugin_graphontrackers_bar_chart
                       (id,field_base,field_group)
                       VALUES(%d,'status_id','')",
                       db_ei($id));

        $res = db_query($sql);
        return db_affected_rows($res) ? new GraphOnTrackers_Chart_Bar($graphic_report, $id, $rank, $title, $description, $width, $height) : null;
    }
    
    public function getField_base() { return $this->field_base; }
    public function setField_base($field_base) { return $this->field_base = $field_base; }
    public function getField_group() { return $this->field_group; }
    public function setField_group($field_group) { return $this->field_group = $field_group; }
    
    protected function getEngine() {
        return new GraphOnTrackers_Engine_Bar();
    }
    protected function getChartDataBuilder($artifacts) {
        return new GraphOnTrackers_Chart_BarDataBuilder($this,$artifacts);
    }
    public function delete() {
        $sql = "DELETE FROM plugin_graphontrackers_bar_chart WHERE id = ". db_ei($this->id);
        $res = db_query($sql);
    }
    public function getProperties() {
        return array_merge(parent::getProperties(),
            array(
                new HTML_Element_Selectbox_TrackerFields_Selectboxes($GLOBALS['Language']->getText('plugin_graphontrackers_bar_property','bar_field_base'), 'chart[field_base]', $this->getField_base(),false),
                
                new HTML_Element_Selectbox_TrackerFields_Selectboxes($GLOBALS['Language']->getText('plugin_graphontrackers_bar_property','bar_field_group'), 'chart[field_group]', $this->getField_group(), true)
        ));
    }
    protected function updateSpecificProperties($row) {
        $db_update_needed = false;
        foreach(array('field_base', 'field_group') as $prop) {
            if (isset($row[$prop]) && $this->$prop != $row[$prop]) {
                $this->$prop = $row[$prop];
                $db_update_needed = true;
            }
        }
        if ($db_update_needed) {
            $sql = sprintf("UPDATE plugin_graphontrackers_bar_chart SET
                          field_base = '%s',
                          field_group = '%s'
                       WHERE id = %d",
                       db_es($this->field_base),
                       db_es($this->field_group),
                       db_ei($this->id));
            $res = db_query($sql);
            return db_affected_rows($res);
        }
        return false;
    }
    
    function userCanVisualize(){
    	$artifact_field_base=new ArtifactField();
    	$artifact_field_base->fetchData($GLOBALS['ath']->getID(),$this->field_base);
    	if($this->field_group){
	    	$artifact_field_group=new ArtifactField();
	    	$artifact_field_group->fetchData($GLOBALS['ath']->getID(),$this->field_group);
    		if(!$artifact_field_group->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    		
    	if($artifact_field_base->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
	    	return true;
    	}else{return false;}
    }
    
    public function getChartType() {
        return 'bar';
    }
    
    public function getSpecificRow() {
        return array(
            'field_base'  => $this->getField_base(), 
            'field_group' => $this->getField_group(),
        );
    }
}
?>

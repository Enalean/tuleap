<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('DataBuilder.class.php');
require_once('ChartDataBuilder.class.php');

class GraphOnTrackers_Chart_GanttDataBuilder extends ChartDataBuilder {

    /**
    * function to get a date field value
    *  @param field_name : the date field_name
    *  @return Unix date : value date of field_name
    */

    function getDateValues($field_name) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(),$field_name);
        if (!$af->isStandardField()) {
            $sql = sprintf('SELECT afv.artifact_id as id,afv.valueDate as val
                            FROM artifact_field_value afv
                            JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name)
                          );

        } else {
            $sql = sprintf('SELECT a.artifact_id as id,a.'.db_es($field_name).' as val
                            FROM artifact a
                            WHERE a.group_artifact_id = %d
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid())
                          );
        }
        $res = db_query($sql);
        $r_date = array();
        //echo $sql;
        for ($i=0;$i<db_numrows($res);$i++) {
            $date[$i]          = db_fetch_array($res);
            $r_date[$i]['id']  = $date[$i]['id'];
            $r_date[$i]['val'] = $date[$i]['val'];
        }
        return $r_date;
    }

    function getIntValues($field_name) {
        $sql = sprintf(' SELECT artifact_id as id,afv.valueInt as val
                            FROM artifact_field_value afv
                            JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name));

        $res = db_query($sql);
        //echo $sql;
        $r_if = array();
        for ($i=0;$i<db_numrows($res);$i++) {
            $if[$i]          = db_fetch_array($res);
            $r_if[$i]['id']  = $if[$i]['id'];
            $r_if[$i]['val'] = $if[$i]['val'];
        }
        return $r_if;
    }

    function getFloatValues($field_name) {
        $sql = sprintf(' SELECT artifact_id as id,afv.valueFloat as val
                            FROM artifact_field_value afv
                            JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name));

        $res = db_query($sql);
        //echo $sql;
        $r_ff = array();
        for ($i=0;$i<db_numrows($res);$i++) {
            $ff[$i]          = db_fetch_array($res);
            $r_ff[$i]['id']  = $ff[$i]['id'];
            $r_ff[$i]['val'] = $ff[$i]['val'];
        }
        return $r_ff;
    }

    /**
    * function to get a SelectBox field value
    *  @param field_name : the SelectBox field_name
    *  @return String : value of the SelectBox field
    */

    function getSFValues($field_name) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(),$field_name);
        if ((!$af->isStandardField()) && (!$af->isUsername())) {
            $sql = sprintf('SELECT artifact_id as id,afvl.value as val
                            FROM artifact_field_value afv
                            JOIN artifact_field af
                            USING (field_id)
                            JOIN artifact_field_value_list afvl
                            USING (group_artifact_id,field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.valueInt = afvl.value_id
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name)
                          );
        } else if (($af->isStandardField()) && (!$af->isUsername())) {
            $sql = sprintf('SELECT artifact_id as id,afvl.value as val
                            FROM artifact a
                            JOIN artifact_field_value_list afvl
                            USING (group_artifact_id)
                            JOIN artifact_field af
                            USING (group_artifact_id,field_id)
                            WHERE a.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND a.%s = afvl.value_id
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name),
                            db_es($field_name)
                          );
                          //echo $sql;
        } else if ((!$af->isStandardField()) && ($af->isUsername())) {
            $sql = sprintf('SELECT artifact_id as id,u.realName as val
                            FROM artifact_field_value afv
                            JOIN artifact_field af USING (field_id)
                            JOIN user u
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.valueInt = u.user_id
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name)
                          );
        } else {
            $sql = sprintf('SELECT a.artifact_id as id,u.realName  as val
                            FROM artifact a,user u
                            WHERE a.group_artifact_id = %d
                            AND a.%s = u.user_id
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name)
                          );
        }
        $res = db_query($sql);
        //echo $sql;
        $r_sf = array();
        $arts = array();
        for ($i=0;$i<db_numrows($res);$i++) {
            $sf[$i]          = db_fetch_array($res);
            if ($sf[$i]['val'] == '0') {
                $sf[$i]['val'] = 'None';
            }
            $pos = array_search($sf[$i]['id'],$arts);
            if ($pos == false) {
                $arts[count($arts)] = $sf[$i]['id'];
                $r_sf[$i]['id']  = $sf[$i]['id'];
                $r_sf[$i]['val'] = $sf[$i]['val'];
            } else {
                $pos_id = $this->seekId($sf[$i]['id'],$r_sf);
                $r_sf[$pos_id]['val'] .= ' - '.$sf[$i]['val'];                
            }
        }
        return $r_sf;
    }

    /**
    * function to get a Text field value
    *  @param field_name : the Text field_name
    *  @return String : value of the Text field
    */

    function getTFValues($field_name) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(),$field_name);
        if (!$af->isStandardField()) {
            $sql = sprintf('SELECT artifact_id as id,afv.valueText as val
                            FROM artifact_field_value afv
                            JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name)
                          );
        } else {
            $sql = sprintf('SELECT artifact_id as id, %s as val
                            FROM artifact a
                            WHERE a.group_artifact_id = %d
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_es($field_name),
                            db_ei($this->chart->getGraphicReport()->getAtid())

                          );
        }
        $res = db_query($sql);
        //echo $sql;
        $r_sf = array();
        for ($i=0;$i<db_numrows($res);$i++) {
            $sf[$i]          = db_fetch_array($res);
            $r_sf[$i]['id']  = $sf[$i]['id'];
            $r_sf[$i]['val'] = $sf[$i]['val'];
        }
        return $r_sf;
    }

    /**
    * getter method to get start date property
    *
    *     @return Unix Date start_date : the ganttbar (activity) start date
    */

    function getStartDate() {
        return $this->getDateValues($this->chart->getField_start());
    }

    /**
    * getter method to get finish date property
    *
    *     @return Unix Date finish_date : the ganttbar (activity) finish date
    */

    function getFinishDate() {
        return $this->getDateValues($this->chart->getField_finish());
    }

    /**
    * getter method to get due date property
    *
    *     @return Unix Date due_date : the ganttbar (activity) due date
    */

    function getDueDate() {
        return $this->getDateValues($this->chart->getField_due());
    }

    /**
    * getter method to get progress property
    *
    *     @return float progress : the ganttbar (activity) progress <0..1>
    */

    function getProgress() {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(),$this->chart->getField_percentage());
        
		$value_array= $this->getIntValues($this->chart->getField_percentage());
		$clean_value_array=array();
		for($i=0;$i<count($value_array);$i++){       		
			$clean_value_array[$i]['id'] = $value_array[$i]['id'];
	    	if ($value_array[$i]['val']<0){
	    		$clean_value_array[$i]['val']=0;
	    	}else if($value_array[$i]['val']>100){
	    		$clean_value_array[$i]['val']=1;
	    	}else{
	    	    $clean_value_array[$i]['val']=$value_array[$i]['val']/100;
	    	}
		}
		return $clean_value_array;
  
    }

    /**
    * getter method to get righttext property
    *
    *     @return String : text diplayed in the right of ganttbar (activity)
    */

    function getRightText() {
        return $this->getSFValues($this->chart->getField_righttext());
    }

 


    function getHint() {
        $summary    = $this->getSummary();
        $progress = $this->getProgress();
        $returns = array();
        for ($i=0;$i<count($summary);$i++) {
            $returns[$i]['id']  = isset($summary[$i]['id'])?$summary[$i]['id']:"";
            $returns[$i]['val'] = isset($summary[$i]['val'])?$summary[$i]['val']:"";

			$progress_tooltip = $this->seekId($summary[$i]['id'],$progress);
         }
        return $returns;
    }

    /**
    * getter method to get summary property
    *
    *     @return String : text diplayed in the left of gantt chart
    */

    function getSummary() {
        return $this->getTFValues($this->chart->getSummary());
    }

    /**
    * getter method to get links property
    *
    *     @return String :  links to be used for each ganttbar (activity)
    */

    function getLinks() {
        $links = array();
        for ($i=0;$i<count($this->artifacts);$i++) {
            $links[$i]['id'] = $this->artifacts[$i];
            $links[$i]['val'] = "/tracker/?func=detail&aid=".$this->artifacts[$i]."&group_id=".$this->chart->getGraphicReport()->getGroupId()."&atid=".$this->chart->getGraphicReport()->getAtid();
        }
        return $links;
    }

    /**
     * build Gantt chart properties
     *
     * @param Bar_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $engine->start      = $this->getStartDate();
        $engine->due        = $this->getDueDate();
        $engine->finish     = $this->getFinishDate();
        $engine->progress   = $this->getProgress();
        $engine->right      = $this->getRightText();
        $engine->hint       = $this->getHint();
        $engine->links      = $this->getLinks();
        $engine->summary    = $this->getSummary();
        $engine->title      = $this->chart->getTitle();
        $engine->description= $this->chart->getDescription();
        $engine->scale      = $this->chart->getScale();
        $engine->asOfDate   = $this->chart->getAs_of_date();
        $this->buildData($engine);
    }

    /**
     * build bar chart data
     *
     * @param Gantt_Engine object
     * @return array data array
     */ 
    function buildData($engine) {
        for ($i=0;$i<count($this->artifacts);$i++) {
            $engine->data[$i]['id']       = $this->artifacts[$i];
            if (isset($engine->start[$this->seekId($this->artifacts[$i],$engine->start)])) {
                $engine->data[$i]['start'] = $engine->start[$this->seekId($this->artifacts[$i],$engine->start)]['val'];
            }
            // due is not mandatory => it can be not set
            if ($this->chart->getField_due() != "") {
                $engine->data[$i]['due'] = $engine->due[$this->seekId($this->artifacts[$i],$engine->due)]['val'];
            } else {
                $engine->data[$i]['due'] = "";
            }
            if (isset($engine->finish[$this->seekId($this->artifacts[$i],$engine->finish)])) {
                $engine->data[$i]['finish'] = $engine->finish[$this->seekId($this->artifacts[$i],$engine->finish)]['val'];
            }
            if (isset($engine->progress[$this->seekId($this->artifacts[$i],$engine->progress)])) {
                $engine->data[$i]['progress'] = $engine->progress[$this->seekId($this->artifacts[$i],$engine->progress)]['val'];
            }
            if (isset($engine->right[$this->seekId($this->artifacts[$i],$engine->right)])) {
                $engine->data[$i]['right'] = $engine->right[$this->seekId($this->artifacts[$i],$engine->right)]['val'];
            }
            if (isset($engine->hint[$this->seekId($this->artifacts[$i],$engine->hint)])) {
                $engine->data[$i]['hint'] = $engine->hint[$this->seekId($this->artifacts[$i],$engine->hint)]['val'];
            }
            if (isset($engine->summary[$this->seekId($this->artifacts[$i],$engine->summary)])) {
                $engine->data[$i]['summary'] = $engine->summary[$this->seekId($this->artifacts[$i],$engine->summary)]['val'];
            }
        }
        
        for($i=0;$i<count($engine->data);$i++) {
            $engine->data[$i]['links'] = "/tracker/?func=detail&aid=".$engine->data[$i]['id']."&group_id=".$this->chart->getGraphicReport()->getGroupId()."&atid=".$this->chart->getGraphicReport()->getAtid();
        }

        // format data

        for($i=0;$i<count($engine->data);$i++) {
            if (!isset($engine->data[$i]['summary'])) {
                $engine->data[$i]['summary'] = "";
            }
            if (!isset($engine->data[$i]['start'])) {
                $engine->data[$i]['start'] = 0;
            }
            if (!isset($engine->data[$i]['due'])) {
                $engine->data[$i]['due'] = 0;
            }
            if (!isset($engine->data[$i]['finish'])) {
                $engine->data[$i]['finish'] = 0;
            }
            if (!isset($engine->data[$i]['progress'])) {
                $engine->data[$i]['progress'] = 0;
            }
            if (!isset($engine->data[$i]['right'])) {
                $engine->data[$i]['right'] = "";
            }
            if (!isset($engine->data[$i]['hint'])) {
                $engine->data[$i]['hint'] = "";
            }
            if (!isset($engine->data[$i]['links'])) {
                $engine->data[$i]['links'] = "";
            }

        }
        //print_r($engine->data);
        return $engine->data;
    }

    /**
    * function to search ganttbar (activity) position in the data array
    *   @param id : artifact_id
    *   @param data : data array
    *     @return int position: position of the artifact
    */

    function seekId($id,$data) {
        $data_cp = array();
        for($i=0;$i<count($data);$i++) {
            $data_cp[$i] = $data[$i]['id'];
        }
        $pos = array_search($id,$data_cp);
        if ($pos === false) {
            return count($data_cp);
        } else {
            return $pos;
        }
    }

}
?>

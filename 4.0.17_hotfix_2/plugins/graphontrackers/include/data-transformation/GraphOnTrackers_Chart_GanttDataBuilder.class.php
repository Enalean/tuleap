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
require_once('DataBuilder.class.php');
require_once('ChartDataBuilder.class.php');

class GraphOnTrackers_Chart_GanttDataBuilder extends ChartDataBuilder {

    /**
     * function to get a date field value
     *  @param field_name : the date field_name
     *  @return Unix date : value date of field_name
     */
    function getDateValues(ArtifactField $af) {
        if (!$af->isStandardField()) {
            $sql = sprintf('SELECT afv.artifact_id as id,afv.valueDate as val
                            FROM artifact_field_value afv
                            INNER JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($af->field_name)
                          );

        } else {
            $sql = sprintf('SELECT a.artifact_id as id,a.'.db_es($af->field_name).' as val
                            FROM artifact a
                            WHERE a.group_artifact_id = %d
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid())
                          );
        }
        return db_query($sql);
    }

    /**
     * Return all artifact values of given integer field
     * 
     * @param $field_name
     * @return resource
     */
    function getIntValues($field_name) {
        $sql = sprintf(' SELECT artifact_id as id,afv.valueInt as val
                            FROM artifact_field_value afv
                            INNER JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($field_name));
        return db_query($sql);
    }

    /**
     * function to get a SelectBox field value
     *  @param field_name : the SelectBox field_name
     *  @return String : value of the SelectBox field
     */
    function getSFValues(ArtifactField $af) {
        
        if ((!$af->isStandardField()) && (!$af->isUsername())) {
            $sql = sprintf('SELECT artifact_id as id,afvl.value as val
                            FROM artifact_field_value afv
                            INNER JOIN artifact_field af
                            USING (field_id)
                            INNER JOIN artifact_field_value_list afvl
                            USING (group_artifact_id,field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.valueInt = afvl.value_id
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($af->field_name)
                          );
        } else if (($af->isStandardField()) && (!$af->isUsername())) {
            $sql = sprintf('SELECT artifact_id as id,afvl.value as val
                            FROM artifact a
                            INNER JOIN artifact_field_value_list afvl
                            USING (group_artifact_id)
                            INNER JOIN artifact_field af
                            USING (group_artifact_id,field_id)
                            WHERE a.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND a.%s = afvl.value_id
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($af->field_name),
                            db_es($af->field_name)
                          );
                          //echo $sql;
        } else if ((!$af->isStandardField()) && ($af->isUsername())) {
            $sql = sprintf('SELECT artifact_id as id,u.realName as val
                            FROM artifact_field_value afv
                            INNER JOIN artifact_field af USING (field_id)
                            INNER JOIN user u
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.valueInt = u.user_id
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($af->field_name)
                          );
        } else {
            $sql = sprintf('SELECT a.artifact_id as id,u.realName  as val
                            FROM artifact a,user u
                            WHERE a.group_artifact_id = %d
                            AND a.%s = u.user_id
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($af->field_name)
                          );
        }
        return db_query($sql);
    }

    /**
     * function to get a Text field value
     *  @param field_name : the Text field_name
     *  @return String : value of the Text field
     */
    function getTFValues(ArtifactField $af) {
        if (!$af->isStandardField()) {
            $sql = sprintf('SELECT artifact_id as id,afv.valueText as val
                            FROM artifact_field_value afv
                            INNER JOIN artifact_field af
                            USING (field_id)
                            WHERE af.group_artifact_id = %d
                            AND af.field_name = "%s"
                            AND afv.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_ei($this->chart->getGraphicReport()->getAtid()),
                            db_es($af->field_name)
                          );
        } else {
            $sql = sprintf('SELECT artifact_id as id, %s as val
                            FROM artifact a
                            WHERE a.group_artifact_id = %d
                            AND a.artifact_id IN ('.implode(',',$this->artifacts).')',
                            db_es($af->field_name),
                            db_ei($this->chart->getGraphicReport()->getAtid())

                          );
        }
        return db_query($sql);
    }

    /**
     * Fill engine object with database results depending of data type
     */
    protected function _fillEngineData($res, $engine, $type) {
        if ($res && !db_error($res)) {
            while ($row = db_fetch_array($res)) {
                // Take into account when there are multiple values for a field
                if (isset($engine->data[$this->artPos[$row['id']]][$type])) {
                    $engine->data[$this->artPos[$row['id']]][$type] .= ', '.$row['val'];
                } else {
                    $engine->data[$this->artPos[$row['id']]][$type] = $row['val'];
                }
            }
        }
    }

    /**
     * getter method to get start date property
     *
     *     @return Unix Date start_date : the ganttbar (activity) start date
     */
    function getStartDate($engine) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(), $this->chart->getField_start());
        $res = $this->getDateValues($af);
        $this->_fillEngineData($res, $engine, 'start');
    }

    /**
     * getter method to get finish date property
     *
     *     @return Unix Date finish_date : the ganttbar (activity) finish date
     */
    function getFinishDate($engine) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(), $this->chart->getField_finish());
        $res = $this->getDateValues($af);
        $this->_fillEngineData($res, $engine, 'finish');
    }

    /**
     * getter method to get due date property
     *
     *     @return Unix Date due_date : the ganttbar (activity) due date
     */
    function getDueDate($engine) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(), $this->chart->getField_due());
        $res = $this->getDateValues($af);
        $this->_fillEngineData($res, $engine, 'due');
    }

    /**
     * getter method to get progress property
     *
     *     @return float progress : the ganttbar (activity) progress <0..1>
     */
    function getProgress($engine) {
        $res = $this->getIntValues($this->chart->getField_percentage());
        if ($res && !db_error($res)) {
            while ($row = db_fetch_array($res)) {
                if ($row['val'] <= 0){
                    $val = 0;
                } elseif($row['val'] >= 100){
                    $val = 1;
                } else{
                    $val = $row['val'] / 100;
                }
                $engine->data[$this->artPos[$row['id']]]['progress'] = $val;
            }
        }
    }

    /**
     * getter method to get righttext property
     *
     *     @return String : text diplayed in the right of ganttbar (activity)
     */
    function getRightText($engine) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(), $this->chart->getField_righttext());
        $res = $this->getSFValues($af);
        $this->_fillEngineData($res, $engine, 'right');
    }

    /**
     * getter method to get summary property
     *
     *     @return String : text diplayed in the left of gantt chart
     */
    function getSummary($engine) {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(), $this->chart->getSummary());
        $engine->summary_label = $af->getLabel();

        $res = $this->getTFValues($af);
        $this->_fillEngineData($res, $engine, 'summary');
    }

    /**
     * build Gantt chart properties
     *
     * Gather all data needed to build the Gantt chart, field by field
     *
     * @param Bar_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);

        // First, fill $engine->data an record where artifact postion in
        // array (mandatory to keep sort order)
        $engine->data = array();
        $nbArts  = count($this->artifacts);
        for ($i=0; $i < $nbArts; $i++) {
            $engine->data[$i]['id'] = $this->artifacts[$i];
            $this->artPos[$this->artifacts[$i]] = $i;
        }

        $this->getStartDate($engine);
        $this->getDueDate($engine);
        $this->getFinishDate($engine);
        $this->getProgress($engine);
        $this->getRightText($engine);
        $this->getSummary($engine);
        $engine->title      = $this->chart->getTitle();
        $engine->description= $this->chart->getDescription();
        $engine->scale      = $this->chart->getScale();
        $engine->asOfDate   = $this->chart->getAs_of_date();

        $this->buildData($engine);
    }

    /**
     * Build bar chart data
     *
     * Normalize data computed in buildProperties in order to have consistent
     * values to give to jpgraph
     *
     * @param Gantt_Engine object
     * @return array data array
     */ 
    function buildData($engine) {
        $groupId = $this->chart->getGraphicReport()->getGroupId();
        $atid    = $this->chart->getGraphicReport()->getAtid();
        foreach ($engine->data as $i => $data) {
            if (!isset($data['start'])) {
                $engine->data[$i]['start'] = 0;
            }
            if (!isset($data['due'])) {
                $engine->data[$i]['due'] = '';
            }
            if (!isset($data['finish'])) {
                $engine->data[$i]['finish'] = 0;
            }
            if (!isset($data['progress'])) {
                $engine->data[$i]['progress'] = 0;
            }
            if (!isset($data['right'])) {
                $engine->data[$i]['right'] = '';
            }
            if (!isset($data['summary'])) {
                $engine->data[$i]['summary'] = '';
            }
            $engine->data[$i]['hint'] = $engine->data[$i]['summary'];

            $engine->data[$i]['links'] = "/tracker/?func=detail&aid=".$data['id']."&group_id=".$groupId."&atid=".$atid;
        }

        //var_dump($engine->data);
        return $engine->data;
    }
}
?>

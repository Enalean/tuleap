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

class GraphOnTrackers_Chart_LineDataBuilder extends ChartDataBuilder {

    /**
     * build bar chart properties
     *
     * @param Line_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $engine->method         = $this->chart->getMethod();
        $engine->date_reference = $this->chart->getDate_reference();
        $engine->date_min       = $this->chart->getDate_min();
        $engine->date_max       = $this->chart->getDate_max();
        $engine->state_source   = $this->chart->getState_source();
        $engine->state_target   = $this->chart->getState_target();
        $engine->field_base     = $this->chart->getField_base();
        $this->buildData($engine);
    }

    /**
    * function to build line chart data
    *   @param le : Line_engine object
    *   @return array : data array
    */
    function buildData($engine) {
        if ($engine->method == 'age') {
            $engine->data = $this->getAgeOfArtifacts();
        } else {
            $engine->data = $this->getTimeEvolution();
        }
        $engine->xaxis = $this->formatGraphicXaxis($engine->data);
        return $engine->data;
    }

    /**
    * function to get Month Hearders
    *
    *     @return array : Month headers array
    */

    function getMonthHeaders(){

        return array($GLOBALS['Language']->getText('plugin_graphontrackers_line_month','january'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','february'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','march'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','april'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','may'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','june'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','jully'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','august'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','september'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','october'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','november'),
                     $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','december'),
                    );
    }

    /**
    * function to get Month position in data array
    *
    *     @return int position : get Position ofr a month in the data array
    */

    function getMonthNum($month) {
        switch ($month) {
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','january')   : return '01'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','february')  : return '02'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','march')     : return '03'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','april')     : return '04'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','may')       : return '05'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','june')      : return '06'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','jully')     : return '07'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','august')    : return '08'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','september') : return '09'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','october')   : return '10'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','november')  : return '11'; break;
            case $GLOBALS['Language']->getText('plugin_graphontrackers_line_month','december')  : return '12'; break;
            default: return '00'; break;
        }
    }

    /**
    * function to get WF WellFormed artifacts (Mean artifacts that pass to Dest State and pass by src State before it)
    *
    *     @return array artifact_id
    *
    */

    function getValidArtifacts() {

        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DISTINCT ah1.artifact_id
                    FROM artifact_history ah1 JOIN (artifact_history ah2) USING (artifact_id,field_name)
                    WHERE  ah1.new_value = ".db_es($this->chart->getState_target())."
                    AND ah2.new_value=".db_es($this->chart->getState_source())."
                    AND ah1.artifact_id
                    IN (".implode(",",$this->artifacts).")
                    AND ah1.artifact_id
                    IN (
                        SELECT DISTINCT ah3.artifact_id
                        FROM artifact_history ah3
                        JOIN (artifact_history ah4) USING (artifact_id,field_name)
                        WHERE ah3.artifact_id
                        IN (".implode(',',$this->artifacts).")
                        AND ah3.new_value=".db_es($this->chart->getState_source())."
                        AND ah4.new_value=".db_es($this->chart->getState_target())."
                        AND ah4.date >= ah3.date
                        AND ah3.field_name='".db_es($this->chart->getField_base())."'
                       )
                    ORDER BY ah1.artifact_id";
            $res = db_query($sql);
            return util_result_column_to_array($res);
        } else {
            return  array();
        }
    }

    /**
    * function generate Time Cycle artifacts WF WellFormed (means artifacts that pass to dest state and pass by scr state before it
    *
    *     @return array (data array) format array([1] =>('t'=>month/year,'s' => average period (in days)))
    *
    */

    function buildTimeEvolutionDataWF() {
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(ah.date),'%m/%Y') t,
                    ((HOUR(SEC_TO_TIME(SUM(ah.date - dah.date)))/24)) s,
                    COUNT(*) as c
                    FROM artifact_history ah
                    JOIN (
                          SELECT ah1.artifact_id,ah1.field_name,ah1.date
                          FROM artifact_history ah1
                          WHERE ah1.artifact_id
                          IN (".implode(",",$this->artifacts).")
                          AND ah1.field_name='".db_es($this->chart->getField_base())."'
                          AND ah1.new_value=".db_es($this->chart->getState_source()).") dah USING (artifact_id,field_name)
                    WHERE ah.artifact_id IN (".implode(",",$this->artifacts).")
                    AND ah.new_value=".db_es($this->chart->getState_target())."
                    AND ah.field_name='".db_es($this->chart->getField_base())."'
                    GROUP BY t";
            $res = db_query($sql);

            for($i=0;$i<db_numrows($res);$i++) {
                $result[$i] = db_fetch_array($res);
                $tab[$i]['t'] = $result[$i]['t'];
                $tab[$i]['s'] = $result[$i]['s'];
                $tab[$i]['c'] = $result[$i]['c'];
            }

            return $tab;
        } else {
            return  array();
        }
    }

    /**
    * function to get NWF NotWellFormed artifacts (Mean artifacts that pass to Dest State without passing By src State)
    *
    *     @return array artifact_id
    *
    */

    function getInvalidActifacts() {
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DISTINCT artifact_id
                    FROM artifact_history
                    WHERE artifact_id
                    IN (".implode(",",$this->artifacts).")
                    AND  field_name='".db_es($this->chart->getField_base())."'
                    AND  new_value=".db_es($this->chart->getState_target())."
                    AND  artifact_id
                    NOT IN (
                           SELECT DISTINCT artifact_id
                           FROM artifact_history
                           WHERE artifact_id IN (".implode(",",$this->artifacts).")
                           AND field_name='".db_es($this->chart->getField_base())."'
                            AND new_value=".db_es($this->chart->getState_source())."
                           )";
            $res = db_query($sql);
            return util_result_column_to_array($res);
        } else {
            return  array();
        }
    }


     /**
     * function generate Time Cycle artifacts NWF NotWellFormed (means artifacts that pass to dest state without scr state [example: (None->Revolved) not from (New->Resolved)] )
     *
     *     @return array (data array) format array([1] =>('t'=>month/year,'s' => average period (in days)))
     *
     */

    function buildTimeEvolutionDataDestNotSrc() {
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(ah.date),'%m/%Y') as t,
                    ((HOUR(SEC_TO_TIME(SUM(ah.date - da.open_date)))/24)) as s,
                    COUNT(*) as c
                    FROM artifact_history ah
                    JOIN (
                          SELECT a.artifact_id,a.open_date
                          FROM artifact a
                          WHERE a.artifact_id
                          IN (".implode(",",$this->artifacts).")) da USING (artifact_id)
                    WHERE ah.artifact_id
                    IN (".implode(",",$this->artifacts).")
                    AND ah.new_value=".db_es($this->chart->getState_target())."
                    AND ah.field_name='".db_es($this->chart->getField_base())."'
                    GROUP BY t";
            $res = db_query($sql);
            for($i=0;$i<db_numrows($res);$i++) {
                $result[$i] = db_fetch_array($res);
                $tab[$i]['t'] = $result[$i]['t'];
                $tab[$i]['s'] = $result[$i]['s'];
                $tab[$i]['c'] = $result[$i]['c'];
            }
            return $tab;
        } else {
            return  array();
        }
    }

     /**
     * function generate Age of artifacts Valid (means artifacts that have a passage date to 'New' state (were in New state))
     *
     *     @return array (data array) format array([1] =>('t'=>month/year,'s' => average period (in days)))
     *
     */


    function buildAgeOfArtifactData() {
        if ($this->chart->getDate_reference() == '') {
            $this->chart->setDate_reference(strtotime('now'));
        }
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0) && ($this->chart->getState_target() != null) && ($this->chart->getState_target() != 0)) {
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(ah.date),'%m/%Y') as t,
                    SUM(DATEDIFF( DATE_FORMAT(FROM_UNIXTIME(ah.date),'%Y-%m-01') + INTERVAL (DAYOFMONTH('".date('Y-m-d',db_ei($this->chart->getDate_reference()))."') -1) DAY,DATE_FORMAT(FROM_UNIXTIME(dah.date),'%Y-%m-%d'))) as s,
                    COUNT(*) as c
                    FROM artifact_history ah
                    JOIN (
                         SELECT ah1.artifact_id,ah1.field_name, ah1.date
                         FROM artifact_history ah1
                         WHERE ah1.artifact_id IN(".implode(',',$this->artifacts).")
                         AND ah1.field_name='".db_es($this->chart->getField_base())."'
                         AND ah1.new_value=".db_es($this->chart->getState_source()).") dah USING (artifact_id,field_name)
                    WHERE ah.artifact_id IN(".implode(',',$this->artifacts).")";
             // temporary
            $values_field2 = null;
            if($values_field2 != null) {
                $sql .=" AND (ah.new_value IN (".implode(',',db_es($this->chart->getState_target())).") OR ah.new_value IN (".implode(',',$values_field2).")) ";
            } else {
                $sql .=" AND ah.new_value = ".db_es($this->chart->getState_target())." ";
            }

            $sql .= " AND ah.field_name='".db_es($this->chart->getField_base())."'";

            if (($this->chart->getDate_min() != 0 ) && ($this->chart->getDate_max() != 0) && ($this->chart->getDate_min() < $this->chart->getDate_max())) {
                $sql .= " AND ah.date BETWEEN ".db_ei($this->chart->getDate_min())." AND ".db_ei($this->chart->getDate_max())."";
            }

            $sql .= " GROUP BY t";
            //echo $sql;
            $res = db_query($sql);
            for($i=0;$i<db_numrows($res);$i++) {
                $result[$i] = db_fetch_array($res);
                if  ($result[$i]['s'] >= 0) {
                    $tab[$i] = array('t' => $result[$i]['t'],'s' => $result[$i]['s'], 'c' => $result[$i]['c']);
                } else {
                    $tab[$i] = array('t' => $result[$i]['t'],'s' => 0-$result[$i]['s'], 'c' => $result[$i]['c']);
                }
            }
            return $tab;
        } else {
            return  array();
        }

    }

   /**
     * function generate Age of artifacts Invalid (means artifacts that doesn't have a New state Date (were not in New state before))
     *
     *     @return array (data array) format array([1] =>('t'=>month/year,'s' => average period (in days)))
     *
     */

    function buildAgeOfInvalidArtifactData() {
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(ah.date),'%m/%Y') as t,
                    SUM(DATEDIFF( DATE_FORMAT(FROM_UNIXTIME(ah.date),'%Y-%m-01') + INTERVAL (DAYOFMONTH('".date('Y-m-d',$this->chart->getDate_reference())."') -1) DAY,DATE_FORMAT(FROM_UNIXTIME(dah.open_date),'%Y-%m-%d'))) as s,
                    COUNT(*) as c
                    FROM artifact_history ah
                    JOIN (
                         SELECT a.artifact_id, a.open_date
                         FROM artifact a
                         WHERE a.artifact_id IN (".implode(',',$this->artifacts).")
                         ) dah USING (artifact_id)
                    WHERE ah.artifact_id IN(".implode(',',$this->artifacts).")";
            $values_field2 = null;

            if($values_field2 != null) {
                 $sql .= " AND ((ah.new_value IN (".implode(',',$values_field2).") AND ah.field_name='".db_es($this->chart->getField_base())."' ) OR (ah.new_value IN (".implode(',',$values_field2).") AND ah.field_name='".db_es($field_name_status)."'))";
            } else {
                $sql .= " AND (ah.new_value IN (".db_es($this->chart->getState_target()).") AND ah.field_name='".db_es($this->chart->getField_base())."') ";
            }
            $sql .= " AND ah.date BETWEEN ".db_ei($this->chart->getDate_min())." AND ".db_ei($this->chart->getDate_max());
            $sql .= " GROUP BY t";
            //echo $sql;
            $res = db_query($sql);
            $tab = array();
            for($i=0;$i<db_numrows($res);$i++) {
                $result[$i] = db_fetch_array($res);
                if  ($result[$i]['s'] >= 0) {
                    $tab[$i] = array('t' => $result[$i]['t'],'s' => $result[$i]['s'], 'c' => $result[$i]['c']);
                } else {
                    $tab[$i] = array('t' => $result[$i]['t'],'s' => 0-$result[$i]['s'], 'c' => $result[$i]['c']);
                }
            }
            return $tab;
        } else {
            return  array();
        }
    }

    /**
    * function generate Age of artifacts data array (merge valid and invalid age of artifacts data array)
    *
    *     @return array (data array) format array([1] =>('t'=>month/year,'s' => average period (in days)))
    *
    */

    function getAgeOfArtifacts() {
        $ValidArts = $this->getValidArtifactNewDate();
        $dataValid = array();
        //print_r($ValidArts);
        if (count($ValidArts)>0) {
            $dataValid = $this->buildAgeOfArtifactData();
            $artifactsRemain = array_diff($this->artifacts,$ValidArts);
        } else {
            $artifactsRemain = $this->artifacts;
        }
        $artifacts = $this->artifacts;
        $this->artifacts = $artifactsRemain;
        $InvalidArts = $this->getInValidArtifactNewDate();

        if (count($InvalidArts)>0) {
            $dataInvalid = $this->buildAgeOfInvalidArtifactData();

            //merge values
            for ($i=0;$i<count($dataValid);$i++) {
                $position = $this->getDatePosition($dataValid[$i]['t'],$dataInvalid);
                if ($position !== false) {
                    if ($dataValid[$i]['c']+$dataInvalid[$position]['c'] != 0){
                        $dataInvalid[$position]['s'] = ($dataValid[$i]['s'] + $dataInvalid[$position]['s'])/($dataValid[$i]['c']+$dataInvalid[$position]['c']);
                    } else {
                        $dataInvalid[$position]['s'] = ($dataValid[$i]['s'] + $dataInvalid[$position]['s']);
                    }
                } else {
                    if (count($dataValid[$i]['c']) != 0) {
                        $dataInvalid[count($dataInvalid)] =array ('t' => $dataValid[$i]['t'], 's' =>$dataValid[$i]['s']/($dataValid[$i]['c']));
                    } else {
                        $dataInvalid[count($dataInvalid)] =array ('t' => $dataValid[$i]['t'], 's' =>$dataValid[$i]['s']);
                    }
                }
            }
            return $dataInvalid;

        } else {
            return $dataValid;
        }
    }

    /**
     * function generate Time Cycle data array
     *
     *     @return array (data array) format array([1] =>('t'=>month/year,'s' => average period (in days)))
     *
     */

    function getTimeEvolution() {
        $artifactsRemain = array();
        $artifactsInvalid = array();
        $dataWF = array();
        $dataNWF = array();
        $artifacts = $this->artifacts;
        // artifacts well formed (WellFormed) where the artifact pass from state_source to state_target and dest date > src Date
        $this->artifacts = $this->getValidArtifacts();
        if (count($this->artifacts)) {
            $dataWF = $this->buildTimeEvolutionDataWF();
            $artifactsRemain = array_diff($artifacts,$this->artifacts);
        } else {
             $artifactsRemain = $artifacts;
        }
        $this->artifacts = $artifactsRemain;
        // artifacts in target state but without being state source before

        $artifactsInvalid = $this->getInvalidActifacts();
        $artifacts = $this->artifacts;
        $this->artifacts = $artifactsInvalid;

        if (count($artifactsInvalid)>0) {
            $dataNWF = $this->buildTimeEvolutionDataDestNotSrc();
            //merge values
            for ($i=0;$i<count($dataWF);$i++) {
                $position = $this->getDatePosition($dataWF[$i]['t'],$dataNWF);
                if ($position !== false) {
                    if (($dataNWF[$position]['c']+$dataWF[$i]['c']) != 0) {
                        $dataNWF[$position]['s'] = (($dataWF[$i]['s'] + $dataNWF[$position]['s'])/($dataNWF[$position]['c']+$dataWF[$i]['c']));
                    } else {
                        $dataNWF[$position]['s'] = ($dataWF[$i]['s'] + $dataNWF[$position]['s']);
                    }
                } else {
                    if ($dataWF[$i]['c'] != 0) {
                        $dataNWF[count($dataNWF)] =array ('t' => $dataWF[$i]['t'], 's' =>($dataWF[$i]['s']/$dataWF[$i]['c']));
                    } else {
                        $dataNWF[count($dataNWF)] =array ('t' => $dataWF[$i]['t'], 's' =>($dataWF[$i]['s']));
                    }
                }
            }
            return $dataNWF;
        } else {

            return $dataWF;
        }
    }

    /**
    * function to check if there is values in Time Cycle data array
    *
    *     @return boolean (true if is empty data array)
    *
    */

    function isEmptyData(){
        $data  = $this->getTimeEvolution();
        if (count($data)>0){
            return false;
        } else {
            return true;
        }
    }

    /**
    * function to check if there is values in Age data array
    *
    *     @return boolean (true if is empty data array)
    *
    */

    function isEmptyDataAge() {
        $data = $this->getAgeOfArtifacts();
        if (count($data)>0){
            return false;
        } else {
            return true;
        }
    }

    /**
    * function to get artifacts that pass by New state
    *
    *     @return array artifact_id
    *
    */

    function getValidArtifactNewDate() {
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DISTINCT artifact_id
                    FROM artifact_history
                    WHERE artifact_id
                    IN (".implode(",",$this->artifacts).")
                    AND field_name='".db_es($this->chart->getField_base())."'
                    AND new_value=".db_es($this->chart->getState_source());
            $res = db_query($sql);
            return util_result_column_to_array($res);
        } else {
            return  array();
        }
    }

    /**
    * function to get artifacts that doesn't pass by New state (directly to another state - Resolved, Verified)
    *
    *     @return array artifact_id
    *
    */

    function getInValidArtifactNewDate() {
        if ((is_array($this->artifacts)) && (count($this->artifacts)>0)) {
            $sql = "SELECT DISTINCT artifact_id
                    FROM artifact_history
                    WHERE artifact_id
                    IN (".implode(",",$this->artifacts).")
                    AND field_name='".db_es($this->chart->getField_base())."'
                    AND  new_value <> ".db_es($this->chart->getState_source());
            $res = db_query($sql);
            return util_result_column_to_array($res);
        } else {
            return array();
        }
    }

    /**
    * function to get date position in data array
    *
    *    @param key: date month/year (06/2007)
    *   @param data: Data array array([1] =>('t'=>month/year,'s' => average period (in days)) )
    *     @return int position
    *
    */

    function getDatePosition($key,$data) {
        for ($i=0;$i<count($data);$i++) {
            if ($data[$i]['t'] == $key) {
                return $i;
            }
        }
        return false;
    }

    /**
     * method for inversing a matrix
     *
     *    @param matrix:the matrice to be inversed
     *    @return data matrice
     *
     */

    function invMatrice($matrix) {
        for ($i=0;$i<count($matrix);$i++) {
            for ($j=0;$j<count($matrix[$i]);$j++) {
                $matrixRet[$j][$i] = $matrix[$i][$j];
            }
        }
        return $matrixRet;
    }

    /**
    * method to format axis and data to required form
    *
    *    @param data data matrix
    *    @return array header to be put in axis
    *
    */

    function formatGraphicXaxis (&$data) {

        $year = array();

        for($i=0;$i<count($data);$i++) {
            $year[$i] = (int)(substr($data[$i]['t'],strpos($data[$i]['t'],'/')+1));
        }

        $returns = null;

        if (count($year)>0) {
            $year_start = $this->select_extremum($year,0);

            $year_end   = $this->select_extremum($year,1);

            $months = $this->getMonthHeaders();
            $k = 0;

            for ($i=$year_start;$i<=$year_end;$i++) {
                for ($j=0;$j<count($months);$j++) {
                    $returns[$k] = $months[$j].'/'.$i;
                    $k++;
                }
            }

            for($i=0;$i<count($returns);$i++) {
                $y  = substr($returns[$i],strpos($returns[$i],'/')+1);
                $m  = (int) ($this->getMonthNum(substr($returns[$i],0,strpos($returns[$i],'/')))-1);
                $format = $m.'/'.$y;
                $data_return[$i]['t'] = $format;
                $data_return[$i]['s'] = 0;
            }

            for($i=0;$i<count($data);$i++) {
                $y  = substr($data[$i]['t'],strpos($data[$i]['t'],'/')+1);
                $m  = (int) (substr($data[$i]['t'],0,strpos($data[$i]['t'],'/'))-1);
                $mf = $months[$m];
                $format = $mf.'/'.$y;
                $position = array_search($format,$returns);
                if ($position !== false) {
                    $data_return[$position]['s'] = $data[$i]['s'];
                }
            }
            for($i=0;$i<count($data_return);$i++) {
                $data_array[$i] = $data_return[$i]['s'];
            }

            $data = $data_array;
        }

        if ((is_null($returns)) || (count($returns) == 0)) {
            $returns[0] = "";
        }
        return $returns;
    }

    /**
    * method to select the extremum in data matrix
    *
    * @param collect data matrix
    * @param extremum int (0 or 1 to choose the min or the max)
    * @return array header to be put in axis
    *
    */

    function select_extremum($collect,$extremum){
        $val = $collect[0];
         for ($i=0;$i<count($collect);$i++) {
             if ($extremum == 0) {
                 if($collect[$i] <= $val) {
                     $val = $collect[$i];
                 }
             } else {
                 if($collect[$i] >= $val) {
                     $val = $collect[$i];

                 }
             }
         }
         return $val;
    }
}
?>

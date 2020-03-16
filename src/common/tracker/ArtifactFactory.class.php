<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class ArtifactFactory
{

    /**
     * The ArtifactType object.
     *
     * @var     object  $ArtifactType.
     */
    public $ArtifactType;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     *
     *
     *    @param    object    The ArtifactType object to which this ArtifactFactory is associated.
     *    @return bool success.
     */
    public function __construct(&$ArtifactType)
    {
        global $Language;

        if (!$ArtifactType || !is_object($ArtifactType)) {
            $this->setError('ArtifactFactory:: ' . $Language->getText('tracker_common_canned', 'not_valid'));
            return false;
        }
        if ($ArtifactType->isError()) {
            $this->setError('ArtifactFactory:: ' . $ArtifactType->getErrorMessage());
            return false;
        }
        $this->ArtifactType = $ArtifactType;

        return true;
    }

    /**
     *    getMyArtifacts - get an array of Artifact objects submitted by a user or assigned to this user
     *
     *  @param user_id: the user id
     *
     *    @return    array    The array of Artifact objects.
     */
    public function getMyArtifacts($user_id)
    {
        global $Language;

        $artifacts = array();

     // List of trackers - Check on assigned_to or multi_assigned_to or submitted by
        $sql = "SELECT a.*,afv.valueInt as assigned_to FROM artifact_group_list agl, artifact a, artifact_field af, artifact_field_value afv WHERE " .
         "a.group_artifact_id = " . db_ei($this->ArtifactType->getID()) . " AND a.group_artifact_id = agl.group_artifact_id AND af.group_artifact_id = agl.group_artifact_id AND " .
         "(af.field_name = 'assigned_to' OR af.field_name = 'multi_assigned_to') AND af.field_id = afv.field_id AND a.artifact_id = afv.artifact_id AND " .
         "(afv.valueInt=" . db_ei($user_id) . " OR a.submitted_by=" . db_ei($user_id) . ") AND a.status_id <> 3 LIMIT 100";

     //echo $sql;
        $result = db_query($sql);
        $rows = db_numrows($result);
        $this->fetched_rows = $rows;
        if (db_error()) {
            $this->setError($Language->getText('tracker_common_factory', 'db_err') . ': ' . db_error());
            return false;
        } else {
            while ($arr = db_fetch_array($result)) {
                $artifacts[$arr['artifact_id']] = new Artifact($this->ArtifactType, $arr['artifact_id']);
            }
            if (count($artifacts)) {
                return $artifacts;
            }
        }

     // List of trackers - Check on submitted_by
        $sql = "SELECT a.*, 0 as assigned_to FROM artifact_group_list agl, artifact a WHERE " .
         "a.group_artifact_id = " . db_ei($this->ArtifactType->getID()) . " AND a.group_artifact_id = agl.group_artifact_id AND " .
         "a.submitted_by=" . db_ei($user_id) . " AND a.status_id <> 3 LIMIT 100";

     //echo $sql;
        $result = db_query($sql);
        $rows = db_numrows($result);
        $this->fetched_rows = $rows;
        if (db_error()) {
            $this->setError($Language->getText('tracker_common_factory', 'db_err') . ': ' . db_error());
            return false;
        } else {
            while ($arr = db_fetch_array($result)) {
                $artifacts[$arr['artifact_id']] = new Artifact($this->ArtifactType, $arr['artifact_id']);
            }
        }

        return $artifacts;
    }
    /**
     *  getArtifacts - get an array of Artifact objects
     *
     *    @param $criteria : array of items field => value
     *    @param $offset   : the index of artifact to begin
     *    @param $max_rows : number of artifacts to return
     *
     *  @param OUT $total_artifacts : total number of artifacts (if offset and max_rows were not here)
     *
     *    @return    array    The array of Artifact objects.
     */
    public function getArtifacts($criteria, $offset, $max_rows, &$total_artifacts)
    {
        global $Language, $art_field_fact;

        $ACCEPTED_OPERATORS = array('=', '<', '>', '<>', '<=', '>=');

        $artifacts = array();
        if (is_array($criteria) && count($criteria) > 0) {
            $sql_select = "SELECT a.* ";
            $sql_from = " FROM artifact_group_list agl, artifact a ";
            $sql_where = " WHERE a.group_artifact_id = " . db_ei($this->ArtifactType->getID()) . " AND 
                          a.group_artifact_id = agl.group_artifact_id ";

            $cpt_criteria = 0;  // counter for criteria (used to build the SQL query)
            foreach ($criteria as $c => $cr) {
                $af = $art_field_fact->getFieldFromName($cr->field_name);
                if (!$af || !is_object($af)) {
                    $this->setError('Cannot Get ArtifactField From Name : ' . $cr->field_name);
                    return false;
                } elseif ($art_field_fact->isError()) {
                    $this->setError($art_field_fact->getErrorMessage());
                    return false;
                }

                if ($af->isDateField() && ($cr->field_name != 'open_date' && $cr->field_name != 'close_date' && $cr->field_name != 'last_update_date')) {
                    // The SQL query expects a timestamp, whereas the given date is in YYYY-MM-DD format
                    $cr->field_value = strtotime($cr->field_value);
                }

                $operator = "=";    // operator by default
                if (isset($cr->operator) && in_array($cr->operator, $ACCEPTED_OPERATORS)) {
                    $operator = $cr->operator;
                }

                if ($af->isStandardField()) {
                    if ($cr->operator == '=' && ($cr->field_name == 'open_date' || $cr->field_name == 'close_date' || $cr->field_name == 'last_update_date')) {
                        // special case for open_date and close_date with operator = : the hours, minutes, and seconds are stored, so we have to compare an interval
                        list($year,$month,$day) = util_date_explode($cr->field_value);
                        $time_end = mktime(23, 59, 59, $month, $day, $year);
                        $sql_where .= " AND (a." . $cr->field_name . " >= '" . strtotime($cr->field_value) . "')";
                        $sql_where .= " AND (a." . $cr->field_name . " <= '" . $time_end . "')";
                    } else {
                        if ($af->isDateField()) {
                            $sql_where .= " AND (a." . $cr->field_name . " " . $operator . " '" . strtotime($cr->field_value) . "')";
                        } else {
                            $sql_where .= " AND (a." . $cr->field_name . " " . $operator . " '" . db_es($cr->field_value) . "')";
                        }
                    }
                } else {
                    $sql_select .= ", afv" . $cpt_criteria . ".valueInt ";
                    $sql_from .= ", artifact_field af" . $cpt_criteria . ", artifact_field_value afv" . $cpt_criteria . " ";
                    $sql_where .= " AND af" . $cpt_criteria . ".group_artifact_id = agl.group_artifact_id
                                    AND (af" . $cpt_criteria . ".field_name = '" . $cr->field_name . "' 
                                    AND afv" . $cpt_criteria . "." . $af->getValueFieldName() . " " . $operator . " '" . $cr->field_value . "') 
                                    AND af" . $cpt_criteria . ".field_id = afv" . $cpt_criteria . ".field_id 
                                    AND a.artifact_id = afv" . $cpt_criteria . ".artifact_id ";
                }
                $cpt_criteria += 1;
            }

            $sql = $sql_select . $sql_from . $sql_where;
        } else {
            $sql = "SELECT a.artifact_id 
                    FROM artifact_group_list agl, artifact a 
                    WHERE a.group_artifact_id = " . db_ei($this->ArtifactType->getID()) . " AND 
                          a.group_artifact_id = agl.group_artifact_id";
        }

        // we count the total number of artifact (without offset neither limit) to be able to perform the pagination
        $result_count = db_query($sql);
        $rows_count = db_numrows($result_count);
        $total_artifacts = $rows_count;

        $offset = intval($offset);
        $max_rows = intval($max_rows);
        if ($max_rows > 0) {
            if (!$offset || $offset < 0) {
                $offset = 0;
            }
            $sql .= " LIMIT " .  db_ei($offset)  . "," .  db_ei($max_rows);
        }

        $result = db_query($sql);
        $rows = db_numrows($result);
        $this->fetched_rows = $rows;
        if (db_error()) {
            $this->setError($Language->getText('tracker_common_factory', 'db_err') . ': ' . db_error());
            return false;
        } else {
            while ($arr = db_fetch_array($result)) {
                $artifact = new Artifact($this->ArtifactType, $arr['artifact_id'], true);
                // artifact is not added if the user can't view it
                if ($artifact->userCanView()) {
                    $artifacts[$arr['artifact_id']] = $artifact;
                }
            }
        }
        return $artifacts;
    }


    public function getArtifactsFromReport($group_id, $group_artifact_id, $report_id, $criteria, $offset, $max_rows, $sort_criteria, &$total_artifacts)
    {
        global $ath, $art_field_fact, $Language;

        $GLOBALS['group_id'] = $group_id;

        $chunksz = $max_rows;
        $advsrch = 0;   // ?
        $prefs = array();

        $report = new ArtifactReport($report_id, $group_artifact_id);
        if (!$report || !is_object($report)) {
            $this->setError('Cannot Get ArtifactReport From ID : ' . $report_id);
            return false;
        } elseif ($report->isError()) {
            $this->setError($report->getErrorMessage());
            return false;
        }

        $query_fields = $report->getQueryFields();
        $result_fields = $report->getResultFields();

        // Filter part
        if (is_array($criteria)) {
            foreach ($criteria as $cr) {
                $af = $art_field_fact->getFieldFromName($cr->field_name);
                if (!$af || !is_object($af)) {
                    $this->setError('Cannot Get ArtifactField From Name : ' . $cr->field_name);
                    return false;
                } elseif ($art_field_fact->isError()) {
                    $this->setError($art_field_fact->getErrorMessage());
                    return false;
                }

                if (! array_key_exists($cr->field_name, $query_fields)) {
                    $this->setError('You cannot filter on field ' . $cr->field_name . ': it is not a query field for report ' . $report_id);
                    return false;
                }

                if ($af->isSelectBox() || $af->isMultiSelectBox()) {
                    $prefs[$cr->field_name] = explode(",", $cr->field_value);
                } else {
                    $prefs[$cr->field_name] = array($cr->field_value);
                    if (isset($cr->operator)) {
                        $prefs[$cr->field_name][] = $cr->operator;
                    }
                }
            }
        }

        // Sort part
        $morder = '';
        $array_morder = array();
        if (is_array($sort_criteria)) {
            foreach ($sort_criteria as $sort_cr) {
                $field_name = $sort_cr->field_name;
                // check if fieldname is ok
                $af = $art_field_fact->getFieldFromName($sort_cr->field_name);
                if (!$af || !is_object($af)) {
                    $this->setError('Cannot Get ArtifactField From Name : ' . $sort_cr->field_name);
                    return false;
                } elseif ($art_field_fact->isError()) {
                    $this->setError($art_field_fact->getErrorMessage());
                    return false;
                }

                if (! array_key_exists($sort_cr->field_name, $result_fields)) {
                    $this->setError('You cannot sort on field ' . $sort_cr->field_name . ': it is not a result field for report ' . $report_id);
                    return false;
                }

                // check if direction is ok
                $sort_direction = '>'; // by default, direction is ASC
                if (isset($sort_cr->sort_direction) && $sort_cr->sort_direction == 'DESC') {
                    $sort_direction = '<';
                }

                $array_morder[] = $field_name . $sort_direction;
            }
        }
        $morder = implode(',', $array_morder);

        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        $ath = new ArtifactTypeHtml($group);

        $artifact_report = new ArtifactReport($report_id, $group_artifact_id);

        // get the total number of artifact that answer the query, and the corresponding IDs
        $total_artifacts = $artifact_report->selectReportItems($prefs, $morder, $advsrch, $aids);

        // get the SQL query corresponding to the query
        $sql = $artifact_report->createQueryReport($prefs, $morder, $advsrch, $offset, $chunksz, $aids);

        $result = $artifact_report->getResultQueryReport($sql);
        $result_fields = $artifact_report->getResultFields();

        //we get from result only fields that we need to display in the report (we add at the begining id and severity only to identify the artifact and for the severity color)
        $artifacts = array();
        $i = 0;
        foreach ($result as $art) {
            $artifact_id = $art['artifact_id'];
            $severity_id = $art['severity_id'];
            $artifact = new Artifact($this->ArtifactType, $art['artifact_id'], true);
            if ($artifact->userCanView()) {
                $fields = array();
                reset($result_fields);
                $fields['severity_id'] = $severity_id;
                $fields['id'] = $artifact_id;
                foreach ($result_fields as $key => $field) {
                    $value = $result[$i][$key];
                    $fields[$key] = $value;
                }
                $artifacts[$artifact_id] = $fields;
            }
            $i++;
        }

        return $artifacts;
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state = true;
        $this->error_message = $string;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }
}

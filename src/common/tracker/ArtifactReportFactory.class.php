<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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


class ArtifactReportFactory
{
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
     *    @return bool success.
     */
    public function __construct()
    {
        return true;
    }

    /**
     * Return a new ArtifactReport object
     *
     * @param report_id: the report id to create the new ArtifactReport
     *
     * @return void
     */
    public function getArtifactReportHtml($report_id, $atid)
    {
        $sql = "SELECT * FROM artifact_report " .
         "WHERE report_id=" . db_ei($report_id);
     //echo $sql.'<br>';
        $res = db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            return false;
        }
        return new ArtifactReportHtml($report_id, $atid);
    }

    /**
     *
     *  Copy all the reports informations from a tracker to another.
     *
     *  @param atid_source: source tracker
     *  @param atid_dest: destination tracker
     *
     *    @return bool
     */
    public function copyReports($atid_source, $atid_dest)
    {
        global $Language;
        $report_mapping = array(100 => 100); //The system report 'Default' (sic)
     //
     // Copy artifact_report records which are not individual/personal
        $sql = "SELECT report_id,user_id,name,description,scope,is_default " .
        "FROM artifact_report " .
        "WHERE group_artifact_id='" . db_ei($atid_source) . "'" .
            "AND scope != 'I'";

     //echo $sql;

        $res = db_query($sql);

        while ($report_array = db_fetch_array($res)) {
            $sql_insert = 'INSERT INTO artifact_report (group_artifact_id,user_id,name,description,scope,is_default) VALUES (' . db_ei($atid_dest) . ',' . db_ei($report_array["user_id"]) .
              ',"' . db_es($report_array["name"]) . '","' . db_es($report_array["description"]) . '","' . db_es($report_array["scope"]) . '","' . db_es($report_array["is_default"]) . '")';

            $res_insert = db_query($sql_insert);
            if (!$res_insert || db_affected_rows($res_insert) <= 0) {
                $this->setError($Language->getText('tracker_common_reportfactory', 'ins_err', array($report_array["report_id"],$atid_dest,db_error())));
                return false;
            }

            $report_id = db_insertid($res_insert, 'artifact_report', 'report_id');
            $report_mapping[$report_array["report_id"]] = $report_id;
      // Copy artifact_report_field records
            $sql_fields = 'SELECT field_name,show_on_query,show_on_result,place_query,place_result,col_width ' .
            'FROM artifact_report_field ' .
            'WHERE report_id=' . db_ei($report_array["report_id"]);

      //echo $sql_fields;

            $res_fields = db_query($sql_fields);

            while ($field_array = db_fetch_array($res_fields)) {
                $show_on_query = ($field_array["show_on_query"] == "" ? "null" : $field_array["show_on_query"]);
                $show_on_result = ($field_array["show_on_result"] == "" ? "null" : $field_array["show_on_result"]);
                $place_query = ($field_array["place_query"] == "" ? "null" : $field_array["place_query"]);
                $place_result = ($field_array["place_result"] == "" ? "null" : $field_array["place_result"]);
                $col_width = ($field_array["col_width"] == "" ? "null" : $field_array["col_width"]);

                $sql_insert = 'INSERT INTO artifact_report_field VALUES (' . db_ei($report_id) . ',"' . db_es($field_array["field_name"]) .
                  '",' . db_ei($show_on_query) . ',' . db_ei($show_on_result) . ',' . db_ei($place_query) .
                  ',' . db_ei($place_result) . ',' . db_ei($col_width) . ')';

             //echo $sql_insert;
                $res_insert = db_query($sql_insert);
                if (!$res_insert || db_affected_rows($res_insert) <= 0) {
                    $this->setError($Language->getText('tracker_common_reportfactory', 'f_ind_err', array($report_array["report_id"],$field_array["field_name"],db_error())));
                    return false;
                }
            } // while
        } // while

        return $report_mapping;
    }

    /**
     *
     *  Delete all the reports informations for a tracker
     *
     *  @param atid: the tracker id
     *
     *    @return bool
     */
    public function deleteReports($atid)
    {
     // Delete artifact_report_field records
        $sql = 'SELECT report_id ' .
        'FROM artifact_report ' .
        'WHERE group_artifact_id=' . db_ei($atid);

     //echo $sql;

        $res = db_query($sql);

        while ($report_array = db_fetch_array($res)) {
            $sql_fields = 'DELETE ' .
            'FROM artifact_report_field ' .
            'WHERE report_id=' . db_ei($report_array["report_id"]);

      //echo $sql_fields;

            $res_fields = db_query($sql_fields);
        } // while

     // Delete artifact_report records
        $sql = 'DELETE ' .
        'FROM artifact_report ' .
        'WHERE group_artifact_id=' . db_ei($atid);

     //echo $sql;

        $res = db_query($sql);

        return true;
    }

    /**
     *  getReports - get an array of ArtifactReport objects
     *
     *    @param $group_artifact_id : the tracker id
     *    @param $user_id  : the user id
     *
     *    @return    array    The array of ArtifactReport objects.
     */
    public function getReports($group_artifact_id, $user_id)
    {
        $artifactreports = array();
        $sql = 'SELECT report_id,name,description,scope,is_default FROM artifact_report WHERE ';
        if (!$user_id || ($user_id == 100)) {
            $sql .= "(group_artifact_id=" .  db_ei($group_artifact_id)  . " AND scope='P') OR scope='S' " .
            'ORDER BY report_id';
        } else {
            $sql .= "(group_artifact_id= " . db_ei($group_artifact_id) . " AND (user_id=" . db_ei($user_id) . " OR scope='P')) OR " .
            "scope='S' ORDER BY scope,report_id";
        }

        $result = db_query($sql);
        $rows = db_numrows($result);
        if (db_error()) {
            $this->setError($Language->getText('tracker_common_factory', 'db_err') . ': ' . db_error());
            return false;
        } else {
            while ($arr = db_fetch_array($result)) {
                $artifactreports[$arr['report_id']] = new ArtifactReport($arr['report_id'], $group_artifact_id);
            }
        }
        return $artifactreports;
    }

    /**
     *  getDefaultReport - get report_id of the default report
     *
     *  @param group_artifact_id : the tracker id
     *  @return int     report_id
     */

    public function getDefaultReport($group_artifact_id)
    {
        $report_id = null;
        $sql = "SELECT report_id FROM artifact_report WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND is_default = 1";
        $result = db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $report_id = $arr['report_id'];
        }
        return $report_id;
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
}

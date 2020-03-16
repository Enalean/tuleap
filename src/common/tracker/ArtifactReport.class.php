<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

// Sort functions - Must be outside the classes
// Sort by place query
function cmp_place_query($field1, $field2)
{
    if ($field1->getPlaceQuery() <> null || $field2->getPlaceQuery() <> null) {
        if ($field1->getPlaceQuery() < $field2->getPlaceQuery()) {
            return -1;
        } elseif ($field1->getPlaceQuery() > $field2->getPlaceQuery()) {
            return 1;
        }
        return 0;
    } else {
        //For fields which are not search-ranked, use the field rank-on-screen
        if ($field1->getPlace() < $field2->getPlace()) {
            return -1;
        } elseif ($field1->getPlace() > $field2->getPlace()) {
            return 1;
        }
        return 0;
    }
}

// Sort by place result
function cmp_place_result($field1, $field2)
{
    if ($field1->getPlaceResult() <> null || $field2->getPlaceResult() <> null) {
        if ($field1->getPlaceResult() < $field2->getPlaceResult()) {
            return -1;
        } elseif ($field1->getPlaceResult() > $field2->getPlaceResult()) {
            return 1;
        }
        return 0;
    } else {
        //For fields which are not report-ranked, use the field rank-on-screen
        if ($field1->getPlace() < $field2->getPlace()) {
            return -1;
        } elseif ($field1->getPlace() > $field2->getPlace()) {
            return 1;
        }
        return 0;
    }
}

// Classe to manage the artifact report
class ArtifactReport
{

    // The report id
    public $report_id;

    // The group artifact id (artifact type)
    public $group_artifact_id;

    // The fields used by this report (array)
    public $fields;

    // Name of this report
    public $name;

    // Description of this report
    public $description;

    // Scope of this report ('S': system, 'P': project)
    public $scope;

    // Is this default report
    public $is_default;
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
     *    @param    report_id
     *  @param  atid: the artifact type id
     *
     *    @return bool success.
     */
    public function __construct($report_id, $atid)
    {
        $this->group_artifact_id = $atid;
        $this->fields = array();

        if (!$this->fetchData($report_id, $atid)) {
            return false;
        }

        return true;
    }

    public function getReportId()
    {
        return $this->report_id;
    }

    /**
     *    recreate - use this to reset a Report in the database.
     *
     *    @param    string    The report name.
     *    @param    string    The report description.
     *    @return true on success, false on failure.
     */
    public function recreate($user_id, $name, $description, $scope, $is_default)
    {
        global $ath,$Language;
     /*
     $perm = $ath->Group->getPermissionFromId( $user_id);

      if (!$perm || !is_object($perm) || !$perm->isArtifactAdmin()) {
       $this->setError('ArtifactReport: Permission Denied');
       return false;
      }
    */

        if (!$name || !$description || !$scope) {
            $this->setError('ArtifactReport: ' . $Language->getText('tracker_common_report', 'name_requ'));
            echo 'ArtifactReport: ' . $Language->getText('tracker_common_report', 'name_requ');
            return false;
        }

        $group_id = $ath->Group->getID();

     // first delete any report field entries for this report
        $res = db_query("DELETE FROM artifact_report_field WHERE report_id=" . db_ei($this->report_id));

        $res = db_query("UPDATE artifact_report
                         SET user_id='" . db_ei($user_id) . "',name='" . db_es($name) . "', description='" . db_es($description) . "',scope='" . db_es($scope) . "',is_default='" . db_es($is_default) . "'
                         WHERE report_id=" . db_ei($this->report_id));

        // set other reports as not default report
        if ($is_default == 1) {
               $res = db_query("UPDATE artifact_report SET is_default=0 WHERE report_id <>" . db_ei($this->report_id) . " AND group_artifact_id=" . db_ei($this->group_artifact_id));
        }
        $this->user_id = $user_id;
        $this->name = $name;
        $this->description = $description;
        $this->scope = $scope;
        $this->is_default = $is_default;
        $this->fields = array();
        return true;
    }

    /**
     *    delete - use this to remove a Report from the database.
     *
     *    @return true on success, false on failure.
     */
    public function delete()
    {
        global $ath;

     // first delete any report field entries for this report
        $res = db_query("DELETE FROM artifact_report_field WHERE report_id=" . db_ei($this->report_id));

     // then delete the report entry item
        $res = db_query("DELETE FROM artifact_report WHERE report_id=" . db_ei($this->report_id));

        $this->name = '';
        $this->description = '';
        $this->scope = '';
        $this->is_default = '';
        $this->fields = array();
        return true;
    }

    /**
     *  updateDefaultReport - use this to set the report to default
     *  @return true on success false on failure
     */

    public function updateDefaultReport()
    {
        if ($GLOBALS['ath']->userIsAdmin()) {
            db_query("UPDATE artifact_report SET is_default=1 WHERE report_id =" . db_ei($this->report_id) . " AND group_artifact_id=" . db_ei($this->group_artifact_id));
            db_query("UPDATE artifact_report SET is_default=0 WHERE report_id <>" . db_ei($this->report_id) . " AND group_artifact_id=" . db_ei($this->group_artifact_id));
            return true;
        }
        return false;
    }

    /**
     *    create - use this to create a new Report in the database.
     *
     *    @param    string    The report name.
     *    @param    string    The report description.
     *    @return id on success, false on failure.
     */
    public function create($user_id, $name, $description, $scope, $is_default)
    {
        global $ath,$Language;
     /*$perm = $ath->Group->getPermissionFromId( $user_id);

      if (!$perm || !is_object($perm) || !$perm->isArtifactAdmin()) {
       $this->setError('ArtifactReport: Permission Denied');
       return false;
      }
    */
        if (!$name || !$description || !$scope) {
            $this->setError('ArtifactReport: ' . $Language->getText('tracker_common_report', 'name_requ'));
            return false;
        }

        $group_id = $ath->Group->getID();
        $atid = $ath->getID();

        $sql = 'INSERT INTO artifact_report (group_artifact_id,user_id,name,description,scope,is_default) ' .
        "VALUES ('" . db_ei($atid) . "','" . db_ei($user_id) . "','" . db_es($name) . "'," .
        "'" . db_es($description) . "','" . db_es($scope) . "','" . db_ei($is_default) . "')";
     //echo $sql;

        $res = db_query($sql);

        $report_id = db_insertid($res, 'artifact_report', 'report_id');
        if (($is_default == 1) && ($report_id)) {
            db_query("UPDATE artifact_report SET is_default=0 WHERE report_id <>" . db_ei($report_id) . " AND group_artifact_id=" . db_ei($this->group_artifact_id));
        }
        if (!$res || !$report_id) {
            $this->setError('ArtifactReport: ' . db_error());
            return false;
        } else {
            $this->report_id = $report_id;
            $this->description = $description;
            $this->name = $name;
            $this->scope = $scope;
            $this->is_default = $is_default;

            $this->fields = array();
            return true;
        }
    }

    public function add_report_field($field_name, $show_on_query, $show_on_result, $place_query, $place_result, $col_width)
    {
        $sql = 'INSERT INTO artifact_report_field (report_id, field_name,' .
        'show_on_query,show_on_result,place_query,place_result,col_width) VALUES ';

        $sql .= "(" . db_ei($this->report_id) . ",'" . db_es($field_name) . "'," . db_ei($show_on_query) . "," . db_ei($show_on_result) . "," .
        db_ei($place_query, CODENDI_DB_NULL) . "," . db_ei($place_result, CODENDI_DB_NULL) . "," . db_ei($col_width, CODENDI_DB_NULL) . ")";
      //echo $sql.'<br>';
        $res = db_query($sql);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    public function toggleFieldColumnUsage($field_name)
    {
        $sql = "UPDATE artifact_report_field
                SET show_on_result = 1 - show_on_result
                WHERE report_id  = " .  db_ei($this->report_id) . "
                  AND field_name = '" . db_es($field_name) . "'";
        db_query($sql);
    }
    public function toggleFieldQueryUsage($field_name)
    {
        $sql = "UPDATE artifact_report_field
                SET show_on_query = 1 - show_on_query
                WHERE report_id  = " .  db_ei($this->report_id) . "
                  AND field_name = '" . db_es($field_name) . "'";
        db_query($sql);
    }
    /**
     *    fetchData - re-fetch the data for this ArtifactReport from the database.
     *
     *    @param    int        The report ID.
     *    @return bool success.
     */
    public function fetchData($report_id)
    {
        global $Language;

     // Read the report infos
        $sql = "SELECT * FROM artifact_report " .
         "WHERE report_id=" . db_ei($report_id);
     //echo $sql.'<br>';
        $res = db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            $this->setError('ArtifactReport: ' . $Language->getText('tracker_common_report', 'not_found'));
            return false;
        }
        $data_array = db_fetch_array($res);
        $this->name = $data_array['name'];
        $this->description = $data_array['description'];
        $this->scope = $data_array['scope'];
        $this->is_default = $data_array['is_default'];
        $this->report_id = $report_id;

     // Read the fields infos
        $res = db_query("SELECT * FROM artifact_report_field " .
        "WHERE report_id=" . db_ei($report_id));
        if (!$res || db_numrows($res) < 1) {
            $this->setError('ArtifactReport:fetchData');
            return false;
        }

     // Store the fields in $this->fields
        $this->fields = array();
        $i = 0;
        while ($field_array = db_fetch_array($res)) {
         // ArtifactReportField inherits from ArtifactField
      // So we need to retreive ArtifactField values
            $this->fields[$field_array['field_name']] = new ArtifactReportField();
            $obj = $this->fields[$field_array['field_name']];
            $obj->fetchData($this->group_artifact_id, $field_array['field_name']);
            $obj->setReportFieldsFromArray($field_array);
            $this->fields[$field_array['field_name']] = $obj;
            $i++;
        }
        return true;
    }

    /**
     *    Retrieve the artifact report list order by scope
     *
     *    @param    group_artifact_id: the artifact type
     *
     *    @return    array
     */
    public function getReports($group_artifact_id, $user_id)
    {
        // If user is unknown then get only project-wide and system wide reports
        // else get personal reports in addition  project-wide and system wide.
        $sql = 'SELECT report_id,name,description,scope,is_default FROM artifact_report WHERE ';
        if (!$user_id || ($user_id == 100)) {
            $sql .= "(group_artifact_id=" . db_ei($group_artifact_id) . " AND scope='P') OR scope='S' " .
            'ORDER BY report_id';
        } else {
            $sql .= "(group_artifact_id=" . db_ei($group_artifact_id) . " AND (user_id=" . db_ei($user_id) . " OR scope='P')) OR " .
            "scope='S' ORDER BY scope,report_id";
        }
        //echo "DBG sql report = $sql";
        return db_query($sql);
    }

    /**
     * Return the field list used for the query report
     *
     * @return array
     */
    public function getQueryFields()
    {
        $query_fields = array();

        if (count($this->fields) == 0) {
            return $query_fields;
        }

        foreach ($this->fields as $key => $field) {
            if (($field->isShowOnQuery()) && ($field->isUsed())) {
                if ($field->userCanRead($GLOBALS['group_id'], $this->group_artifact_id)) {
                    $query_fields[$key] = $field;
                }
            }
        }

        uasort($query_fields, 'cmp_place_query');
        return $query_fields;
    }

    /**
     * Return the field list used to display the report
     *
     * @return array
     */
    public function getResultFields()
    {
        $result_fields = array();

        if (count($this->fields) == 0) {
            return $result_fields;
        }

        foreach ($this->fields as $key => $field) {
            if (($field->isShowOnResult()) && ($field->isUsed())) {
                if ($field->userCanRead($GLOBALS['group_id'], $this->group_artifact_id)) {
                    $result_fields[$key] = $field;
                }
            }
        }

        uasort($result_fields, 'cmp_place_result');
        return $result_fields;
    }

    /**
     * Return all the fields list used for the report
     *
     * @return array
     */
    public function getSortedFields()
    {
        global $group;
        $result_fields = array();

        if (count($this->fields) == 0) {
            return $result_fields;
        }

        foreach ($this->fields as $key => $field) {
            if ($field->isUsed()) {
                $result_fields[$key] = $field;
            }
        }

        uasort($result_fields, 'cmp_place_result');
        return $result_fields;
    }

    /**
     * Return the field list used to display the report (SelectBox or MultiBox type)
     *
     * @return array
     */
    public function getSingleMultiBoxFields()
    {
        $result_fields = array();

        if (count($this->fields) == 0) {
            return $result_fields;
        }

        foreach ($this->fields as $key => $field) {
            if (($field->isShowOnResult()) && ($field->getUseIt() == 1) && ($field->isMultiSelectBox() || $field->isSelectBox())) {
                $result_fields[$key] = $field;
            }
        }

        return $result_fields;
    }

    /**
     *
     * @param aids: the list (array) of artifact ids that match the query given in $prefs
     * Returns the number of rows for the current query, filtered with permissions
     *
     *
     * @return int
     */
    public function selectReportItems($prefs, $morder, $advsrch, &$aids)
    {
            global $ath;
            $this->getQueryElements($prefs, $advsrch, $from, $where);

            $um = UserManager::instance();
            $u  = $um->getCurrentUser();
            $instances = array('artifact_type' => $this->group_artifact_id);
            $group_id = $ath->Group->getID();
            $ugroups = $u->getUgroups($group_id, $instances);

            $pm          = PermissionsManager::instance();
            $permissions = $pm->getPermissionsAndUgroupsByObjectid($this->group_artifact_id);

        if (!$u->isSuperUser() && !$u->isTrackerAdmin($group_id, $this->group_artifact_id)) {
            //artifact permissions
            $from  .= " LEFT JOIN permissions
                             ON (permissions.object_id = CONVERT(a.artifact_id USING utf8)
                                 AND
                                 permissions.permission_type = 'TRACKER_ARTIFACT_ACCESS') ";
            $where .= " AND (a.use_artifact_permissions = 0
                             OR
                             (
                                 permissions.ugroup_id IN (" . implode(',', $ugroups) . ")
                             )
                       ) ";
        }

            $aids = array();
            //Does the user member of at least one group which has ACCESS_FULL ?
        if ($u->isSuperUser() || $u->isTrackerAdmin($group_id, $this->group_artifact_id) || (isset($permissions['TRACKER_ACCESS_FULL']) && count(array_intersect($ugroups, $permissions['TRACKER_ACCESS_FULL'])) > 0)) {
            $sql = "SELECT a.artifact_id " .
                   $from . " " .
                   $where .
                   "";
            $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
        } else {
            $static_ugroups  = $u->getStaticUgroups($group_id);
            $dynamic_ugroups = $u->getDynamicUgroups($group_id, $instances);
            //Does the user member of at least one group which has ACCESS_SUBMITTER ?
            if (isset($permissions['TRACKER_ACCESS_SUBMITTER']) && count(array_intersect($ugroups, $permissions['TRACKER_ACCESS_SUBMITTER'])) > 0) {
                // {{{ The static ugroups
                if (count(array_intersect($static_ugroups, $permissions['TRACKER_ACCESS_SUBMITTER'])) > 0) {
                    $sql = "SELECT a.artifact_id " .
                           $from . " , ugroup_user uu " .
                           $where .
                           "  AND a.submitted_by = uu.user_id " .
                           "  AND uu.ugroup_id IN (" . db_es(implode(', ', $static_ugroups)) . ") " .
                           "";
                    $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                }
                // }}}

                // {{{ tracker_admins
                if (in_array($GLOBALS['UGROUP_TRACKER_ADMIN'], $dynamic_ugroups) &&
                in_array($GLOBALS['UGROUP_TRACKER_ADMIN'], $permissions['TRACKER_ACCESS_SUBMITTER'])) {
                    $sql = "SELECT a.artifact_id " .
                           $from . " , artifact_perm p " .
                           $where .
                           "  AND a.submitted_by = p.user_id " .
                           "  AND p.group_artifact_id = " . db_ei($this->group_artifact_id) . " " .
                           "  AND p.perm_level >= 2 " .
                           "";
                    $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                }
                //}}}
                // {{{ project_members
                if (in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $dynamic_ugroups) &&
                in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $permissions['TRACKER_ACCESS_SUBMITTER'])) {
                    $sql = "SELECT a.artifact_id " .
                           $from . " , user_group ug " .
                           $where .
                           "  AND a.submitted_by = ug.user_id " .
                           "  AND ug.group_id = " . db_ei($GLOBALS['group_id']) . " " .
                           "";
                    $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                }
                //}}}
                // {{{ project_admins
                if (in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $dynamic_ugroups) &&
                in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $permissions['TRACKER_ACCESS_SUBMITTER'])) {
                    $sql = "SELECT a.artifact_id " .
                           $from . " , user_group ug " .
                           $where .
                           "  AND a.submitted_by = ug.user_id " .
                           "  AND ug.group_id = " . db_ei($GLOBALS['group_id']) . " " .
                           "  AND ug.admin_flags = 'A' " .
                           "";
                    $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                }
                //}}}
            }

            //Does the user member of at least one group which has ACCESS_ASSIGNEE ?
            if (isset($permissions['TRACKER_ACCESS_ASSIGNEE']) && count(array_intersect($ugroups, $permissions['TRACKER_ACCESS_ASSIGNEE'])) > 0) {
                //Get only once the field_id of assigned_to/multi_assigned_to
                $field_dao = new ArtifactFieldDao(CodendiDataAccess::instance());
                $dar = $field_dao->searchAssignedToFieldIdByArtifactTypeId($this->group_artifact_id);
                $assigned_to = array();
                while ($row = $dar->getRow()) {
                    $assigned_to[] = $row['field_id'];
                }
                if (count($assigned_to) > 0) {
                    // {{{ The static ugroups
                    if (count(array_intersect($static_ugroups, $permissions['TRACKER_ACCESS_ASSIGNEE'])) > 0) {
                        $sql = "SELECT a.artifact_id " .
                               $from . " , artifact_field_value afv, ugroup_user uu " .
                               $where .
                               "  AND a.artifact_id = afv.artifact_id " .
                               "  AND afv.field_id IN (" . db_es(implode(', ', $assigned_to)) . ") " .
                               "  AND afv.valueInt = uu.user_id " .
                               "  AND uu.ugroup_id IN (" . db_es(implode(', ', $static_ugroups)) . ") " .
                               "";
                        $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                    }
                    // }}}

                    // {{{ tracker_admins
                    if (in_array($GLOBALS['UGROUP_TRACKER_ADMIN'], $dynamic_ugroups) &&
                    in_array($GLOBALS['UGROUP_TRACKER_ADMIN'], $permissions['TRACKER_ACCESS_ASSIGNEE'])) {
                        $sql = "SELECT a.artifact_id " .
                               $from . " , artifact_field_value afv, artifact_perm p " .
                               $where .
                               "  AND a.artifact_id = afv.artifact_id " .
                               "  AND afv.field_id IN (" . db_es(implode(', ', $assigned_to)) . ") " .
                               "  AND afv.valueInt = p.user_id " .
                               "  AND p.group_artifact_id = " . db_ei($this->group_artifact_id) . " " .
                               "  AND p.perm_level >= 2 " .
                               "";
                        $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                    }
                    //}}}
                    // {{{ project_members
                    if (in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $dynamic_ugroups) &&
                    in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $permissions['TRACKER_ACCESS_ASSIGNEE'])) {
                        $sql = "SELECT a.artifact_id " .
                               $from . " , artifact_field_value afv, user_group ug " .
                               $where .
                               "  AND a.artifact_id = afv.artifact_id " .
                               "  AND afv.field_id IN (" . db_es(implode(', ', $assigned_to)) . ") " .
                               "  AND afv.valueInt = ug.user_id " .
                               "  AND ug.group_id = " . db_ei($GLOBALS['group_id']) . " " .
                               "";
                        $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                    }
                    //}}}
                    // {{{ project_admins
                    if (in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $dynamic_ugroups) &&
                    in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $permissions['TRACKER_ACCESS_ASSIGNEE'])) {
                        $sql = "SELECT a.artifact_id " .
                               $from . " , artifact_field_value afv, user_group ug " .
                               $where .
                               "  AND a.artifact_id = afv.artifact_id " .
                               "  AND afv.field_id IN (" . db_es(implode(', ', $assigned_to)) . ") " .
                               "  AND afv.valueInt = ug.user_id " .
                               "  AND ug.group_id = " . db_ei($GLOBALS['group_id']) . " " .
                               "  AND ug.admin_flags = 'A' " .
                               "";
                        $aids = array_merge($aids, $this->_ExecuteQueryForSelectReportItems($sql));
                    }
                    //}}}
                }
            }
        }
            $aids = array_unique($aids);
            return count($aids);
    }

    public function _ExecuteQueryForSelectReportItems($sql)
    {
        $ret = array();
        $res = db_query($sql);
        while ($arr = db_fetch_array($res)) {
            $ret[] = $arr['artifact_id'];
        }
        return $ret;
    }

    /**
     * Return all the fields list used for the report
     *
     * @return string
     */
    public function createQueryReport($prefs, $morder, $advsrch, $offset, $chunksz, $aids)
    {
        $select   = null;
        $from     = null;
        $where    = null;
        $order_by = null;

        $this->getResultQueryElements($prefs, $morder, $advsrch, $aids, $select, $from, $where, $order_by);
        $limit = "";
     // Final query
        if ($offset != 0 || $chunksz != 0) {
            // there is no limit only in case where offset==0 and chunksz==0, in any other case, there is a limit
            $limit = " LIMIT " . db_ei($offset) . "," . db_ei($chunksz);
        }

        //We need group by due to multi assign-to. However, the performances with big trackers are really bad.
        $sql = $select . " " . $from . " " . $where . " GROUP BY artifact_id " . $order_by . $limit;
         //echo "<DBG> query=".$sql."<br>";

        return $sql;
    }

    /**
     * Return if the value is ANY
     *
     * The ANY value is 0. The simple fact that
     * ANY (0) is one of the value means it is Any even if there are
     * other non zero values in the  array
     *
     * @return bool
     */
    public function isvarany($var)
    {
        if (is_array($var)) {
            foreach ($var as $v) {
                if ($v == 0) {
                    return true;
                }
            }
            return false;
        } else {
            return ($var == 0);
        }
    }


    /**
     * Return the value to find for a field, for the current query
     *
     * @param field: the field object
     * @param prefs: field values array (HTTP GET variable)
     * @param field_value: the field name
     * @param advsrch: advance search or simple search
         * @param notany: is true if the value of the field is not "Any"
     *
     * @return string
     */
    public function getValuesWhereClause($field, $prefs, $field_name, $advsrch, &$notany)
    {
        $notany = true;
         $where = '';

     //echo $field_name."->prefs[".$field->getName()."]=".$prefs[$field->getName()][0]."<br>";
        if (($field->isSelectBox() || $field->isMultiSelectBox()) && (isset($prefs[$field->getName()]) && !$this->isvarany($prefs[$field->getName()]))) {
            // Only select box criteria to where clause if argument is not ANY
            return " AND " . $field_name . " IN (" . db_es(implode(",", $prefs[$field->getName()])) . ") ";
        } elseif ($field->isDateField() && (
            ((isset($prefs[$field->getName()]) && $prefs[$field->getName()][0]) ||
            (isset($prefs[$field->getName() . '_end']) && $prefs[$field->getName() . '_end'][0])))) {
      // transform a date field into a unix time and use <, > or =
            list($time,$ok) = util_date_to_unixtime($prefs[$field->getName()][0]);

            if ($advsrch) {
                list($time_end,$ok_end) = util_date_to_unixtime($prefs[$field->getName() . '_end'][0]);
                if ($ok) {
                    list($year,$month,$day) = util_date_explode($prefs[$field->getName()][0]);
                    $time_after = mktime(0, 0, 0, $month, $day, $year);
                    $where .= " AND " . $field_name . " >= " . $time_after;
                }

                if ($ok_end) {
                    list($year,$month,$day) = util_date_explode($prefs[$field->getName() . '_end'][0]);
                    $time_before = mktime(23, 59, 59, $month, $day, $year);
                    $where .= " AND " . $field_name . " <= " . $time_before;
                }
            } else {
                if (isset($prefs[$field->getName()][1])) {
                    $operator = $prefs[$field->getName()][1];
                } else {
                    $operator = $prefs[$field->getName() . '_op'][0];
                }

                   // '=' means that day between 00:00 and 23:59
                if ($operator == '=') {
                    list($year,$month,$day) = util_date_explode($prefs[$field->getName()][0]);
                    $time_end = mktime(23, 59, 59, $month, $day, $year);
                    $where = " AND " . $field_name . " >= " . $time . " AND " . $field_name . " <= " . $time_end;
                } elseif ($operator == '>') {
                    list($year,$month,$day) = util_date_explode($prefs[$field->getName()][0]);
                    $time_after = mktime(0, 0, 0, $month, $day + 1, $year);
                    $where = " AND " . $field_name . " " . $operator . "=" . $time_after;
                } elseif ($operator == '<') {
                    list($year,$month,$day) = util_date_explode($prefs[$field->getName()][0]);
                    $time_before = mktime(23, 59, 59, $month, $day - 1, $year);
                    $where = " AND " . $field_name . " " . $operator . "=" . $time_before;
                }
            }

      // Always exclude undefined dates (0)
            $where .= " AND " . $field_name . " <> 0 ";

            return $where;
        } elseif (($field->isTextField() || $field->isTextArea())
                        && isset($prefs[$field->getName()][0]) && $prefs[$field->getName()][0]) {
      // It's a text field accept. Process INT or TEXT,VARCHAR fields differently
            return " AND " . $field->buildMatchExpression($field_name, $prefs[$field->getName()][0]);
        }
        $notany = false;
    }

    /**
     * Return the fields for the order by statement, for the current query
     *
     * @return array
     */
    public function getFieldsOrder($morder)
    {
        global $art_field_fact;

        $fields_order = array();

        $arr = explode(',', $morder);
        foreach ($arr as $attr) {
            $key = substr($attr, 0, (strlen($attr) - 1));
            if (isset($this->fields[$key]) && $this->fields[$key]->isUsed() && ('severity' == $key || $this->fields[$key]->isShowOnResult())) {
                preg_match("/\s*([^<>]*)([<>]*)/", $attr, $match);
                list(,$mattr,$mdir) = $match;
              //echo "<br>DBG \$mattr=$mattr,\$mdir=$mdir";
                if (($mdir == '>') || (!isset($mdir))) {
                    $fields_order[$mattr] = "ASC";
                } else {
                    $fields_order[$mattr] = "DESC";
                }
            }
        }

        return $fields_order;
    }

    /**
     * Return the different elements for building the current query
     *
     * @param countonly: specifies whether the query is only used to count the number of
     *                   queried artifacts. If we only want to count, we don't need to
     *                   build a huge join for fields that are not determining the serach
     *                   result
     * @param aids: the artifact ids that have been calculated in a former select and that
     *              correspond to the query given in prefs. By using the aids we do not need
     *              to use the query constraints on the different artifact fields => lesser
     *              tables to JOIN
     * @param select: the select value
     * @param from: the from value
     * @param where: the where value
     * @param order_by: the order by value
     *
     * @return void
     */
    public function getQueryElements($prefs, $advsrch, &$from, &$where)
    {
        global $art_field_fact;
      // NOTICE
      //
      // We can't use left join because the performs are very bad.
      // Take care: Big organisations (like FT) adore having many, many fields in their trackers
      // So the restriction to this: all fields used in the query must have a value.
      // That involves artifact creation or artifact admin (add a field) must create
      // empty records with default values for fields which haven't a value (from the user).
      /* The query must be something like this :
      FROM artifact a
                             JOIN artifact_field_value v1 ON (v1.artifact_id=a.artifact_id)
                             JOIN artifact_field_value v2 ON (v2.artifact_id=a.artifact_id)
                             JOIN artifact_field_value v3 ON (v2.artifact_id=a.artifact_id)
                             JOIN user u3 ON (v3.valueInt = u3.user_id)
                             JOIN user u
      WHERE a.group_artifact_id = 100 and
      v1.field_id=101 and
      v2.field_id=103 and
      v3.field_id=104 and
      a.submitted_by = u.user_id

      */

      // Get the fields sorted by the result order
        $fields = $this->getSortedFields();

        $count = 1;
        $status_id_ok = 0;
        $assigned_to_ok = 0;
        $multi_assigned_to_ok = 0;

        $from = "FROM artifact a";
        $where = "WHERE a.group_artifact_id = " . db_ei($this->group_artifact_id);
        foreach ($fields as $field) {
        //echo $field->getName()."-".$field->getID()."<br>";

            if ($field->isShowOnQuery()) {
              // If the field is a standard field ie the value is stored directly into the artifact table (severity, artifact_id, ...)
                if ($field->isStandardField()) {
                    $where .= $this->getValuesWhereClause($field, $prefs, "a." . $field->getName(), $advsrch, $notany);

                    if ($field->getName() == "status_id") {
                        $status_id_ok = 1;
                    }
                } else {
          // The field value is stored into the artifact_field_value table
          // So we need to add a new join
                    $where .= $this->getValuesWhereClause($field, $prefs, "v" . $count . "." . $field->getValueFieldName(), $advsrch, $notany);

                    if ($notany) {
                        $from .= " JOIN artifact_field_value v" . $count . " ON (v" . $count . ".artifact_id=a.artifact_id" .
                        " and v" . $count . ".field_id=" . db_ei($field->getID()) . ")";

                        $count++;
                    }

          // special case for assigned_to/multi_assigned_to fields or status_id field that can also be set
          // in prefs through My or Open link but which are not necessarily in the ShowOnQuery fields of this report
                    if ($field->getName() == "assigned_to") {
                        $assigned_to_ok = 1;
                    } elseif ($field->getName() == "multi_assigned_to") {
                        $multi_assigned_to_ok = 1;
                    }
                }
            }
        }

        if (isset($prefs['assigned_to']) && !$assigned_to_ok) {
            $field = $art_field_fact->getFieldFromName('assigned_to');
            if ($field) {
                  $where .= $this->getValuesWhereClause($field, $prefs, "v" . $count . "." . $field->getValueFieldName(), $advsrch, $notany);

                if ($notany) {
                    $from .= " JOIN artifact_field_value v" . $count . " ON (v" . $count . ".artifact_id=a.artifact_id" .
                    " and v" . $count . ".field_id=" . db_ei($field->getID()) . ")";

                    $count++;
                }
            }
        } elseif (isset($prefs['multi_assigned_to']) && !$multi_assigned_to_ok) {
            $field = $art_field_fact->getFieldFromName('multi_assigned_to');
            if ($field) {
                  $where .= $this->getValuesWhereClause($field, $prefs, "v" . $count . "." . $field->getValueFieldName(), $advsrch, $notany);

                if ($notany) {
                    $from .= " JOIN artifact_field_value v" . $count . " ON (v" . $count . ".artifact_id=a.artifact_id" .
                    " and v" . $count . ".field_id=" . db_ei($field->getID()) . ")";

                    $count++;
                }
            }
        }
        if (isset($prefs['status_id']) && !$status_id_ok) {
            $field = $art_field_fact->getFieldFromName('status_id');
            if ($field) {
                  $where .= $this->getValuesWhereClause($field, $prefs, "a." . $field->getName(), $advsrch, $notany);
            }
        }
    }


    /**
     * Return the different elements for building the current query
     *
     * @param aids: the artifact ids that have been calculated in a former select and that
     *              correspond to the query given in prefs. By using the aids we do not need
     *              to use the query constraints on the different artifact fields => lesser
     *              tables to JOIN
     * @param select: the select value
     * @param from: the from value
     * @param where: the where value
     * @param order_by: the order by value
     *
     * @return void
     */
    public function getResultQueryElements($prefs, $morder, $advsrch, $aids, &$select, &$from, &$where, &$order_by)
    {
      // NOTICE
      //
      // We can't use left join because the performs are very bad.
      // Take care: Big organisations (like FT) adore having many, many fields in their trackers
      // So the restriction to this: all fields used in the query must have a value.
      // That involves artifact creation or artifact admin (add a field) must create
      // empty records with default values for fields which haven't a value (from the user).
      /* The query must be something like this :
                SELECT a.artifact_id,u.user_name,v1.valueInt,v2.valueText,u3.user_name
      FROM artifact a
                             JOIN artifact_field_value v1 ON (v1.artifact_id=a.artifact_id)
                             JOIN artifact_field_value v2 ON (v2.artifact_id=a.artifact_id)
                             JOIN artifact_field_value v3 ON (v2.artifact_id=a.artifact_id)
                             JOIN user u3 ON (v3.valueInt = u3.user_id)
                             JOIN user u
      WHERE a.group_artifact_id = 100 and
      v1.field_id=101 and
      v2.field_id=103 and
      v3.field_id=104 and
      a.submitted_by = u.user_id
      group by a.artifact_id
      order by v3.valueText,v1.valueInt
      */

      // Get the fields sorted by the result order
        $fields = $this->getSortedFields();

        $count = 1;

        $select = "SELECT STRAIGHT_JOIN a.severity as severity_id, a.artifact_id as artifact_id, ";
        $from = "FROM artifact a";
        $where = "WHERE a.group_artifact_id = " . db_ei($this->group_artifact_id);

      //add directly the aids concerned by the query given in prefs
        if ($aids) {
            $where .= " AND a.artifact_id IN (" . db_es(implode(",", $aids)) . ")";
        }

        $order_by = "ORDER BY ";

      // Get the order fields
        $fields_order = $this->getFieldsOrder($morder);
      //echo "morder=$morder, fields_order: ".implode(",",array_keys($fields_order))."<br>\n";

        $fields_order_result = array();

        $select_count = 0;

        if (count($fields) == 0) {
            return;
        }

        foreach ($fields as $field) {
        //echo $field->getName()."-".$field->getID()."<br>";

        // If the field is a standard field ie the value is stored directly into the artifact table (severity, artifact_id, ...)
            if ($field->isStandardField()) {
              // Build the where
              // if we now already the affected aids we do not need to integrate the query constraints into the SQL query
                if ($field->isShowOnQuery() && !$aids) {
                    $where .= $this->getValuesWhereClause($field, $prefs, "a." . $field->getName(), $advsrch, $notany);
                }

                if (($field->isShowOnResult()) || ($field->getName() == "severity")) {
                    if ($select_count != 0) {
                        $select .= ",";
                        $select_count ++;
                    } else {
                        $select_count = 1;
                    }

          // Special case for fields which are user name
                    if (($field->isUsername()) && (!$field->isSelectBox()) && (!$field->isMultiSelectBox())) {
                        $select .= " u.user_name as " . $field->getName();
                        $from .= " JOIN user u ON (u.user_id=a." . $field->getName() . ")";
                    } else {
                        $select .= " a." . $field->getName();
                    }
                }

              // Set the fields_order_result array to    build the order_by
                if (isset($fields_order[$field->getName()]) && $fields_order[$field->getName()]) {
                    if (($field->isUsername()) && (!$field->isSelectBox()) && (!$field->isMultiSelectBox())) {
                          $fields_order_result[$field->getName()] = "u.user_name";
                    } else {
                          $fields_order_result[$field->getName()] = "a." . $field->getName();
                    }
                }
            } else {
              // The field value is stored into the artifact_field_value table
              // So we need to add a new join

              // Build the where
                if ($field->isShowOnQuery() && !$aids) {
                    $where .= $this->getValuesWhereClause($field, $prefs, "v" . $count . "." . $field->getValueFieldName(), $advsrch, $notany);
                }

                if ($field->isShowOnResult() ||
                ($field->isShowOnQuery() && !$aids && $notany)) {
                    $from .= " JOIN artifact_field_value v" . $count . " ON (v" . $count . ".artifact_id=a.artifact_id" .
                    " and v" . $count . ".field_id=" . db_ei($field->getID()) . ")";
                }

                if ($field->isShowOnResult()) {
                    if ($select_count != 0) {
                          $select .= ",";
                          $select_count ++;
                    } else {
                          $select_count = 1;
                    }

          // Special case for fields which are user name
                    if ($field->isUsername()) {
                          $select .= " u" . $count . ".user_name as " . $field->getName();
                          $from .= " JOIN user u" . $count . " ON (v" . $count . "." . $field->getValueFieldName() . " = u" . $count . ".user_id)";
                    } elseif ($field->isSelectBox() || $field->isMultiSelectBox()) {
                          $select .= " v" . $count . "." . $field->getValueFieldName() . " as " . $field->getName();
                          $from .= " LEFT JOIN artifact_field_value_list v" . $count . "_val"
                            . " ON (v" . $count . "_val.group_artifact_id=" . db_ei($this->group_artifact_id)
                            . " and v" . $count . "_val.field_id=" . db_ei($field->getID())
                            . " and v" . $count . "_val.value_id=v" . $count . "." . $field->getValueFieldName()
                            . ")";
                    } else {
                          $select .= " v" . $count . "." . $field->getValueFieldName() . " as " . $field->getName();
                    }
                }

          // Set the fields_order_result array to    build the order_by
                if (isset($fields_order[$field->getName()]) && $fields_order[$field->getName()]) {
                    //if ( ($field->isUsername())&&(!$field->isSelectBox())&&(!$field->isMultiSelectBox()) ) {
                    if ($field->isUsername()) {
                        $fields_order_result[$field->getName()] = "u" . $count . ".user_name";
                    } else {
                        if ($field->isSelectBox() || $field->isMultiSelectBox()) {
                            $fields_order_result[$field->getName()] = "v" . $count . "_val.order_id";
                        } else {
                            $fields_order_result[$field->getName()] = "v" . $count . "." . $field->getValueFieldName();
                        }
                    }
                }

                $count++;
            }
        }

      // Build the order_by using the fields_order_result array
        if (count($fields_order) > 0) {
            $i = 1;
            foreach ($fields_order as $key => $field) {
                if ($i > 1) {
                    $order_by .= ", ";
                }

                  //little special case for severity that is not always in the queryable fields of a report
                  //but that should be there on default ...
                if ($key == 'severity') {
                    // we use modulo 100 to prevent bad sort when none value is allowed in severity field (none value=100).
                    $order_by .= "a.severity % 100 " . $fields_order[$key];
                } else {
                    $order_by .= $fields_order_result[$key] . " " . $fields_order[$key];
                }
                  $i ++;
            }
        } else {
            $order_by = "";
        }
    }


    /**
     * Return the query result in a multi array
     * For SelectBox or MultiBox, we retreive the label value
     *
     * @param query: the SQL query
     *
     * @return array
     */
    public function getResultQueryReport($query)
    {
        $fields_sb = $this->getSingleMultiBoxFields();

        $result = db_query($query);
        $res = array();
        for ($i = 0; $i < db_numrows($result); $i++) {
            $res[$i] = db_fetch_array($result);
            if ($res[$i]['artifact_id']) {
                foreach ($fields_sb as $field_name => $field) {
                    $values = array();
                    if ($field->isStandardField()) {
                        $values[] = $res[$i][$field_name];
                    } else {
                        $values = $field->getValues($res[$i]['artifact_id']);
                    }

                    $label_values = $field->getLabelValues($this->group_artifact_id, $values);
                    $res[$i][$field_name] = join(", ", $label_values);
                }
            }
        }

        return $res;
    }

    // getters for the ArtifactReport class

    /**
     *      getID - get this ArtifactReportID.
     *
     *      @return    int    The report_id #.
     */

    public function getID()
    {
        return $this->report_id;
    }

    /**
     *      getArtifactTypeID - get this ArtifactTypeID.
     *
     *      @return    int    The group_artifact_id #.
     */

    public function getArtifactTypeID()
    {
        return $this->group_artifact_id;
    }

    /**
     *      getName - get Name of this report.
     *
     *      @return    string    The report name.
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     *      getDescription - get Description of this report.
     *
     *      @return    string    The report description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *      getScope - get Scope of this report.
     *
     *      @return    string    The report scope ('S': system, 'P': project).
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * is_default - get is_default value
     *
     * @return string  The default report value.
     */
    public function isDefault()
    {
        return $this->is_default;
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

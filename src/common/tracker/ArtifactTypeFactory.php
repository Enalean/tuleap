<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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


class ArtifactTypeFactory //phpcs:ignore
{
    /**
     * The Group object.
     *
     * @var     object  $Group.
     */
    public $Group;

    /**
     * The ArtifactTypes array.
     *
     * @var     array    ArtifactTypes.
     */
    public $ArtifactTypes;
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
     *    @param    object    The Group object to which this ArtifactTypeFactory is associated
     *    @return bool success.
     */
    public function __construct($Group)
    {
        if ($Group) {
            if ($Group->isError()) {
                $this->setError('ArtifactTypeFactory:: ' . $Group->getErrorMessage());
                return false;
            }
            $this->Group = $Group;
        }

        return true;
    }

    /**
     *    getGroup - get the Group object this ArtifactType is associated with.
     *
     *    @return    object    The Group object.
     */
    public function getGroup()
    {
        return $this->Group;
    }

    /**
     *    getStatusIdCount - return a array of each status_id count.
     *
     *    @param    group_artifact_id
     *
     *    @return    array of counts
     */
    public function getStatusIdCount($group_artifact_id)
    {
        $count_array = [];
        $sql         = 'select status_id,count(*) from artifact where group_artifact_id = ' . db_ei($group_artifact_id) .
        ' group by status_id';
        $result      = db_query($sql);

        $rows = db_numrows($result);

        if (! $result || $rows < 1) {
            $this->setError('None Found ' . db_error());
            return false;
        } else {
            $count_array['count'] = 0;
            while ($arr = db_fetch_array($result)) {
                if ($arr['status_id'] == 1) {
                    $count_array['open_count'] = $arr[1];
                }
                $count_array['count'] += $arr[1];
            }
            return $count_array;
        }
    }

    /**
     * Check if the name of the tracker is already used
     *@param string $name the name of the tracker we are lokking for
     * @param int $group_id th ID of the group
     * @return bool
     */
    public function isNameExists($name, $group_id)
    {
        $reference_dao = $this->getArtifactGroupListDao();
        $dar           = $reference_dao->searchNameByGroupId($group_id);
        while ($row = $dar->getRow()) {
            if ($name == $row['name']) {
                return true;
            }
        }
        return false;
    }

    /**
     *    create - use this to create a new ArtifactType in the database.
     *
     *  @param  group_id: the group id of the new tracker
     *    @param    group_id_template: the template group id (used for the copy)
     *    @param    atid_template: the template artfact type id
     *    @param    name: the name of the new tracker
     *    @param    description: the description of the new tracker
     *    @param    itemname: the itemname of the new tracker
     *    @return int|false id on success, false on failure.
     */
    public function create($group_id, $group_id_template, $atid_template, $name, $description, $itemname, $ugroup_mapping = false, &$report_mapping = [])
    {
        global $Language;

        if (! $name || ! $description || ! $itemname || trim($name) == '' || trim($description) == '' || trim($itemname) == '') {
            $this->setError('ArtifactTypeFactory: ' . $Language->getText('tracker_common_type', 'name_requ'));
            return false;
        }

         // Necessary test to avoid issues when exporting the tracker to a DB (e.g. '-' not supported as table name)
        if (! preg_match('/^[a-zA-Z0-9_]+$/i', $itemname)) {
                $this->setError($Language->getText('tracker_common_type', 'invalid_shortname', $itemname));
                return false;
        }

         $reference_manager = ReferenceManager::instance();
        if ($reference_manager->_isKeywordExists($itemname, $group_id)) {
            $this->setError($Language->getText('tracker_common_type', 'shortname_already_exists', $itemname));
               return false;
        }

        if ($this->isNameExists($name, $group_id)) {
            $this->setError($Language->getText('tracker_common_type', 'name_already_exists', $name));
            return false;
        }

         //    get the template Group object
        $pm             = ProjectManager::instance();
        $template_group = $pm->getProject($group_id_template);

        if (! $template_group || ! is_object($template_group) || $template_group->isError()) {
            $this->setError('ArtifactTypeFactory: ' . $Language->getText('tracker_common_type', 'invalid_templ'));
        }

     // get the Group object of the new tracker
        $pm    = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        if (! $group || ! is_object($group) || $group->isError()) {
            $this->setError('ArtifactTypeFactory: ' . $Language->getText('tracker_common_type', 'invalid_templ'));
        }

     // We retrieve allow_copy from template
        $at_template = new ArtifactType($template_group, $atid_template);

        $id_sharing = new TrackerIdSharingDao();
        if ($id = $id_sharing->generateTrackerId()) {
            // First, we create a new ArtifactType into artifact_group_list
            // By default, set 'instantiate_for_new_projects' to '1', so that a project that is not yet a
            // template will be able to have its trackers cloned by default when it becomes a template.
            $sql = "INSERT INTO
                artifact_group_list
                (group_artifact_id, group_id, name, description, item_name, allow_copy,
                             submit_instructions,browse_instructions,instantiate_for_new_projects,stop_notification
                             )
                VALUES
                ($id,
                '" . db_ei($group_id) . "',
                '" . db_es($name) . "',
                '" . db_es($description) . "',
                '" . db_es($itemname) . "',
                            '" . db_ei($at_template->allowsCopy()) . "',
                            '" . db_es($at_template->getSubmitInstructions()) . "',
                            '" . db_es($at_template->getBrowseInstructions()) . "',1,0)";
            //echo $sql;
            $res = db_query($sql);
            if (! $res || db_affected_rows($res) <= 0) {
                $this->setError('ArtifactTypeFactory: ' . db_error());
                return false;
            } else {
                //No need to get the last insert id since we already know the id : $id
                //$id = db_insertid($res,'artifact_group_list','group_artifact_id');
                $at_new = new ArtifactType($group, $id);
                if (! $at_new->fetchData($id)) {
                    $this->setError('ArtifactTypeFactory: ' . $Language->getText('tracker_common_type', 'load_fail'));
                    return false;
                } else {
                    //create global notifications
                    $sql = 'INSERT INTO artifact_global_notification (tracker_id, addresses, all_updates, check_permissions)
                    SELECT ' . db_ei($id) . ', addresses, all_updates, check_permissions
                    FROM artifact_global_notification
                    WHERE tracker_id = ' . db_ei($atid_template);
                    $res = db_query($sql);
                    if (! $res || db_affected_rows($res) <= 0) {
                        $this->setError('ArtifactTypeFactory: ' . db_error());
                    }

                    // Create fieldset factory
                    $art_fieldset_fact = new ArtifactFieldSetFactory($at_template);
                    // Then copy all the field sets.
                    $mapping_field_set_array = $art_fieldset_fact->copyFieldSets($atid_template, $id);
                    if (! $mapping_field_set_array) {
                        $this->setError('ArtifactTypeFactory: ' . $art_fieldset_fact->getErrorMessage());
                        return false;
                    }

                    // Create field factory
                    $art_field_fact = new ArtifactFieldFactory($at_template);

                    // Then copy all the fields informations
                    if (! $art_field_fact->copyFields($id, $mapping_field_set_array, $ugroup_mapping)) {
                        $this->setError('ArtifactTypeFactory: ' . $art_field_fact->getErrorMessage());
                        return false;
                    }

                    // Then copy all the reports informations
                    // Create field factory
                    $art_report_fact = new ArtifactReportFactory();

                    if (! $report_mapping = $art_report_fact->copyReports($atid_template, $id)) {
                        $this->setError('ArtifactTypeFactory: ' . $art_report_fact->getErrorMessage());
                        return false;
                    }
                    $em          = EventManager::instance();
                    $pref_params = ['atid_source'   => $atid_template,
                        'atid_dest'     => $id,
                    ];
                    $em->processEvent('artifactType_created', $pref_params);

                    // Copy artifact_notification_event and artifact_notification_role
                    if (! $at_new->copyNotificationEvent($id)) {
                        return false;
                    }
                    if (! $at_new->copyNotificationRole($id)) {
                        return false;
                    }

                    // Create user permissions: None for group members and Admin for group admin
                    if (! $at_new->createUserPerms($id)) {
                        return false;
                    }

                    // Create canned responses
                    $canned_new      = new ArtifactCanned($at_new);
                    $canned_template = $at_template->getCannedResponses();
                    if ($canned_template && db_numrows($canned_template) > 0) {
                        while ($row = db_fetch_array($canned_template)) {
                            $canned_new->create($row['title'], $row['body']);
                        }
                    }

                    //Copy template permission
                    permission_copy_tracker_and_field_permissions($atid_template, $id, $group_id_template, $group_id, $ugroup_mapping);

                    //Copy Rules
                    require_once('ArtifactRulesManager.php');
                    $arm = new ArtifactRulesManager();
                    $arm->copyRules($atid_template, $id);
                }
            }
        }
        return $id;
    }

    public function getArtifactGroupListDao()
    {
        return new ArtifactGroupListDao(CodendiDataAccess::instance());
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state   = true;
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

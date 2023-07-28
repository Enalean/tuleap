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

use Tuleap\Reference\CrossReferencesDao;

require_once __DIR__ . '/../../www/project/admin/permissions.php';


class ArtifactType
{
    /**
     * The Group object.
     *
     * @var        object $Group .
     */
    public $Group;

    /**
     * Current user permissions.
     *
     * @var        int        $current_user_perm.
     */
    public $current_user_perm;


    /**
     * Canned responses resource ID.
     *
     * @var        int        $cannecresponses_res.
     */
    public $cannedresponses_res;

    /**
     * Array of artifact data.
     *
     * @var        array    $data_array.
     */
    public $data_array;

    /**
     * number of notification events
     *
     * @var        array
     */
    public $num_events = 0;

    /**
     * Array of events
     *
     * @var        array
     */
    public $arr_events = [];

    /**
     * number of roles
     *
     * @var        array
     */
    public $num_roles = 0;

    /**
     * Array of roles
     *
     * @var        array
     */
    public $arr_roles = [];

    /**
     * Technicians db resource ID.
     *
     * @var        int        $admins_res.
     */
       public $admins_res;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     *    ArtifactType - constructor.
     *
     *    @param    object    The Group object.
     *    @param    int        The id # assigned to this artifact type in the db.
     *  @param    array    The associative array of data.
     *    @return bool success.
     */
    public function __construct($Group, $artifact_type_id = false, $arr = false)
    {
        global $Language;

        if (! $Group || ! is_object($Group)) {
            $this->setError($Language->getText('tracker_common_type', 'invalid'));
            return false;
        }
        if ($Group->isError()) {
            $this->setError('ArtifactType: ' . $Group->getErrorMessage());
            return false;
        }

        $this->Group = $Group;
        if ($artifact_type_id) {
            $res_events       = $this->getNotificationEvents($artifact_type_id);
            $this->num_events = db_numrows($res_events);
            $i                = 0;
            while ($arr_events = db_fetch_array($res_events)) {
                 $this->arr_events[$i] = $arr_events;
                $i++;
            }

            $res_roles       = $this->getNotificationRoles($artifact_type_id);
            $this->num_roles = db_numrows($res_roles);
            $i               = 0;
            while ($arr_roles = db_fetch_array($res_roles)) {
                 $this->arr_roles[$i] = $arr_roles;
                $i++;
            }

            if (! $arr || ! is_array($arr)) {
                if (! $this->fetchData($artifact_type_id)) {
                    return false;
                }
            } else {
                $this->data_array = $arr;
                if ($this->data_array['group_id'] != $this->Group->getID()) {
                    $this->setError($Language->getText('tracker_common_type', 'no_match'));
                    $this->data_array = null;
                    return false;
                }
            }
        }

        unset($this->admins_res);
        unset($this->current_user_perm);
        unset($this->cannedresponses_res);
    }

    /**
     *    Create user permissions: Tech Only for group members and Tech & Admin for group admin
     *
     *    @param    atid: the artfact type id
     *
     *    @return bool
     */
    public function createUserPerms($atid)
    {
        global $Language;

        $sql = "SELECT "
        . "user.user_id AS user_id, "
        . "user_group.admin_flags "
        . "FROM user,user_group WHERE "
        . "user.user_id=user_group.user_id AND user_group.group_id=" . db_ei($this->Group->getID());
        $res = db_query($sql);

        while ($row = db_fetch_array($res)) {
            if ($row['admin_flags'] == "A") {
                // Admin user
                $perm = 3;
            } else {
                // Standard user
                $perm = 0;
            }

            if (! $this->addUser($row['user_id'], $perm)) {
                $this->setError($Language->getText('tracker_common_type', 'perm_fail', $this->getErrorMessage()));
                return false;
            }
        }

        return true;
    }

    /**
     *  fetch the notification roles for this ArtifactType from the database.
     *
     *  @param    int        The artifact type ID.
     *  @return query result.
     */
    public function getNotificationRoles($artifact_type_id)
    {
        $sql = 'SELECT * FROM artifact_notification_role WHERE group_artifact_id=' . db_ei($artifact_type_id) . ' ORDER BY `rank` ASC;';
        return db_query($sql);
    }

    /**
     *  fetch the notification events for this ArtifactType from the database.
     *
     *  @param    int        The artifact type ID.
     *  @return query result.
     */
    public function getNotificationEvents($artifact_type_id)
    {
        $sql = 'SELECT * FROM artifact_notification_event WHERE group_artifact_id=' . db_ei($artifact_type_id) . ' ORDER BY `rank` ASC;';
        return db_query($sql);
    }

    /**
     *  fetchData - re-fetch the data for this ArtifactType from the database.
     *
     *  @param    int        The artifact type ID.
     *  @return bool success.
     */
    public function fetchData($artifact_type_id)
    {
        global $Language;

        $sql = "SELECT * FROM artifact_group_list
			WHERE group_artifact_id='" . db_ei($artifact_type_id) . "'
			AND group_id='" .  db_ei($this->Group->getID())  . "'";
        $res = db_query($sql);
        if (! $res || db_numrows($res) < 1) {
            $this->setError('ArtifactType: ' . $Language->getText('tracker_common_type', 'invalid_at'));
            return false;
        }
        $this->data_array = db_fetch_array($res);
        db_free_result($res);
        return true;
    }

    /**
     *      getGroup - get the Group object this ArtifactType is associated with.
     *
     *      @return    Object    The Group object.
     */
    public function &getGroup()
    {
        return $this->Group;
    }

    /**
     *      getID - get this ArtifactTypeID.
     *
     *      @return    int    The group_artifact_id #.
     */
    public function getID()
    {
        return $this->data_array['group_artifact_id'] ?? 0;
    }

    /**
     *      getID - get this Artifact Group ID.
     *
     *      @return    int    The group_id #.
     */
    public function getGroupID()
    {
        return $this->data_array['group_id'];
    }

    /**
     *      getOpenCount - get the count of open tracker items in this tracker type.
     *
     *      @return    int    The count.
     */
    public function getOpenCount()
    {
            $count = $this->data_array['open_count'];
            return ($count ? $count : 0);
    }

    /**
     *      getTotalCount - get the total number of tracker items in this tracker type.
     *
     *      @return    int    The total count.
     */
    public function getTotalCount()
    {
            $count = $this->data_array['count'];
            return ($count ? $count : 0);
    }

    /**
     *      isInstantiatedForNewProjects
     *
     *      @return bool - true if the tracker is instantiated for new projects (tracker templates).
     */
    public function isInstantiatedForNewProjects()
    {
        return $this->data_array['instantiate_for_new_projects'];
    }

    /**
     *      allowsAnon - determine if non-logged-in users can post.
     *
     *      @return bool allow_anonymous_submissions.
     */
    public function allowsAnon()
    {
        if (! isset($this->data_array['allow_anon'])) {
            // First, check that anonymous users can access the tracker
            if ($this->userCanView(100)) {
                // Then check if they can submit a field
                $this->data_array['allow_anon'] = $this->userCanSubmit(100);
            } else {
                $this->data_array['allow_anon'] = false;
            }
        }
            return $this->data_array['allow_anon'];
    }

    /**
     *      allowsCopy - determine if artifacts can be copied using a copy button
     *
     *      @return bool allow_copy.
     */
    public function allowsCopy()
    {
        return $this->data_array['allow_copy'];
    }

    /**
     *      getSubmitInstructions - get the free-form string strings.
     *
     *      @return    string    instructions.
     */
    public function getSubmitInstructions()
    {
        return $this->data_array['submit_instructions'];
    }

    /**
     *      getBrowseInstructions - get the free-form string strings.
     *
     *      @return string instructions.
     */
    public function getBrowseInstructions()
    {
        return $this->data_array['browse_instructions'];
    }

    /**
     *      getName - the name of this ArtifactType.
     *
     *      @return    string    name.
     */
    public function getName()
    {
        return $this->data_array['name'] ?? '';
    }

    /**
     *      getItemName - the item name of this ArtifactType.
     *
     *      @return    string    name.
     */
    public function getItemName()
    {
        return $this->data_array['item_name'];
    }

    /**
     *      getCapsItemName - the item name of this ArtifactType with the first letter in caps.
     *
     *      @return    string    name.
     */
    public function getCapsItemName()
    {
        return strtoupper(substr($this->data_array['item_name'], 0, 1)) . substr($this->data_array['item_name'], 1);
    }

    /**
     *      getDescription - the description of this ArtifactType.
     *
     *      @return    string    description.
     */
    public function getDescription()
    {
        return $this->data_array['description'];
    }

    /**
     *      this tracker is not deleted
     *
     *      @return bool .
     */
    public function isValid()
    {
        return ($this->data_array['status'] == 'A');
    }

    /**
     *    getCannedResponses - returns a result set of canned responses.
     *
     *    @return database result set.
     */
    public function getCannedResponses()
    {
        if (! isset($this->cannedresponses_res)) {
            $sql = "SELECT artifact_canned_id,title,body
				FROM artifact_canned_responses
				WHERE group_artifact_id='" .  db_ei($this->getID())  . "'";
         //echo $sql;
            $this->cannedresponses_res = db_query($sql);
        }
        return $this->cannedresponses_res;
    }

    /**
     * getStopNotification - get notification status in this tracker (1 for stopped or 0 for active)
     *
     * @return bool true if notification stopped, false if notification is active
     */
    public function getStopNotification()
    {
        return $this->data_array['stop_notification'];
    }

    /**
     *  setStopNotification - set notification status in this tracker (1 for stopped or 0 for active)
     */
    public function setStopNotification($stop_notification)
    {
        $sql = 'UPDATE artifact_group_list'
            . ' SET stop_notification = ' .  db_ei($stop_notification)
            . ' WHERE group_artifact_id = ' .  db_ei($this->getID())
            . ' AND group_id = ' .  db_ei($this->Group->getID());
        return db_query($sql);
    }

    /**
     *    addUser - add a user to this ArtifactType - depends on UNIQUE INDEX preventing duplicates.
     *
     *    @param    int        user_id of the new user.
     *  @param  value: the value permission
     *
     *    @return bool success.
     */
    public function addUser($id, $value)
    {
        global $Language;

        if (! $this->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }
        if (! $id) {
            $this->setError($Language->getText('tracker_common_canned', 'missing_param'));
            return false;
        }
        $sql    = "INSERT INTO artifact_perm (group_artifact_id,user_id,perm_level)
			VALUES ('" . db_ei($this->getID()) . "','" . db_ei($id) . "'," . db_ei($value) . ")";
        $result = db_query($sql);
        if ($result && db_affected_rows($result) > 0) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     *    existUser - check if a user is already in the project permissions
     *
     *    @param    int        user_id of the new user.
     *    @return bool success.
     */
    public function existUser($id)
    {
        global $Language;

        if (! $id) {
            $this->setError($Language->getText('tracker_common_canned', 'missing_param'));
            return false;
        }
        $sql    = "SELECT * FROM artifact_perm WHERE user_id=" . db_ei($id) . " AND group_artifact_id=" . db_ei($this->getID());
        $result = db_query($sql);
        if (db_numrows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *    updateUser - update a user's permissions.
     *
     *    @param    int        user_id of the user to update.
     *    @param    int        (1) tech only, (2) admin & tech (3) admin only.
     *    @return bool success.
     */
    public function updateUser($id, $perm_level)
    {
        global $Language;

        if (! $this->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }
        if (! $id) {
            $this->setError($Language->getText('tracker_common_canned', 'missing_param') . ': ' . $id . '|' . $perm_level);
            return false;
        }
        $sql    = "UPDATE artifact_perm SET perm_level='" . db_ei($perm_level) . "'
			WHERE user_id='" . db_ei($id) . "' AND group_artifact_id='" . db_ei($this->getID()) . "'";
        $result = db_query($sql);
        if ($result) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     *    deleteUser - delete a user's permissions.
     *
     *    @param    int        user_id of the user who's permissions to delete.
     *    @return bool success.
     */
    public function deleteUser($id)
    {
        global $Language;

        if (! $id) {
            $this->setError($Language->getText('tracker_common_canned', 'missing_param'));
            return false;
        }
        if (! $this->userIsAdmin($id)) {
         //$this->setError($Language->getText('tracker_common_canned','perm_denied'));
            return true;
        }
        $sql    = "DELETE FROM artifact_perm
			WHERE user_id='" . db_ei($id) . "' AND group_artifact_id='" . db_ei($this->getID()) . "'";
        $result = db_query($sql);
        if ($result) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     *    preDelete - Mark this for deletion.
     *
     * @param bool $bypassPerms Set to true to bypass testing if user is tracker admin
     *
     *    @return bool success.
     */
    public function preDelete($bypassPerms = false)
    {
        global $Language;

        if (! $bypassPerms && ! $this->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }
        $date   = (time() + 1000000); // 12 days of delay
        $sql    = "update artifact_group_list SET status='D', deletion_date='" . db_ei($date) . "'
			WHERE group_artifact_id='" . db_ei($this->getID()) . "'";
        $result = db_query($sql);
        if ($result) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     *    delay - change date for deletion.
     *
     *    @return bool success.
     */
    public function delay($date)
    {
        global $Language;
        if (! $this->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }
        $keywords = preg_split("/-/", $date);
        $ts       = mktime("23", "59", "59", $keywords[1], $keywords[2], $keywords[0]);
        if (time() > $ts) {
            $this->setError($Language->getText('tracker_common_type', 'invalid_date'));
            return false;
        }
        $sql    = "update artifact_group_list SET deletion_date='" . db_ei($ts) . "'
			WHERE group_artifact_id='" . db_ei($this->getID()) . "'";
        $result = db_query($sql);
        if ($result) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     *    restore - Unmark this for deletion.
     *
     *    @return bool success.
     */
    public function restore()
    {
        global $Language;

        if (! $this->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }
        $sql    = "update artifact_group_list SET status='A'
			WHERE group_artifact_id='" . db_ei($this->getID()) . "'";
        $result = db_query($sql);
        if ($result) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     *    updateUsers - update the user's permissions.
     *
     *  @param atid: the group artifact id
     *    @param array: the array which contains the user permissions.
     *    @return bool success.
     */
    public function updateUsers($atid, $user_name)
    {
        global $Language;

        $result = $this->getUsersPerm($this->getID());
        $rows   = db_numrows($result);

        if (($rows > 0) && (is_array($user_name))) {
            $update_error = "";

            for ($i = 0; $i < $rows; $i++) {
                $user_id = db_result($result, $i, 'user_id');
                $sql     = "update artifact_perm set perm_level = " . db_ei($user_name[$i]) . " where ";
                $sql    .= "group_artifact_id = " . db_ei($atid) . " and user_id = " . db_ei($user_id);
                //echo $sql."<br>";
                $result2 = db_query($sql);
                if (! $result2) {
                    $update_error .= " " . $Language->getText('tracker_common_type', 'perm_err', [$user_id, db_error()]);
                }
            }

            if ($update_error) {
                $this->setError($update_error);
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /*

        USER PERMISSION FUNCTIONS

    */

    /**
     *      userCanView - determine if the user can view this artifact type.
         *        Note that if there is no group explicitely auhtorized, access is denied (don't check default values)
     *
     *      @param $my_user_id    if not specified, use the current user id..
     *      @return bool user_can_view.
     */
    public function userCanView($my_user_id = 0)
    {
        if (! $my_user_id) {
            // Super-user has all rights...
            if (user_is_super_user()) {
                return true;
            }
            $my_user_id = UserManager::instance()->getCurrentUser()->getId();
        } else {
            $u = UserManager::instance()->getUserById($my_user_id);
            if ($u->isSuperUser()) {
                return true;
            }
        }

        if ($this->userIsAdmin($my_user_id)) {
            return true;
        } else {
            $sql = "SELECT ugroup_id
                      FROM permissions
                      WHERE permission_type LIKE 'TRACKER_ACCESS%'
                        AND object_id='" . db_ei($this->getID()) . "'
                      ORDER BY ugroup_id";
            $res = db_query($sql);

            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    // should work even for anonymous users
                    if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->Group->getID(), $this->getID())) {
                        return true;
                    }
                }
            }
        }
            return false;
    }

    /**
     *      userHasFullAccess - A bit more restrictive than userCanView: determine if the user has
         *        the 'TRACKER_ACCESS_FULL' permission on the tracker.
     *
     *      @param $my_user_id    if not specified, use the current user id..
     *      @return bool
     */
    public function userHasFullAccess($my_user_id = 0)
    {
        if (! $my_user_id) {
            // Super-user has all rights...
            if (user_is_super_user()) {
                return true;
            }
            $my_user_id = UserManager::instance()->getCurrentUser()->getId();
        } else {
            $u = UserManager::instance()->getUserById($my_user_id);
            if ($u->isSuperUser()) {
                return true;
            }
        }

            $sql = "SELECT ugroup_id
                  FROM permissions
                  WHERE permission_type='TRACKER_ACCESS_FULL'
                    AND object_id='" . db_ei($this->getID()) . "'
                  ORDER BY ugroup_id";
            $res = db_query($sql);

        if (db_numrows($res) > 0) {
            while ($row = db_fetch_array($res)) {
                // should work even for anonymous users
                if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->Group->getID(), $this->getID())) {
                    return true;
                }
            }
        }

            return false;
    }

    /**
     *    userIsAdmin - see if the user's perms are >= 2 or project admin.
     *
     *  @param int $user_id the user ID to test, or current user if false
     *    @return bool
     */
    public function userIsAdmin($user_id = false)
    {
        $um = UserManager::instance();
        if (! $user_id) {
            $user    = $um->getCurrentUser();
            $user_id = $user->getId();
        } else {
            $user = $um->getUserById($user_id);
        }
        if ($user->isTrackerAdmin($this->Group->getID(), $this->getID())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *      userCanSubmit - determine if the user can submit an artifact (if he can submit a field).
         *        Note that if there is no group explicitely auhtorized, access is denied (don't check default values)
     *
     *      @param $my_user_id    if not specified, use the current user id..
     *      @return bool user_can_submit.
     */
    public function userCanSubmit($my_user_id = 0)
    {
        if (! $my_user_id) {
            // Super-user has all rights...
            if (user_is_super_user()) {
                return true;
            }
            $my_user_id = UserManager::instance()->getCurrentUser()->getId();
        } else {
            $u = UserManager::instance()->getUserById($my_user_id);
            if ($u->isSuperUser()) {
                return true;
            }
        }

        // Select submit permissions for all fields
        $sql = "SELECT ugroup_id
                  FROM permissions
                  WHERE permission_type='TRACKER_FIELD_SUBMIT'
                    AND object_id LIKE '" . db_ei($this->getID()) . "#%'
                  GROUP BY ugroup_id";
        $res = db_query($sql);

        if (db_numrows($res) > 0) {
            while ($row = db_fetch_array($res)) {
                // should work even for anonymous users
                if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->Group->getID(), $this->getID())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *    getCurrentUserPerm - get the logged-in user's perms from artifact_perm.
     *
     *    @return int perm level for the logged-in user.
     */
    public function getCurrentUserPerm()
    {
        if (! user_isloggedin()) {
            return 0;
        } else {
            if (! isset($this->current_user_perm)) {
                $sql = "select perm_level
				FROM artifact_perm
				WHERE group_artifact_id='" .  db_ei($this->getID())  . "'
				AND user_id='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "'";
                //echo $sql;
                $this->current_user_perm = db_result(db_query($sql), 0, 0);
            }
            return $this->current_user_perm;
        }
    }

    /**
     *    getUserPerm - get a user's perms from artifact_perm.
     *
     *    @return int perm level for a user.
     */
    public function getUserPerm($user_id)
    {
        $sql = "select perm_level
		FROM artifact_perm
		WHERE group_artifact_id='" .  db_ei($this->getID())  . "'
		AND user_id='" . db_ei($user_id) . "'";
     //echo $sql."<br>";
        return db_result(db_query($sql), 0, 0);
    }

    /**
     * Get permissions for all fields based on the ugroups the user is part of
     *
     */
    public function getFieldPermissions($ugroups)
    {
        $art_field_fact = new ArtifactFieldFactory($this);
        $used_fields    = $art_field_fact->getAllUsedFields();
        $field_perm     = [];

        reset($used_fields);
        foreach ($used_fields as $field) {
            $perm = $field->getPermissionForUgroups($ugroups, $this->getID());
            if ($perm && ! empty($perm)) {
                  $field_perm[$field->getName()] = $perm;
            }
        }
        return $field_perm;
    }

    /**
     *  update - use this to update this ArtifactType in the database.
     *
     *  @param    string    The item name.
     *  @param    string    The item description.
     *  @param    int        Days before this item is considered overdue.
     *  @param    int        Days before stale items time out.
     *  @param    bool    (1) true (0) false - whether the resolution box should be shown.
     *  @param    string    Free-form string that project admins can place on the submit page.
     *  @param    string    Free-form string that project admins can place on the browse page.
     *  @param    bool    instantiate_for_new_projects (1) true (0) false - instantiate this tracker template for new projects
     *  @return true on success, false on failure.
     */
    public function update(
        $name,
        $description,
        $itemname,
        $allow_copy,
        $submit_instructions,
        $browse_instructions,
        $instantiate_for_new_projects,
    ) {
        global $Language;

        if (! $this->userIsAdmin()) {
            $this->setError('ArtifactType: ' . $Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }

        if (! $name || ! $description || ! $itemname || trim($name) == "" || trim($description) == "" || trim($itemname) == "") {
            $this->setError('ArtifactType: ' . $Language->getText('tracker_common_type', 'name_requ'));
            return false;
        }

        if (! preg_match("/^[a-zA-Z0-9_]+$/i", $itemname)) {
            $hp = Codendi_HTMLPurifier::instance();
            $this->setError($Language->getText('tracker_common_type', 'invalid_shortname', $hp->purify($itemname, CODENDI_PURIFIER_CONVERT_HTML)));
            return false;
        }

        $group_id = $this->Group->getID();
        $old_name = $this->getName();

        if ($old_name != $name) {
            $atf = new ArtifactTypeFactory($this->Group);
            if ($atf->isNameExists($name, $group_id)) {
                $this->setError($Language->getText('tracker_common_type', 'name_already_exists', $itemname));
                return false;
            }
        }

        $allow_copy                   = ((! $allow_copy) ? 0 : $allow_copy);
        $instantiate_for_new_projects = ((! $instantiate_for_new_projects) ? 0 : $instantiate_for_new_projects);

        $old_item_name = $this->getItemName();

        if ($old_item_name != $itemname) {
            $reference_manager = ReferenceManager::instance();

            if (! $reference_manager->checkKeyword($itemname)) {
                $this->setError($Language->getText('tracker_common_type', 'invalid_shortname', $itemname));
                return false;
            }

            if ($reference_manager->_isKeywordExists($itemname, $group_id)) {
                $this->setError($Language->getText('tracker_common_type', 'shortname_already_exists', $itemname));
                return false;
            }

            //Update table 'reference'
            $reference_dao = $this->getReferenceDao();
            $result        = $reference_dao->update_keyword($old_item_name, $itemname, $this->Group->getID());

            //Update table 'cross_reference'
            $reference_dao = $this->getCrossReferenceDao();
            $reference_dao->updateTargetKeyword($old_item_name, $itemname, $this->Group->getID());
            $reference_dao->updateSourceKeyword($old_item_name, $itemname, $this->Group->getID());
        }

          //Update table 'artifact_group_list'
          $reference_dao = $this->getArtifactGroupListDao();
          $result        = $reference_dao->updateArtifactGroupList($this->getID(), $this->Group->getID(), $name, $description, $itemname, $allow_copy, $submit_instructions, $browse_instructions, $instantiate_for_new_projects);

        if (! $result) {
            $this->setError('ArtifactType::Update(): ' . db_error());
            return false;
        } else {
            $this->fetchData($this->getID());
            return true;
        }
    }

    /**
     *  updateNotificationSettings - use this to update this ArtifactType in the database.
     *
     *  @param    int    uid the user to set watches on
     *  @param    string    the list of users to watch
     *  @param    string    the list of watching users
     *  @return true on success, false on failure.
     */
    public function updateNotificationSettings($user_id, $watchees, $stop_notification)
    {
        $this->setStopNotification($stop_notification);
        $this->setWatchees($user_id, $watchees);
        $this->fetchData($this->getID());
        return true;
    }

    public function deleteWatchees($user_id)
    {
         $sql = "DELETE FROM artifact_watcher WHERE user_id='" . db_ei($user_id) . "' AND artifact_group_id='" . db_ei($this->getID()) . "'";
     //echo $sql."<br>";
         return db_query($sql);
    }

    public function getWatchees($user_id)
    {
        $sql = "SELECT watchee_id FROM artifact_watcher WHERE user_id='" . db_ei($user_id) . "' AND artifact_group_id=" . db_ei($this->getID());
     //echo $sql."<br>";
        return db_query($sql);
    }

    public function setWatchees($user_id, $watchees)
    {
        global $Language;
     //echo "setWatchees($user_id, $watchees)<br>";
        if ($watchees) {
         //echo "watchees";
            $res_watch      = true;
            $arr_user_names = preg_split('/[,;]/D', $watchees);
            $arr_user_ids   = [];
            foreach ($arr_user_names as $user_name) {
                $user_ident = util_user_finder($user_name, true);
                $res        = user_get_result_set_from_unix($user_ident);
                if (! $res || (db_numrows($res) <= 0)) {
             // user doesn;t exist  so abort this step and give feedback
                    $this->setError(" - " . $Language->getText('tracker_common_type', 'invalid_name', $user_name));
                    $res_watch = false;
                    continue;
                } else {
             // store in a hash to eliminate duplicates. skip user itself
                    if (db_result($res, 0, 'user_id') != $user_id) {
                        $arr_user_ids[db_result($res, 0, 'user_id')] = 1;
                    }
                }
            }

            if ($res_watch) {
                $this->deleteWatchees($user_id);
                $arr_watchees  = array_keys($arr_user_ids);
                $sql           = 'INSERT INTO artifact_watcher (artifact_group_id, user_id,watchee_id) VALUES ';
                 $num_watchees = count($arr_watchees);
                for ($i = 0; $i < $num_watchees; $i++) {
                    $sql .= "('" . db_ei($this->getID()) . "','" . db_ei($user_id) . "','" . db_ei($arr_watchees[$i]) . "'),";
                }
                $sql = substr($sql, 0, -1); // remove extra comma at the end
               //echo $sql."<br>";
                return db_query($sql);
            }
        } else {
            $this->deleteWatchees($user_id);
        }
    }

    public function getWatchers($user_id)
    {
        $sql = "SELECT user_id FROM artifact_watcher WHERE watchee_id='" . db_ei($user_id) . "' AND artifact_group_id=" . db_ei($this->getID());
        return db_query($sql);
    }

    public function deleteNotification($user_id)
    {
        $sql = "DELETE FROM artifact_notification WHERE user_id='" . db_ei($user_id) . "' AND group_artifact_id='" . db_ei($this->getID()) . "'";
        //echo $sql."<br>";
        return db_query($sql);
    }

    public function setNotification($user_id, $arr_notification)
    {
        $sql = 'INSERT INTO artifact_notification (group_artifact_id, user_id,role_id,event_id,notify) VALUES ';

        for ($i = 0; $i < $this->num_roles; $i++) {
            $role_id = $this->arr_roles[$i]['role_id'];
            for ($j = 0; $j < $this->num_events; $j++) {
                $event_id = $this->arr_events[$j]['event_id'];
                $sql     .= "('" . db_ei($this->getID()) . "','" . db_ei($user_id) . "','" . db_ei($role_id) . "','" . db_ei($event_id) . "','" . db_ei($arr_notification[$role_id][$event_id]) . "'),";
            }
        }
        $sql = substr($sql, 0, -1); // remove extra comma at the end
     //echo $sql."<br>";
        return db_query($sql);
    }


    // People who have once submitted a bug
    public function getSubmitters($with_display_preferences = false)
    {
        $sqlname = "user.user_name";
        if ($with_display_preferences) {
            $uh      = new UserHelper();
            $sqlname = $uh->getDisplayNameSQLQuery();
        }
        $group_artifact_id = $this->getID();
        $sql               = "(SELECT DISTINCT user.user_id, " . $sqlname . " , user.user_name " .
        "FROM user,artifact " .
        "WHERE (user.user_id=artifact.submitted_by " .
        "AND artifact.group_artifact_id='" . db_ei($group_artifact_id) . "') " .
        "ORDER BY user.user_name)";
        return $sql;
    }

    public function getUsersPerm($group_artifact_id)
    {
        $sql = "SELECT u.user_id,u.user_name,au.perm_level " .
        "FROM user u,artifact_perm au " .
        "WHERE u.user_id=au.user_id AND au.group_artifact_id=" . db_ei($group_artifact_id) . " " .
        "ORDER BY u.user_name";
     //echo $sql;
        return db_query($sql);
    }

    /**
     * Copy notification event from default
     *
     * @param group_artifact_id: the destination artifact type id
     *
     * @return bool
     */
    public function copyNotificationEvent($group_artifact_id)
    {
        global $Language;
        $sql = "insert into artifact_notification_event " .
         "select event_id," . db_ei($group_artifact_id) . ",event_label,`rank`,short_description_msg,description_msg " .
         "from artifact_notification_event_default";

        $res_insert = db_query($sql);

        if (! $res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_type', 'copy_fail'));
            return false;
        }

        return true;
    }

    /**
     * Copy notification role from default
     *
     * @param group_artifact_id: the destination artifact type id
     *
     * @return bool
     */
    public function copyNotificationRole($group_artifact_id)
    {
        global $Language;
        $sql = "insert into artifact_notification_role " .
         "select role_id," . db_ei($group_artifact_id) . ",role_label ,`rank`, short_description_msg,description_msg " .
         "from artifact_notification_role_default";

        $res_insert = db_query($sql);

        if (! $res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_type', 'notif_fail'));
            return false;
        }

        return true;
    }

    /**
     *
     * Get artifacts by age
     *
     * @return array
     */
    public function getOpenArtifactsByAge()
    {
        $time_now = time();
     //            echo $time_now."<P>";

        for ($counter = 1; $counter <= 8; $counter++) {
            $start = ($time_now - ($counter * 604800));
            $end   = ($time_now - (($counter - 1) * 604800));

            $sql = "SELECT count(*)
                  FROM artifact
                  WHERE open_date >= $start AND open_date <= $end
                  AND status_id = '1'
                  AND group_artifact_id='" . db_ei($this->getID()) . "'";

            $result = db_query($sql);

            $names[$counter - 1] = format_date("m/d/y", ($start)) . " to " . format_date("m/d/y", ($end));
            if (db_numrows($result) > 0) {
                $values[$counter - 1] = db_result($result, 0, 0);
            } else {
                $values[$counter - 1] = '0';
            }
        }

        return ['names' => $names, 'values' => $values];
    }

    /**
     *
     * Get artifacts by age
     *
     * @return array
     */
    public function getArtifactsByAge()
    {
        $time_now = time();

        for ($counter = 1; $counter <= 8; $counter++) {
            $start = ($time_now - ($counter * 604800));
            $end   = ($time_now - (($counter - 1) * 604800));

            $sql = "SELECT avg((close_date-open_date)/86400)
                  FROM artifact
                  WHERE close_date > 0 AND (open_date >= $start AND open_date <= $end)
                  AND status_id <> '1'
                  AND group_artifact_id='" . db_ei($this->getID()) . "'";

            $result              = db_query($sql);
            $names[$counter - 1] = format_date("m/d/y", ($start)) . " to " . format_date("m/d/y", ($end));
            if (db_numrows($result) > 0) {
                $values[$counter - 1] = db_result($result, 0, 0);
            } else {
                $values[$counter - 1] = '0';
            }
        }

        return ['names' => $names, 'values' => $values];
    }

    /**
     *
     * Get artifacts grouped by standard field
     *
     * @return bool
     */
    public function getArtifactsBy($field)
    {
        $sql = "SELECT " . $field->getName() . ", count(*) AS Count FROM artifact " .
        " WHERE  artifact.group_artifact_id=" . db_ei($this->getID()) .
        " GROUP BY " . $field->getName();

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            for ($j = 0; $j < db_numrows($result); $j++) {
                if ($field->isSelectBox() || $field->isMultiSelectBox()) {
                    $labelValue = $field->getLabelValues($this->getID(), [db_result($result, $j, 0)]);
                    $names[$j]  = $labelValue[0];
                } else {
                    $names[$j] = db_result($result, $j, 0);
                }
                $values[$j] = db_result($result, $j, 1);
            }
        }

        $results['names']  = $names;
        $results['values'] = $values;

        return $results;
    }

    /**
     *
     * Get open artifacts grouped by standard field
     *
     * @return bool
     */
    public function getOpenArtifactsBy($field)
    {
        $sql = "SELECT " . $field->getName() . ", count(*) AS Count FROM artifact " .
        " WHERE artifact.group_artifact_id='" . db_ei($this->getID()) . "' " .
        " AND artifact.status_id=1" .
        " GROUP BY " . $field->getName();

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            for ($j = 0; $j < db_numrows($result); $j++) {
                if ($field->isSelectBox() || $field->isMultiSelectBox()) {
                    $labelValue = $field->getLabelValues($this->getID(), [db_result($result, $j, 0)]);
                    $names[$j]  = $labelValue[0];
                } else {
                    $names[$j] = db_result($result, $j, 0);
                }
                $values[$j] = db_result($result, $j, 1);
            }
        }

        $results['names']  = $names;
        $results['values'] = $values;

        return $results;
    }

    /**
     *
     * Get artifacts grouped by field
     *
     * @return bool
     */
    public function getArtifactsByField($field)
    {
        $sql = "SELECT " . $field->getValueFieldName() . ", count(*) AS Count FROM artifact_field_value, artifact " .
         " WHERE  artifact.group_artifact_id='" . db_ei($this->getID()) . "' " .
         " AND artifact_field_value.artifact_id=artifact.artifact_id" .
         " AND artifact_field_value.field_id=" . db_ei($field->getID()) .
         " GROUP BY " . $field->getValueFieldName();

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            for ($j = 0; $j < db_numrows($result); $j++) {
                if ($field->isSelectBox() || $field->isMultiSelectBox()) {
                    $labelValue = $field->getLabelValues($this->getID(), [db_result($result, $j, 0)]);
                    $names[$j]  = $labelValue[0];
                } else {
                    $names[$j] = db_result($result, $j, 0);
                }

                $values[$j] = db_result($result, $j, 1);
            }
            $results['names']  = $names;
            $results['values'] = $values;
        }
        return $results;
    }

    /**
     *
     * Get open artifacts grouped by field
     *
     * @return bool
     */
    public function getOpenArtifactsByField($field)
    {
        $sql = "SELECT " . $field->getValueFieldName() . ", count(*) AS Count FROM artifact_field_value, artifact " .
        " WHERE  artifact.group_artifact_id='" . db_ei($this->getID()) . "' " .
        " AND artifact_field_value.artifact_id=artifact.artifact_id" .
        " AND artifact_field_value.field_id=" . db_ei($field->getID()) .
        " AND artifact.status_id=1" .
        " GROUP BY " . $field->getValueFieldName();

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            for ($j = 0; $j < db_numrows($result); $j++) {
                if ($field->isSelectBox() || $field->isMultiSelectBox()) {
                    $labelValue = $field->getLabelValues($this->getID(), [db_result($result, $j, 0)]);
                    $names[$j]  = $labelValue[0];
                } else {
                    $names[$j] = db_result($result, $j, 0);
                }

                $values[$j] = db_result($result, $j, 1);
            }
            $results['names']  = $names;
            $results['values'] = $values;
        }
        return $results;
    }

    /**
     * Check if for a user and for role, there is a change
     *
     * @param user_id: the user id
     * @param role: the role
     * @param changes: array of changes
     *
     * @return bool
     */
    public function checkNotification($user_id, $role, $changes = false)
    {
        $send      = false;
        $arr_notif = $this->buildNotificationMatrix($user_id);
        if (! $arr_notif || (count($arr_notif) == 0)) {
            return true;
        }

        // echo "==== DBG Checking Notif. for $user_id (role=$role)<br>";
        $user_name = user_getname($user_id);

        //----------------------------------------------------------
        // If it's a new bug only (changes is false) check the NEW_BUG event and
        // ignore all other events
        if ($changes == false) {
            if ($arr_notif[$role]['NEW_ARTIFACT']) {
                   // echo "DBG NEW_ARTIFACT notified<br>";
                   return true;
            } else {
                   // echo "DBG No notification<br>";
                   return false;
            }
        }

        //----------------------------------------------------------
        //Check: I_MADE_IT  (I am the author of the change )
        // Check this one first because if the user said no she doesn't want to be
        // aware of any of her change in this role and we can return immediately.
        if (($user_id == UserManager::instance()->getCurrentUser()->getId()) && ! $arr_notif[$role]['I_MADE_IT']) {
       //echo "DBG Dont want to receive my own changes<br>";
            return false;
        }

        //----------------------------------------------------------
        // Check :  NEW_COMMENT  A new followup comment is added
        if ($arr_notif[$role]['NEW_COMMENT'] && isset($changes['comment'])) {
      // echo "DBG NEW_COMMENT notified<br>";
            return true;
        }

        //----------------------------------------------------------
        //Check: NEW_FILE  (A new file attachment is added)
        if ($arr_notif[$role]['NEW_FILE'] && isset($changes['attach'])) {
      // echo "DBG NEW_FILE notified<br>";
            return true;
        }

        //----------------------------------------------------------
        //Check: CLOSED  (The bug is closed)
        // Rk: this one has precedence over PSS_CHANGE. So notify even if PSS_CHANGE
        // says no.
        if ($arr_notif[$role]['CLOSED'] && (isset($changes['status_id']) && $changes['status_id']['add'] == 'Closed')) {
      // echo "DBG CLOSED bug notified<br>";
            return true;
        }

        //----------------------------------------------------------
        //Check: PSS_CHANGE  (Priority,Status,Severity changes)
        if (
            $arr_notif[$role]['PSS_CHANGE'] &&
            (isset($changes['priority']) || isset($changes['status_id']) || isset($changes['severity']))
        ) {
      // echo "DBG PSS_CHANGE notified<br>";
            return true;
        }

        //----------------------------------------------------------
        // Check :  ROLE_CHANGE (I'm added to or removed from this role)
        // Rk: This event is meanningless for Commenters. It also is for submitter but may be
        // one day the submitter will be changeable by the project admin so test it.
        // Rk #2: check this one at the end because it is the most CPU intensive and this
        // event seldomly happens
        if (
            $arr_notif['SUBMITTER']['ROLE_CHANGE'] &&
            isset($changes['submitted_by']) && (($changes['submitted_by']['add'] == $user_name) || ($changes['submitted_by']['del'] == $user_name)) &&
            ($role == 'SUBMITTER')
        ) {
      // echo "DBG ROLE_CHANGE for submitter notified<br>";
            return true;
        }

        if (
            $arr_notif['ASSIGNEE']['ROLE_CHANGE'] &&
            isset($changes['assigned_to']) && (($changes['assigned_to']['add'] == $user_name) || ($changes['assigned_to']['del'] == $user_name)) &&
            ($role == 'ASSIGNEE')
        ) {
      // echo "DBG ROLE_CHANGE for role assignee notified<br>";
            return true;
        }

        $arr_cc_changes = [];
        if (isset($changes['CC']['add'])) {
            $arr_cc_changes = preg_split('/[,;]/D', $changes['CC']['add']);
        }
        $arr_cc_changes[]                = isset($changes['CC']['del']) ? $changes['CC']['del'] : null;
        $is_user_in_cc_changes           = in_array($user_name, $arr_cc_changes);
        $are_anyother_user_in_cc_changes =
        (! $is_user_in_cc_changes || count($arr_cc_changes) > 1);

        if ($arr_notif['CC']['ROLE_CHANGE'] && ($role == 'CC')) {
            if ($is_user_in_cc_changes) {
                   // echo "DBG ROLE_CHANGE for cc notified<br>";
                   return true;
            }
        }

        //----------------------------------------------------------
        //Check: CC_CHANGE  (CC_CHANGE is added or removed)
        // check this right after because  role cahange for cc can contradict
        // thee cc_change notification. If the role change on cc says no notification
        // then it has precedence over a cc_change
        if ($arr_notif[$role]['CC_CHANGE'] && isset($changes['CC'])) {
      // it's enough to test role against 'CC' because if we are at that point
      // it means that the role_change for CC was false or that role is not CC
      // So if role is 'CC' and we are here it means that the user asked to not be
      // notified on role_change as CC, unless other users are listed in the cc changes
            if (($role != 'CC') || (($role == 'CC') && $are_anyother_user_in_cc_changes)) {
                   // echo "DBG CC_CHANGE notified<br>";
                   return true;
            }
        }

        //----------------------------------------------------------
        //Check: CHANGE_OTHER  (Any changes not mentioned above)
        // *** THIS ONE MUST ALWAYS BE TESTED LAST

        // Delete all tested fields from the $changes array. If any remains then it
        // means a notification must be sent
        unset($changes['comment']);
        unset($changes['attach']);
        unset($changes['priority']);
        unset($changes['severity']);
        unset($changes['status_id']);
        unset($changes['CC']);
        unset($changes['assigned_to']);
        unset($changes['submitted_by']);
        if ($arr_notif[$role]['ANY_OTHER_CHANGE'] && count($changes)) {
      // echo "DBG ANY_OTHER_CHANGE notified<br>";
            return true;
        }

        // Sorry, no notification...
        // echo "DBG No notification!!<br>";
        return false;
    }

    /**
     * Build the matrix role/event=notify
     *
     * @param user_id: the user id
     *
     * @return array
     */
    public function buildNotificationMatrix($user_id)
    {
        $arr_notif = [];
        // Build the notif matrix indexed with roles and events labels (not id)
        $res_notif = $this->getNotificationWithLabels($user_id);
        while ($arr = db_fetch_array($res_notif)) {
      //echo "<br>".$arr['role_label']." ".$arr['event_label']." ".$arr['notify'];
            $arr_notif[$arr['role_label']][$arr['event_label']] = $arr['notify'];
        }
        return $arr_notif;
    }

    /**
     * Retrieve the matrix role/event=notify from the db
     *
     * @param user_id: the user id
     *
     * @return array
     */
    public function getNotificationWithLabels($user_id)
    {
        $group             = $this->getGroup();
        $group_artifact_id = $this->getID();

        $sql = "SELECT role_label,event_label,notify FROM artifact_notification_role r, artifact_notification_event e,artifact_notification n " .
        "WHERE n.group_artifact_id=" . db_ei($group_artifact_id) . " AND n.user_id=" . db_ei($user_id) . " AND " .
        "n.role_id=r.role_id AND r.group_artifact_id=" . db_ei($group_artifact_id) . " AND " .
        "n.event_id=e.event_id AND e.group_artifact_id=" . db_ei($group_artifact_id);

   /*
    $sql = "SELECT role_label,event_label,notify FROM artifact_notification_role_default r, artifact_notification_event_default e,artifact_notification n ".
     "WHERE n.user_id=$user_id AND ".
     "n.role_id=r.role_id AND ".
     "n.event_id=e.event_id";
   */
        //echo $sql."<br>";
        return db_query($sql);
    }

    /**
     * Retrieve the next free field id (computed by max(id)+1)
     *
     * @return int
     */
    public function getNextFieldID()
    {
        $sql = "SELECT max(field_id)+1 FROM artifact_field WHERE group_artifact_id=" . db_ei($this->getID());

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            return db_result($result, 0, 0);
        } else {
            return -1;
        }
    }

    /**
     * Return a field name built using an id
     *
     * @param id: the id used to build the field name
     *
     * @return array
     */
    public function buildFieldName($id)
    {
        return "field_" . $id;
    }

    /**
     * Return the different elements for building the export query
     *
     * @param fields: the field list
     * @param select: the select value
     * @param from: the from value
     * @param where: the where value
     * @param count: the number of
     *
     * @return void
     */
    public function getExportQueryElements($fields, &$select, &$from, &$where, &$count_user_fields)
    {
     // NOTICE
     //
     // Use left join because of the performance
     // So the restriction to this: all fields used in the query must have a value.
     // That involves artifact creation or artifact admin (add a field) must create
     // empty records with default values for fields which haven't a value (from the user).
     /* The query must be something like this :
      SELECT a.artifact_id,u.user_name,v1.valueInt,v2.valueText,u3.user_name
      FROM artifact a
                             LEFT JOIN artifact_field_value v1 ON (v1.artifact_id=a.artifact_id)
                             LEFT JOIN artifact_field_value v2 ON (v2.artifact_id=a.artifact_id)
                             LEFT JOIN artifact_field_value v3 ON (v2.artifact_id=a.artifact_id)
                             LEFT JOIN user u3 ON (v3.valueInt = u3.user_id)
                             LEFT JOIN user u
      WHERE a.group_artifact_id = 100 and
      v1.field_id=101 and
      v2.field_id=103 and
      v3.field_id=104 and
      a.submitted_by = u.user_id
      group by a.artifact_id
      order by v3.valueText,v1.valueInt
     */

        $count             = 1;
        $count_user_fields = 0;
        reset($fields);

        $select = "SELECT ";
        $from   = "FROM artifact a";
        $where  = "WHERE a.group_artifact_id = " . db_ei($this->getID());

        $select_count = 0;

        if (count($fields) == 0) {
            return;
        }

        foreach ($fields as $field) {
          //echo $field->getName()."-".$field->getID()."<br>";

            // If the field is a standard field ie the value is stored directly into the artifact table (severity, artifact_id, ...)
            if ($field->isStandardField()) {
                if ($select_count != 0) {
                    $select .= ",";
                    $select_count++;
                } else {
                    $select_count = 1;
                }

                // Special case for fields which are user name
                if (($field->isUsername()) && (! $field->isSelectBox()) && (! $field->isMultiSelectBox())) {
                    $select .= " u.user_name as " . $field->getName();
                    $from   .= " LEFT JOIN user u ON (u.user_id=a." . $field->getName() . ")";
                    $count_user_fields++;
                } else {
                    $select .= " a." . $field->getName();
                }
            } else {
             // Special case for comment_type_id field - No data stored in artifact_field_value
                if ($field->getName() != "comment_type_id") {
           // The field value is stored into the artifact_field_value table
           // So we need to add a new join
                    if ($select_count != 0) {
                           $select .= ",";
                           $select_count++;
                    } else {
                           $select_count = 1;
                    }

           // Special case for fields which are user name
                    $from .= " LEFT JOIN artifact_field_value v" . $count . " ON (v" . $count . ".artifact_id=a.artifact_id" .
                    " and v" . $count . ".field_id=" . db_ei($field->getID()) . ")";
           //$where .= " and v".$count.".field_id=".$field->getID();
                    if (($field->isUsername()) && (! $field->isSelectBox()) && (! $field->isMultiSelectBox())) {
                           $select .= " u" . $count . ".user_name as " . $field->getName();
                           $from   .= " LEFT JOIN user u" . $count . " ON (v" . $count . "." . $field->getValueFieldName() . " = u" . $count . ".user_id)";
                           $count_user_fields++;
                    } else {
                           $select .= " v" . $count . "." . $field->getValueFieldName() . " as " . $field->getName();
                    }

                    $count++;
                }
            }
        }
    }

    /**
     * Return the query string, for export
     *
     * @param fields (OUT): the field list
     * @param col_list (OUT): the field name list
     * @param lbl_list (OUT): the field label list
     * @param dsc_list (OUT): the field description list
     * @param select (OUT):
     * @param from (OUT):
     * @param where (OUT):
     * @param multiple_queries (OUT):
     * @param all_queries (OUT):
     *
     * @return string|null the sql query
     */
    public function buildExportQuery(&$fields, &$col_list, &$lbl_list, &$dsc_list, &$select, &$from, &$where, &$multiple_queries, &$all_queries, $constraint = false)
    {
        global $art_field_fact,$art_fieldset_fact;
        $sql         = null;
        $all_queries = [];
      // this array will be filled with the fields to export, ordered by fieldset and rank,
         // and send as an output argument of the function
         $fields   = [];
        $fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
         // fetch the fieldsets
        foreach ($fieldsets as $fieldset) {
            $fields_in_fieldset = $fieldset->getAllUsedFields();
          // for each fieldset, fetch the used fields inside
            foreach ($fields_in_fieldset as $field) {
                if ($field->getName() != "comment_type_id") {
                    $fields[$field->getName()]   = $field;
                    $col_list[$field->getName()] = $field->getName();
                    $lbl_list[$field->getName()] = $field->getLabel();
                    $dsc_list[$field->getName()] = $field->getDescription();
                }
            }
        }

      //it gets a bit more complicated if we have more fields than SQL wants to treat in one single query
        if (count($fields) > ((int) ForgeConfig::get('sys_server_join'))) {
            $multiple_queries = true;
            $chunked_fields   = array_chunk($fields, ((int) ForgeConfig::get('sys_server_join')) - 3, true);
            $this->cutExportQuery($chunked_fields, $select, $from, $where, $all_queries, $constraint);
        } else {
            $multiple_queries = false;
            $this->getExportQueryElements($fields, $select, $from, $where, $count_user_fields);

            if ($count_user_fields > ((int) ForgeConfig::get('sys_server_join')) - count($fields)) {
                  $multiple_queries = true;
                  $chunked_fields   = array_chunk($fields, count($fields) / 2, true);
                  $this->cutExportQuery($chunked_fields, $select, $from, $where, $count_user_fields, $all_queries, $constraint);
            } else {
                  $sql = $select . " " . $from . " " . $where . " " . ($constraint ? $constraint : "") . " group by a.artifact_id";
            }
        }
        return $sql;
    }

    public function cutExportQuery($chunks, &$select, &$from, &$where, &$all_queries, $constraint = false)
    {
        foreach ($chunks as $chunk) {
            $this->getExportQueryElements($chunk, $select, $from, $where, $count_user_fields);
            if ($count_user_fields > ((int) ForgeConfig::get('sys_server_join')) - count($chunk)) {
                  //for each user field we join another user table
                  $chunked_fields = array_chunk($chunk, count($chunk) / 2, true);
                  $this->cutExportQuery($chunked_fields, $select, $from, $where, $count_user_fields, $all_queries, $constraint);
            } else {
                  $sql           = $select . " " . $from . " " . $where . " " . ($constraint ? $constraint : "") . " group by a.artifact_id";
                  $all_queries[] = $sql;
            }
        }
    }

    /**
     * Return the artifact data with all fields set to default values. (for export)
     *
     * @return array the sql query
     */
    public function buildDefaultRecord()
    {
        global $art_field_fact;

        $fields = $art_field_fact->getAllUsedFields();

        foreach ($fields as $field) {
            $record[$field->getName()] = $field->getDefaultValue();
        }

        return $record;
    }

    /** retrieves all the cc addresses with their artifact_cc_ids
     * for a list of artifact_ids
     * @param change_ids: the list of artifact_ids for which we search the emails
     */
    public function getCC($change_ids)
    {
        $sql    = "select email,artifact_cc_id from artifact_cc where artifact_id in (" . db_es(implode(",", $change_ids)) . ") order by email";
        $result = db_query($sql);
        return $result;
    }

    /**
         * Delete an email address in the CC list
         *
         * @param artifact_cc_id: cc list id
         * @param changes (OUT): list of changes
         *
        * @return bool
         */
    public function deleteCC($delete_cc)
    {
        $ok = true;
        foreach ($delete_cc as $artifact_ccs) {
            $artifact_cc_ids = explode(",", $artifact_ccs);
            $i               = 0;
            foreach ($artifact_cc_ids as $artifact_cc_id) {
                    $sql = "SELECT artifact_id from artifact_cc WHERE artifact_cc_id=" . db_ei($artifact_cc_id);
                    $res = db_query($sql);
                if (db_numrows($res) > 0) {
                    $i++;
                    $aid = db_result($res, 0, 'artifact_id');
                    $ah  = new ArtifactHtml($this, $aid);
                    $ok &= $ah->deleteCC($artifact_cc_id, $changes, true);
                }
            }
        }
        return $ok;
    }

    /**
     * retrieves all the attached files with their size and id
     * for a list of artifact_ids
     * @param change_ids: the list of artifact_ids for which we search the attached files
    */
    public function getAttachedFiles($change_ids)
    {
        $sql = "select filename,filesize,id from artifact_file where artifact_id in (" . db_es(implode(",", $change_ids)) . ") order by filename,filesize";
        return db_query($sql);
    }

    /**
    * Delete the files with specified id from $ids
    * @return bool
    */
    public function deleteAttachedFiles($delete_attached)
    {
        $ok = true;
        $i  = 0;
        foreach ($delete_attached as $id_list) {
            $ids = explode(",", $id_list);
            foreach ($ids as $id) {
                $sql = "SELECT artifact_id FROM artifact_file WHERE id = " . db_ei($id);
                $res = db_query($sql);
                if (db_numrows($res) > 0) {
                    $aid = db_result($res, 0, 'artifact_id');
                    $ah  = new ArtifactHtml($this, $aid);
                    $afh = new ArtifactFileHtml($ah, $id);
                    if (! $afh || ! is_object($afh)) {
                             $GLOBALS['Response']->addFeedback('error', 'Could Not Create File Object::' . $afh->getName());
                    } elseif ($afh->isError()) {
                            $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage() . '::' . $afh->getName());
                    } else {
                        $i++;
                            $okthis = $afh->delete();
                        if (! $okthis) {
                            $GLOBALS['Response']->addFeedback('error', '<br>File Delete: ' . $afh->getErrorMessage());
                        }
                        $ok &= $okthis;
                    }
                }
            }
        }
        return $ok;
    }

    /**
     * retrieves all artifacts
     * for a list of artifact_ids
     * @param change_ids: the list of artifact_ids for which we search the attached files
    */
    public function getDependencies($change_ids)
    {
        $sql = "select d.artifact_depend_id,d.is_dependent_on_artifact_id,a.summary,ag.name,g.group_name, g.group_id " .
        "from artifact_dependencies as d, artifact_group_list ag, groups g, artifact a " .
        "where d.artifact_id in (" . db_es(implode(",", $change_ids)) . ") AND " .
        "d.is_dependent_on_artifact_id = a.artifact_id AND " .
                    "a.group_artifact_id = ag.group_artifact_id AND " .
                    "ag.group_id = g.group_id " .
        "order by is_dependent_on_artifact_id";
        return db_query($sql);
    }

    /** delete all the dependencies specified in delete_dependend */
    public function deleteDependencies($delete_depend)
    {
        global $Language;
        $changed = true;
        foreach ($delete_depend as $depend) {
            $sql = "DELETE FROM artifact_dependencies WHERE artifact_depend_id IN (" . db_es($depend) . ")";
            $res = db_query($sql);
            if (! $res) {
                 $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_type', 'del_err', [$dependent, db_error($res)]));
                $changed = false;
            }
        }
        if ($changed) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact', 'depend_removed'));
        }
        return $changed;
    }

    /**
     * @param group_id: the group id of the new tracker
     * @param group_id_template: the template group id (used for the copy)
     * @param atid_template: the template artfact type id
     */
    public function copyArtifacts($from_atid)
    {
        $result = db_query("SELECT artifact_id FROM artifact WHERE group_artifact_id='" . db_ei($from_atid) . "'");
        while ($row = db_fetch_array($result)) {
            if (! $this->copyArtifact($from_atid, $row['artifact_id'])) {
                return false;
            }
        }
        return true;
    }

    public function copyArtifact($from_atid, $from_aid)
    {
        $aid = 0;
        $res = true;

        // copy common artifact fields
        $id_sharing = new TrackerIdSharingDao();
        if ($aid = $id_sharing->generateArtifactId()) {
            $result = db_query("INSERT INTO artifact (artifact_id, group_artifact_id,status_id,submitted_by,open_date,close_date,summary,details,severity) " .
                "SELECT $aid, " . db_ei($this->getID()) . ",status_id,submitted_by," . time() . ",close_date,summary,details,severity " .
                "FROM artifact " .
                "WHERE artifact_id='" . db_ei($from_aid) . "' " .
                "AND group_artifact_id='" . db_ei($from_atid) . "'");
            if (! $result || db_affected_rows($result) == 0) {
                $this->setError(db_error());
                return false;
            }

            // copy specific artifact fields
            $result = db_query("INSERT INTO artifact_field_value (field_id,artifact_id,valueInt,valueText,valueFloat,valueDate) " .
                "SELECT field_id," . db_ei($aid) . ",valueInt,valueText,valueFloat,valueDate " .
                "FROM artifact_field_value " .
                "WHERE artifact_id = '" . db_ei($from_aid) . "'");
            if (! $result || db_affected_rows($result) <= 0) {
                $this->setError(db_error());
                $res = false;
            }

            //copy cc addresses
            $result = db_query("INSERT INTO artifact_cc (artifact_id,email,added_by,comment,date) " .
                "SELECT " . db_ei($aid) . ",email,added_by,comment,date " .
                "FROM artifact_cc " .
                "WHERE artifact_id='" . db_ei($from_aid) . "'");
            if (! $result || db_affected_rows($result) <= 0) {
                $this->setError(db_error());
                $res = false;
            }

            //copy artifact files
            db_query("INSERT INTO artifact_file (artifact_id,description,bin_data,filename,filesize,filetype,adddate,submitted_by) " .
                "SELECT " . $aid . ",description,bin_data,filename,filesize,filetype,adddate,submitted_by " .
                "FROM artifact_file " .
                "WHERE artifact_id='" . db_ei($from_aid) . "'");
            if (! $result || db_affected_rows($result) <= 0) {
                $this->setError(db_error());
                $res = false;
            }

            return $res;
        }
        return false;
    }

    public function getReferenceDao()
    {
        return new ReferenceDao(CodendiDataAccess::instance());
    }

    public function getCrossReferenceDao(): CrossReferencesDao
    {
        return new CrossReferencesDao();
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

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


class ArtifactType // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
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

        $sql = 'SELECT '
        . 'user.user_id AS user_id, '
        . 'user_group.admin_flags '
        . 'FROM user,user_group WHERE '
        . 'user.user_id=user_group.user_id AND user_group.group_id=' . db_ei($this->Group->getID());
        $res = db_query($sql);

        while ($row = db_fetch_array($res)) {
            if ($row['admin_flags'] == 'A') {
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
			VALUES ('" . db_ei($this->getID()) . "','" . db_ei($id) . "'," . db_ei($value) . ')';
        $result = db_query($sql);
        if ($result && db_affected_rows($result) > 0) {
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
        $keywords = preg_split('/-/', $date);
        $ts       = mktime('23', '59', '59', $keywords[1], $keywords[2], $keywords[0]);
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
    public function userCanView($my_user_id = PFUser::ANONYMOUS_USER_ID)
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
    public function userCanSubmit($my_user_id = PFUser::ANONYMOUS_USER_ID)
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

        if (! $name || ! $description || ! $itemname || trim($name) == '' || trim($description) == '' || trim($itemname) == '') {
            $this->setError('ArtifactType: ' . $Language->getText('tracker_common_type', 'name_requ'));
            return false;
        }

        if (! preg_match('/^[a-zA-Z0-9_]+$/i', $itemname)) {
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

    // People who have once submitted a bug
    public function getSubmitters($with_display_preferences = false)
    {
        $sqlname = 'user.user_name';
        if ($with_display_preferences) {
            $uh      = new UserHelper();
            $sqlname = $uh->getDisplayNameSQLQuery();
        }
        $group_artifact_id = $this->getID();
        $sql               = '(SELECT DISTINCT user.user_id, ' . $sqlname . ' , user.user_name ' .
        'FROM user,artifact ' .
        'WHERE (user.user_id=artifact.submitted_by ' .
        "AND artifact.group_artifact_id='" . db_ei($group_artifact_id) . "') " .
        'ORDER BY user.user_name)';
        return $sql;
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
        $sql = 'insert into artifact_notification_event ' .
         'select event_id,' . db_ei($group_artifact_id) . ',event_label,`rank`,short_description_msg,description_msg ' .
         'from artifact_notification_event_default';

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
        $sql = 'insert into artifact_notification_role ' .
         'select role_id,' . db_ei($group_artifact_id) . ',role_label ,`rank`, short_description_msg,description_msg ' .
         'from artifact_notification_role_default';

        $res_insert = db_query($sql);

        if (! $res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_type', 'notif_fail'));
            return false;
        }

        return true;
    }

    /**
     * retrieves all artifacts
     * for a list of artifact_ids
     * @param change_ids: the list of artifact_ids for which we search the attached files
    */
    public function getDependencies($change_ids)
    {
        $sql = 'select d.artifact_depend_id,d.is_dependent_on_artifact_id,a.summary,ag.name,g.group_name, g.group_id ' .
        'from artifact_dependencies as d, artifact_group_list ag, groups g, artifact a ' .
        'where d.artifact_id in (' . db_es(implode(',', $change_ids)) . ') AND ' .
        'd.is_dependent_on_artifact_id = a.artifact_id AND ' .
                    'a.group_artifact_id = ag.group_artifact_id AND ' .
                    'ag.group_id = g.group_id ' .
        'order by is_dependent_on_artifact_id';
        return db_query($sql);
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

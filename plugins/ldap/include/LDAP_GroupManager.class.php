<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) cjt Systemsoftware AG, 2017. All Rights Reserved.
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

use Tuleap\LDAP\GroupSyncNotificationsManager;

/**
 * Define how a LDAP Group is manage and interaction with Codendi Groups.
 *
 * The current supported Codendi groups are:
 * - Project members
 * - User groups
 *
 * Most of the methods deal with LDAP group represented with their Distinguish
 * Name (DN). This is the path of the group in the LDAP directory.
 * For instance, if the config. define the LDAP group location (sys_ldap_grp_cn)
 * as: 'ou=groups,dc=codendi,dc=com', a valid group dn could be:
 * cn=codendi-devel,ou=groups,dc=codendi,dc=com
 *
 * Most of the methods require to have both Codendi Group database id and LDAP
 * group name set respectively with setId() and setGroupName().
 *
 */
abstract class LDAP_GroupManager
{

    public const NO_SYNCHRONIZATION      = 'never';
    public const AUTO_SYNCHRONIZATION    = 'auto';
    public const BIND_OPTION             = 'bind';
    public const PRESERVE_MEMBERS_OPTION = 'preserve_members';

    /**
     * @type LDAP
     */
    private $ldap;

    protected $groupName;
    protected $groupDn;
    protected $id;
    protected $usersToAdd;
    protected $usersToRemove;
    protected $usersNotImpacted;

    /**
     * @var GroupSyncNotificationsManager
     * */
    protected $notifications_manager;

    /**
     * @var LDAP_UserManager
     */
    protected $ldap_user_manager;

    /**
     * @var ProjectManager
     * */
    private $project_manager;

    /**
     * Constructor
     *
     * @param LDAP $ldap Ldap access object
     */
    public function __construct(
        LDAP $ldap,
        LDAP_UserManager $ldap_user_manager,
        ProjectManager $project_manager,
        GroupSyncNotificationsManager $notifications_manager
    ) {
        $this->ldap                  = $ldap;
        $this->ldap_user_manager     = $ldap_user_manager;
        $this->project_manager       = $project_manager;
        $this->notifications_manager = $notifications_manager;

        // Current group to treat: the ldap group name the Codendi group id
        // and the list of user to add/remove. If you want to manipulate several
        // groups in the same time, instanciate several objects.
        $this->groupName             = null;
        $this->groupDn               = null;
        $this->id                    = null;
        $this->usersToAdd            = null;
        $this->usersToRemove         = null;
        $this->usersNotImpacted      = null;
    }

    /**
     * Set LDAP group common name to be used for further processing
     *
     * @param String $groupName LDAP group identifier
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
    }

    /**
     * Set Codendi Group ID  to be used for further processing
     *
     * @param int $id Codendi Group ID
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set LDAP group distinguish name to be used for further processing
     *
     * @param String $groupDn LDAP group identifier
     *
     * @return Void
     */
    public function setGroupDn($groupDn)
    {
        $this->groupDn = $groupDn;
    }

    /**
     * Return the GroupDn for the current group name
     *
     * @return String|false
     */
    public function getGroupDn()
    {
        if ($this->groupDn === null) {
            $lri = $this->getLdap()->searchGroup($this->groupName);
            if ($lri && count($lri) === 1) {
                $this->groupDn = $lri->current()->getDn();
            } else {
                $this->groupDn = false;
            }
        }
        return $this->groupDn;
    }

    /**
     * Link and synchronize a Codendi Group and an LDAP group
     *
     * @param String  $option 'bind' or 'preserve_members'. The latter keeps ugroup membres that are not members of directory group.
     * @param String  $synchroPolicy   The option to synchrorize the ugroup nightly
     * @param bool $displayFeedback While set to true, it allows the feedback display
     *
     * @return void
     */
    public function bindWithLdap($option = self::BIND_OPTION, $synchroPolicy = self::NO_SYNCHRONIZATION, $displayFeedback = true)
    {
        if ($this->getGroupDn()) {
            $this->bindWithLdapGroup($option, $synchroPolicy);
            $this->syncMembersWithLdap($option);
        } else {
            if ($displayFeedback) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_manager_ldap_group_not_found', $this->groupName)
                );
            }
        }
    }

    /**
     * Synchronize Tuleap Group members and a given LDAP group.
     *
     * Add all users in LDAP group not members of Tuleap Group.
     * Remove all users members of Tuleap group, not members of LDAP group if $option param is equal to 'bind'.
     *
     * @param string $option tells whether it is a complete bind with the ldap group or user wants to preserve
     * @return bool
     */
    protected function syncMembersWithLdap($option)
    {
        $toAdd = $this->getUsersToBeAdded($option);
        if ($toAdd) {
            foreach ($toAdd as $userId) {
                $this->addUserToGroup($this->id, $userId);
            }
        }

        $toRemove = $this->getUsersToBeRemoved($option);
        if ($toRemove) {
            foreach ($toRemove as $userId) {
                $this->removeUserFromGroup($this->id, $userId);
            }
        }

        $this->notifications_manager->sendNotifications($this->project_manager->getProject($this->id), $toAdd, $toRemove);

        $this->resetUsersCollections();

        return true;
    }

    private function resetUsersCollections()
    {
        $this->usersToAdd       = null;
        $this->usersToRemove    = null;
        $this->usersNotImpacted = null;
    }

    /**
     * Compute user membership diffrencies between an LDAP and a Codendi group
     *
     * @param string $option tells whether it is a complete bind with the ldap group or user wants to preserve
     * current ugroup members after the bind.
     */
    protected function diffDbAndDirectory($option)
    {
        if ($this->getGroupDn()) {
            $ldapGroupMembers = $this->getLdapGroupMembersIds($this->groupDn);
            $ugroup_members   = $this->getDbGroupMembersIds($this->id);

            $this->usersToAdd       = array();
            $this->usersToRemove    = array();
            $this->usersNotImpacted = array();

            foreach ($ugroup_members as $userId) {
                if (! isset($ldapGroupMembers[$userId]) && $option != self::PRESERVE_MEMBERS_OPTION) {
                    $this->usersToRemove[$userId] = $userId;
                } else {
                    $this->usersNotImpacted[$userId] = $userId;
                }
            }
            foreach ($ldapGroupMembers as $userId) {
                if (!isset($this->usersNotImpacted[$userId])) {
                    $this->usersToAdd[$userId] = $userId;
                }
            }
        }
    }

    /**
     * Return the list of user ids that will be added to the group
     *
     * @param string $option 'bind' or 'preserve_members'.
     * @return Array
     */
    public function getUsersToBeAdded($option)
    {
        if ($this->usersToAdd === null) {
            $this->diffDbAndDirectory($option);
        }

        return $this->usersToAdd;
    }

    /**
     * Return the list of user ids that will be removed to the group
     *
     * @param string $option 'bind' or 'preserve_members'.
     * @return Array
     */
    public function getUsersToBeRemoved($option)
    {
        if ($this->usersToRemove === null) {
            $this->diffDbAndDirectory($option);
        }

        return $this->usersToRemove;
    }

    /**
     * Return the list of user ids that will not be impacted
     *
     * @param string $option 'bind' or 'preserve_members'.
     * @return Array
     */
    public function getUsersNotImpacted($option)
    {
        if ($this->usersNotImpacted === null) {
            $this->diffDbAndDirectory($option);
        }

        return $this->usersNotImpacted;
    }

    /**
     * Get EdUid of people member of the given LDAP group.
     *
     * @param String $group_dn LDAP group dn
     *
     * @return LDAPResult[]
     */
    public function getLdapGroupMembers($group_dn)
    {
        $ldap_ids          = array();
        $group_definition = $this->ldap->searchGroupMembers($group_dn);
        if (! $group_definition) {
            return array();
        }
        if ($this->canUseObjectClassToDistinguishUsersAndGroups()) {
            $this->getUserIdsWithObjectClass($group_definition, $ldap_ids);
        } elseif ($group_definition && $group_definition->count() === 1) {
            $this->getUserIdsWithGroupDnPatternMatching($group_definition, $ldap_ids);
        }
        return $ldap_ids;
    }

    private function canUseObjectClassToDistinguishUsersAndGroups()
    {
        return trim($this->ldap->getLDAPParam('grp_oc')) && trim($this->ldap->getLDAPParam('user_oc'));
    }

    private function getUserIdsWithObjectClass(LDAPResultIterator $group_definition, array &$ldap_ids)
    {
        foreach ($group_definition as $group_entry) {
            foreach ($group_entry->getGroupMembers() as $member_dn) {
                $object_classes = $this->getObjectClassesForDn($member_dn);
                if (count($object_classes) === 0) {
                    continue;
                }
                if ($this->isGroupObjectClass($object_classes)) {
                    $this->addGroupToLdapIds($member_dn, $ldap_ids);
                } elseif ($this->isUserObjectClass($object_classes)) {
                    $this->addUserToLdapIds($member_dn, $ldap_ids);
                }
            }
        }
    }

    private function isGroupObjectClass(array $object_classes)
    {
        $group_object_class = strtolower($this->ldap->getLDAPParam('grp_oc'));
        return in_array($group_object_class, $object_classes);
    }

    private function isUserObjectClass(array $object_classes)
    {
        $user_object_class = strtolower($this->ldap->getLDAPParam('user_oc'));
        return in_array($user_object_class, $object_classes);
    }

    private function getObjectClassesForDn($member_dn)
    {
        $object_class_results_iterator = $this->ldap->searchDn($member_dn, array('objectClass'));
        if ($object_class_results_iterator) {
            $object_classes = $object_class_results_iterator->get(0)->getAll('objectclass');
            return array_map('strtolower', $object_classes);
        }
        return array();
    }

    private function getUserIdsWithGroupDnPatternMatching(LDAPResultIterator $group_definition, array &$ldap_ids)
    {
        $ldap_group = $group_definition->current();
        $base_group_dn = strtolower($this->ldap->getLDAPParam('grp_dn'));
        foreach ($ldap_group->getGroupMembers() as $member_dn) {
            $member_dn = strtolower($member_dn);
            if (strpos($member_dn, $base_group_dn) !== false) {
                $this->addGroupToLdapIds($member_dn, $ldap_ids);
            } else {
                $this->addUserToLdapIds($member_dn, $ldap_ids);
            }
        }
    }

    private function addUserToLdapIds($member_dn, array &$ldap_ids)
    {
        $result = $this->getLdapEntryForUser($member_dn);
        if ($result) {
            $ldap_ids[$result->getEdUid()] = $result;
        }
    }

    private function addGroupToLdapIds($member_dn, array &$ldap_ids)
    {
        $ldap_users = $this->getLdapGroupMembers($member_dn);
        foreach ($ldap_users as $ldap_result) {
            if (! isset($ldap_ids[$ldap_result->getEdUid()])) {
                $ldap_ids[$ldap_result->getEdUid()] = $ldap_result;
            }
        }
    }

    private function getLdapEntryForUser($member_dn)
    {
        $attributes = array(
            $this->ldap->getLDAPParam('eduid'),
            $this->ldap->getLDAPParam('cn'),
            $this->ldap->getLDAPParam('uid'),
            $this->ldap->getLDAPParam('mail')
        );
        $ldap_user_result_iterator = $this->ldap->searchDn($member_dn, $attributes);
        if ($ldap_user_result_iterator && $ldap_user_result_iterator->count() === 1) {
            return $ldap_user_result_iterator->current();
        }
        return null;
    }

    /**
     * Get the Codendi user id of the people in given LDAP group
     *
     * This method takes an LDAP group Distinguish Name
     * - Fetch all the members of the group
     * - Creates their Codendi account if it doesn't exist
     * - Return the Codendi id of people
     *
     * @param String $groupDn LDAP DN of the group.
     *
     * @return Array
     */
    public function getLdapGroupMembersIds($groupDn)
    {
        $ldapGroupMembers = $this->getLdapGroupMembers($groupDn);
        $ldapGroupUserIds = $this->ldap_user_manager->getUserIdsForLdapUser($ldapGroupMembers);

        return $ldapGroupUserIds;
    }

    /**
     * @return Array of LDAP group attibutes
     * */
    private function getLdapGroupAttributes()
    {
        $ldap = $this->getLdap();
        $attrs = $ldap->getDefaultAttributes();
        if (isset($ldap->getLDAPParams()['grp_display_name'])) {
            $attrs[] = $ldap->getLDAPParams()['grp_display_name'];
        }
        return $attrs;
    }

    /**
     * Get LDAP group entry corresponding to Group id
     *
     * @param int $id Id of the Group
     *
     * @return LDAPResult
     */
    public function getLdapGroupByGroupId($id)
    {
        $ldapGroup = null;
        $dao = $this->getDao();
        $row = $dao->searchByGroupId($id);
        if ($row !== false) {
            $ldap = $this->getLdap();

            $attrs = $this->getLdapGroupAttributes();

            $groupDef = $ldap->searchDn($row['ldap_group_dn'], $attrs);
            if ($groupDef && $groupDef->count() == 1) {
                $ldapGroup = $groupDef->current();
            }
        }
        return $ldapGroup;
    }

    /**
     * Save link between Codendi Group and LDAP group
     *
     * @param  String  $bindOption
     * @param  String  $synchroPolicy
     *
     * @return bool
     */
    protected function bindWithLdapGroup($bindOption = self::BIND_OPTION, $synchroPolicy = self::NO_SYNCHRONIZATION)
    {
        $dao = $this->getDao();
        $row = $dao->searchByGroupId($this->id);
        if ($row !== false) {
            $dao->unlinkGroupLdap($this->id);
        }
        return $dao->linkGroupLdap($this->id, $this->groupDn, $bindOption, $synchroPolicy);
    }

    /**
     * Remove link between a Codendi Group and its LDAP group
     *
     * @return bool
     */
    public function unbindFromBindLdap()
    {
        return $this->getDao()->unlinkGroupLdap($this->id);
    }

    /**
     * Wrapper for LDAP object
     *
     * @return LDAP
     */
    protected function getLdap()
    {
        return $this->ldap;
    }

    /**
     * Add user to a Codendi Group
     *
     * @param int $id Codendi Group ID
     * @param int $userId User ID
     *
     * @return bool
     */
    abstract protected function addUserToGroup($id, $userId);

    /**
     * Remove user from a Codendi Group
     *
     * @param int $id Codendi Group ID
     * @param int $userId User ID
     *
     * @return bool
     */
    abstract protected function removeUserFromGroup($id, $userId);

    /**
     * Get the Codendi Group members ids
     *
     * @param int $id Id of the group
     *
     * @return Array
     */
    abstract protected function getDbGroupMembersIds($id);

    /**
     * Get manager's DataAccessObject
     *
     * @return DataAccessObject
     */
    abstract protected function getDao();
}

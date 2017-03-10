<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

require_once 'LDAP_UserManager.class.php';
require_once 'LDAP.class.php';

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

    const NO_SYNCHRONIZATION      = 'never';
    const AUTO_SYNCHRONIZATION    = 'auto';
    const BIND_OPTION             = 'bind';
    const PRESERVE_MEMBERS_OPTION = 'preserve_members';

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
     * Constructor
     * 
     * @param LDAP $ldap Ldap access object
     */
    public function __construct(LDAP $ldap)
    {
        $this->ldap             = $ldap;
        
        // Current group to treat: the ldap group name the Codendi group id
        // and the list of user to add/remove. If you want to manipulate several
        // groups in the same time, instanciate several objects.
        $this->groupName        = null;
        $this->groupDn          = null;
        $this->id               = null;
        $this->usersToAdd       = null;
        $this->usersToRemove    = null;
        $this->usersNotImpacted = null;
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
     * @param Integer $id Codendi Group ID
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
    public function setGroupDn($groupDn) {
        $this->groupDn = $groupDn;
    }

    /**
     * Return the GroupDn for the current group name 
     * 
     * @return String
     */
    public function getGroupDn() {
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
     * @param Boolean $displayFeedback While set to true, it allows the feedback display
     *
     * @return void
     */
    public function bindWithLdap($option = self::BIND_OPTION, $synchroPolicy = self::NO_SYNCHRONIZATION, $displayFeedback = true) {
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
     * @return Boolean
     */
    protected function syncMembersWithLdap($option) {
        $toAdd = $this->getUsersToBeAdded($option);
        if ($toAdd) {
            foreach($toAdd as $userId) {
                $this->addUserToGroup($this->id, $userId);
            }
        }

        $toRemove = $this->getUsersToBeRemoved($option);
        if ($toRemove) {
            foreach($toRemove as $userId) {
                $this->removeUserFromGroup($this->id, $userId);
            }
        }

        $this->resetUsersCollections();

        return true;
    }

    private function resetUsersCollections() {
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
    public function getUsersToBeAdded($option) {
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
    public function getUsersToBeRemoved($option) {
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
    public function getUsersNotImpacted($option) {
        if ($this->usersNotImpacted === null) {
            $this->diffDbAndDirectory($option);
        }

        return $this->usersNotImpacted;
    }

    /**
     * Get EdUid of people member of the given LDAP group.
     *
     * @param String $groupDn LDAP group dn
     * 
     * @return Array
     */
    public function getLdapGroupMembers($groupDn) 
    {
        $ldapIds  = array();
        $ldap     = $this->getLdap();
        $groupDef = $ldap->searchGroupMembers($groupDn);
        if ($groupDef && $groupDef->count() == 1) {
            $ldapGroup   = $groupDef->current();
            $baseGroupDn = strtolower($ldap->getLDAPParam('grp_dn'));
            foreach ($ldapGroup->getGroupMembers() as $memberDn) {
                $memberDn = strtolower($memberDn);
                if (strpos($memberDn, $baseGroupDn) !== false) {
                    $ids = $this->getLdapGroupMembers($memberDn);
                    $ldapIds = array_merge($ldapIds, $ids);
                } else {
                    // Assume it's a user definition
                    $attrs = array($ldap->getLDAPParam('eduid'),
                    $ldap->getLDAPParam('cn'),
                    $ldap->getLDAPParam('uid'),
                    $ldap->getLDAPParam('mail'));
                    $ldapUserResI = $ldap->searchDn($memberDn, $attrs);
                    if ($ldapUserResI && $ldapUserResI->count() == 1) {
                        $lr = $ldapUserResI->current();
                        $ldapIds[$lr->getEdUid()] = $lr;
                    }
                }
            }
        }
        return $ldapIds;
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
        $ldapUserManager = new LDAP_UserManager($this->getLdap(), LDAP_UserSync::instance());
        $ldapGroupMembers = $this->getLdapGroupMembers($groupDn);
        $ldapGroupUserIds = $ldapUserManager->getUserIdsForLdapUser($ldapGroupMembers);
        return $ldapGroupUserIds;
    }

    /**
     * Get LDAP group entry corresponding to Group id
     * 
     * @param Integer $id Id of the Group
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
            $groupDef = $ldap->searchDn($row['ldap_group_dn']);
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
     * @return Boolean
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
     * @return Boolean
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
     * @param Integer $id      Codendi Group ID
     * @param Integer $userId  User ID
     *
     * @return Boolean
     */
    protected abstract function addUserToGroup($id, $userId);

    /**
     * Remove user from a Codendi Group
     *
     * @param Integer $id      Codendi Group ID
     * @param Integer $userId  User ID
     *
     * @return Boolean
     */
    protected abstract function removeUserFromGroup($id, $userId);

    /**
     * Get the Codendi Group members ids
     *
     * @param Integer $id  Id of the group
     *
     * @return Array
     */
    protected abstract function getDbGroupMembersIds($id);

    /**
     * Get manager's DataAccessObject
     *
     * @return DataAccessObject
     */
    protected abstract function getDao();
}

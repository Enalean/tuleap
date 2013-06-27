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

require_once 'LDAP_UserDao.class.php';
require_once 'LDAP.class.php';
require_once 'LDAP_UserSync.class.php';
require_once 'LDAP_User.class.php';
require_once 'common/user/UserManager.class.php';
require_once 'common/system_event/SystemEventManager.class.php';
require_once 'system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php';

/**
 * Manage interaction between an LDAP group and Codendi user_group.
 */
class LDAP_UserManager {
    const EVENT_UPDATE_LOGIN = 'PLUGIN_LDAP_UPDATE_LOGIN';
    
    /**
     * @type LDAP
     */
    private $ldap;

    /**
     * @var Array of LDAPResult
     */
    private $ldapResultCache = array();

    /**
     * @var Array of User
     */
    private $usersLoginChanged = array();

    /**
     * Constructor
     *
     * @param LDAP $ldap Ldap access object
     */
    function __construct(LDAP $ldap) {
        $this->ldap = $ldap;
    }

    /**
     * Create an LDAP_User object out of a regular user if this user comes as
     * a corresponding LDAP entry
     *
     * @param PFUser $user
     *
     * @return LDAP_User|null
     */
    public function getLDAPUserFromUser(PFUser $user) {
        $ldap_result = $this->getLdapFromUser($user);
        if ($ldap_result) {
            return new LDAP_User($user, $ldap_result);
        }
        return null;
    }

    /**
     * Get LDAPResult object corresponding to an LDAP ID
     *
     * @param  $ldapId    The LDAP identifier
     * @return LDAPResult
     */
    function getLdapFromLdapId($ldapId) {
        if (!isset($this->ldapResultCache[$ldapId])) {
            $lri = $this->getLdap()->searchEdUid($ldapId);
            if ($lri->count() == 1) {
                $this->ldapResultCache[$ldapId] = $lri->current();
            } else {
                $this->ldapResultCache[$ldapId] = false;
            }
        }
        return $this->ldapResultCache[$ldapId];
    }

    /**
     * Get LDAPResult object corresponding to a User object
     * 
     * @param  PFUser $user
     * @return LDAPResult
     */
    function getLdapFromUser($user) {
        if ($user && !$user->isAnonymous()) {
            return $this->getLdapFromLdapId($user->getLdapId());
        } else {
            return false;
        }
    }

    /**
     * Get LDAPResult object corresponding to a user name
     *
     * @param  $userName  The user name
     * @return LDAPResult
     */
    function getLdapFromUserName($userName) {
        $user = $this->getUserManager()->getUserByUserName($userName);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get LDAPResult object corresponding to a user id
     *
     * @param  $userId    The user id
     * @return LDAPResult
     */
    function getLdapFromUserId($userId) {
        $user = $this->getUserManager()->getUserById($userId);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get a User object from an LDAP result
     *
     * @param LDAPResult $lr The LDAP result
     *
     * @return PFUser
     */
    function getUserFromLdap(LDAPResult $lr) {
        $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
        if(!$user) {
            $user = $this->createAccountFromLdap($lr);
        }
        return $user;
    }

    /**
     * Get the list of Codendi users corresponding to the given list of LDAP users.
     *
     * When a user doesn't exist, his account is created automaticaly.
     *
     * @param Array $ldapIds
     * @return Array
     */
    function getUserIdsForLdapUser($ldapIds) {
        $userIds = array();
        $dao = $this->getDao();
        foreach($ldapIds as $lr) {
            $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
            if($user) {
                $userIds[$user->getId()] = $user->getId();
            } else {
                $user = $this->createAccountFromLdap($lr);
                if ($user) {
                    $userIds[$user->getId()] = $user->getId();
                }
            }
        }
        return $userIds;
    }

    /**
     * Return an array of user ids corresponding to the give list of user identifiers
     *
     * @param String $userList A comma separated list of user identifiers
     *
     * @return Array
     */
    function getUserIdsFromUserList($userList) {
        $userIds = array();
        $userList = array_map('trim', split('[,;]', $userList));
        foreach($userList as $u) {
            $user = $this->getUserManager()->findUser($u);
            if($user) {
                $userIds[] = $user->getId();
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_ldap', 'user_manager_user_not_found', $u));
            }
        }
        return $userIds;
    }

    /**
     * Return LDAP logins stored in DB corresponding to given userIds.
     * 
     * @param Array $userIds Array of user ids
     * @return Array ldap logins
     */
    function getLdapLoginFromUserIds(array $userIds) {
        $dao = $this->getDao();
        return $dao->searchLdapLoginFromUserIds($userIds);
    }

    /**
     * Generate a valid, not used Codendi login from a string.
     *
     * @param String $uid User identifier
     * @return String
     */
    function generateLogin($uid) {
        $account_name = $this->getLoginFromString($uid);
        $uid = $account_name;
        $i=2;
        while($this->userNameIsAvailable($uid) !== true) {
            $uid = $account_name.$i;
            $i++;
        }
        return $uid;
    }

    /**
     * Check if a given name is not already a user name or a project name
     *
     * This should be in UserManager
     *
     * @param String $name Name to test
     * @return Boolean
     */
    function userNameIsAvailable($name) {
        $dao = $this->getDao();
        return $dao->userNameIsAvailable($name);
    }

    /**
     * Return a valid Codendi user_name from a given string
     *
     * @param String $uid Identifier to convert
     * @return String
     */
    function getLoginFromString($uid) {
        $name = utf8_decode($uid);
        $name = strtr($name, utf8_decode(' .:;,?%^*(){}[]<>+=$àâéèêùûç'), '____________________aaeeeuuc');
        $name = str_replace("'", "", $name);
        $name = str_replace('"', "", $name);
        $name = str_replace('/', "", $name);
        $name = str_replace('\\', "", $name);
        return strtolower($name);
    }

    /**
     * Create user account based on LDAPResult info.
     *
     * @param  LDAPResult $lr
     * @return PFUser
     */
    function createAccountFromLdap(LDAPResult $lr) {
    	return $this->createAccount($lr->getEdUid(), $lr->getLogin(), $lr->getCommonName(), $lr->getEmail());
    }

    /**
     * Create user account based on LDAP info.
     *
     * @param  String $eduid
     * @param  String $uid
     * @param  String $cn
     * @param  String $email
     * @return PFUser
     */
    function createAccount($eduid, $uid, $cn, $email) {
        if(trim($uid) == '' || trim($eduid) == '') {
            return false;
        }

        $user = new PFUser();
        $user->setUserName($this->generateLogin($uid));
        $user->setLdapId($eduid);
        $user->setRealName($cn);
        $user->setEmail($email);
        // Generates a pseudo-random password. Its not full secure but its
        // better than nothing.
        $user->setPassword(md5((string)mt_rand(10000, 999999).time()));

        // Default LDAP
        $user->setStatus($this->getLdap()->getLDAPParam('default_user_status'));
        $user->setRegisterPurpose('LDAP');
        $user->setUnixStatus('S');
        $user->setTimezone('GMT');
        $user->setLanguageID($GLOBALS['Language']->getText('conf','language_id'));

        $um = $this->getUserManager();
        $u  = $um->createAccount($user);
        if ($u) {
            $u = $um->getUserById($user->getId());
            // Create an entry in the ldap user db
            $ldapUserDao = $this->getDao();
            $ldapUserDao->createLdapUser($u->getId(), 0, $uid);
            return $u;
        }
        return false;
    }

    /**
     * Synchronize user account with LDAP informations
     *
     * @param  PFUser       $user
     * @param  LDAPResult $lr
     * @param  String     $password
     * @return Boolean
     */
    function synchronizeUser(PFUser $user, LDAPResult $lr, $password) {
        $user->setPassword($password);

        $sync = LDAP_UserSync::instance();
        $sync->sync($user, $lr);

        // Perform DB update
        $userUpdated = $this->getUserManager()->updateDb($user);
        
        $ldapUpdated = true;
        $user_id    = $this->getLdapLoginFromUserIds(array($user->getId()))->getRow();
        if ($user_id['ldap_uid'] != $lr->getLogin()) {
            $ldapUpdated = $this->updateLdapUid($user, $lr->getLogin());
            $this->triggerRenameOfUsers();
        }
        
        return ($userUpdated || $ldapUpdated);
    }

    /**
     * Store new LDAP login in database
     * 
     * Force update of SVNAccessFile in project the user belongs to as 
     * project member or user group member
     * 
     * @param PFUser    $user    The user to update 
     * @param String  $ldapUid New LDAP login
     * 
     * @return Boolean
     */
    function updateLdapUid(PFUser $user, $ldapUid) {
        if ($this->getDao()->updateLdapUid($user->getId(), $ldapUid)) {
            $this->addUserToRename($user);
            return true;
        }
        return false;
    }

    /**
     * Get the list of users whom LDAP uid changed
     * 
     * @return Array of User
     */
    public function getUsersToRename() {
        return $this->usersLoginChanged;
    }

    /**
     * Add a user whom login changed to the rename pipe
     * 
     * @param PFUser $user A user to rename
     */
    public function addUserToRename(PFUser $user) {
        $this->usersLoginChanged[] = $user;
    }

    /**
     * Create PLUGIN_LDAP_UPDATE_LOGIN event if there are user login updates pending
     */
    public function triggerRenameOfUsers() {
        if (count($this->usersLoginChanged)) {
            $userIds = array();
            foreach ($this->usersLoginChanged as $user) {
                $userIds[] = $user->getId();
            }
            $sem = $this->getSystemEventManager();
            $sem->createEvent(self::EVENT_UPDATE_LOGIN, implode(SystemEvent::PARAMETER_SEPARATOR, $userIds), SystemEvent::PRIORITY_MEDIUM);
        }
    }

    /**
     * Wrapper for DAO
     *
     * @return LDAP_UserDao
     */
    function getDao()
    {
        return new LDAP_UserDao(CodendiDataAccess::instance());
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
     * Wrapper for UserManager object
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }
    
    /**
     * Wrapper for SystemEventManager object
     *
     * @return SystemEventManager
     */
    protected function getSystemEventManager()
    {
        return SystemEventManager::instance();
    }
}

?>
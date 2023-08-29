<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\LDAP\Exception\IdentifierTypeNotFoundException;
use Tuleap\LDAP\Exception\IdentifierTypeNotRecognizedException;
use Tuleap\User\DataIncompatibleWithUsernameGenerationException;
use Tuleap\User\UserNameNormalizer;

/**
 * Manage interaction between an LDAP group and Codendi user_group.
 */
class LDAP_UserManager
{
    public const EVENT_UPDATE_LOGIN = 'PLUGIN_LDAP_UPDATE_LOGIN';

    /**
     * @type LDAP
     */
    private $ldap;

    /**
     * @var array<string, LDAPResult|false>
     */
    private $ldapResultCache = [];

    /**
     * @var PFUser[]
     */
    private $usersLoginChanged = [];

    /**
     * @var LDAP_UserSync
     */
    private $user_sync;

    public function __construct(
        LDAP $ldap,
        LDAP_UserSync $user_sync,
        private UserNameNormalizer $username_generator,
        private \Tuleap\User\PasswordVerifier $user_password_verifier,
    ) {
        $this->ldap      = $ldap;
        $this->user_sync = $user_sync;
    }

    /**
     * Create an LDAP_User object out of a regular user if this user comes as
     * a corresponding LDAP entry
     */
    public function getLDAPUserFromUser(PFUser $user): ?LDAP_User
    {
        $ldap_result = $this->getLdapFromUser($user);
        if ($ldap_result) {
            return new LDAP_User($user, $ldap_result);
        }
        return null;
    }

    /**
     * Get LDAPResult object corresponding to an LDAP ID
     *
     * @return LDAPResult|false
     */
    public function getLdapFromLdapId($ldapId)
    {
        if (! isset($this->ldapResultCache[$ldapId])) {
            $lri = $this->getLdap()->searchEdUid($ldapId);
            if ($lri && $lri->count() == 1) {
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
     * @return LDAPResult|false
     */
    public function getLdapFromUser($user)
    {
        if ($user && ! $user->isAnonymous()) {
            return $this->getLdapFromLdapId($user->getLdapId());
        } else {
            return false;
        }
    }

    /**
     * Get LDAPResult object corresponding to a user name
     *
     * @param  string $userName  The user name
     * @return LDAPResult|false
     */
    public function getLdapFromUserName($userName)
    {
        $user = $this->getUserManager()->getUserByUserName($userName);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get LDAPResult object corresponding to a user id
     *
     * @param  int $userId    The user id
     * @return LDAPResult|false
     */
    public function getLdapFromUserId($userId)
    {
        $user = $this->getUserManager()->getUserById($userId);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get a User object from an LDAP result
     *
     * @param LDAPResult $lr The LDAP result
     *
     * @return PFUser|false
     */
    public function getUserFromLdap(LDAPResult $lr)
    {
        $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
        if (! $user) {
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
    public function getUserIdsForLdapUser($ldapIds)
    {
        $userIds = [];
        $dao     = $this->getDao();
        foreach ($ldapIds as $lr) {
            $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
            if ($user) {
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
    public function getUserIdsFromUserList($userList)
    {
        $userIds  = [];
        $userList = array_map('trim', preg_split('/[,;]/', $userList));
        foreach ($userList as $u) {
            $user = $this->getUserManager()->findUser($u);
            if ($user) {
                $userIds[] = $user->getId();
            } else {
                $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-ldap', 'User not found: %1$s'), $u));
            }
        }
        return $userIds;
    }

    /**
     * Return LDAP logins stored in DB corresponding to given userIds.
     *
     * @param array $userIds Array of user ids
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface ldap logins
     */
    public function getLdapLoginFromUserIds(array $userIds)
    {
        $dao = $this->getDao();
        return $dao->searchLdapLoginFromUserIds($userIds);
    }

    /**
     * Check if a given name is not already a user name or a project name
     *
     * This should be in UserManager
     *
     * @param String $name Name to test
     * @return bool
     */
    public function userNameIsAvailable($name)
    {
        $dao = $this->getDao();
        return $dao->userNameIsAvailable($name);
    }

    /**
     * Create user account based on LDAPResult info.
     *
     * @return PFUser|false
     */
    public function createAccountFromLdap(LDAPResult $lr)
    {
        $user = $this->createAccount($lr->getEdUid(), $lr->getLogin(), $lr->getCommonName(), $lr->getEmail());
        return $user;
    }

    /**
     * Create user account based on LDAP info.
     *
     * @param  String $eduid
     * @param  String $uid
     * @param  String $cn
     * @param  String $email
     * @return PFUser|false
     */
    public function createAccount($eduid, $uid, $cn, $email)
    {
        if (trim($uid) == '' || trim($eduid) == '') {
            return false;
        }

        $user = new PFUser();
        try {
            $user->setUserName($this->username_generator->normalize($uid));
        } catch (DataIncompatibleWithUsernameGenerationException $exception) {
            return false;
        }

        $user->setLdapId($eduid);
        $user->setRealName($cn);
        $user->setEmail($email);
        $mail_confirm_code_generator = new \Tuleap\User\MailConfirmationCodeGenerator(
            $this->getUserManager(),
            new RandomNumberGenerator()
        );
        $mail_confirm_code           = $mail_confirm_code_generator->getConfirmationCode();
        $user->setConfirmHash($mail_confirm_code);

        // Default LDAP
        $user->setStatus($this->getLdap()->getLDAPParam('default_user_status'));
        $user->setRegisterPurpose('LDAP');
        $user->setTimezone('UTC');
        $user->setLanguageID(ForgeConfig::get(BaseLanguage::CONFIG_KEY, BaseLanguage::DEFAULT_LANG));

        $um   = $this->getUserManager();
        $user = $um->createAccount($user);
        if (! $user) {
            return false;
        }

        return $user;
    }

    public function createLdapUser(LDAP_User $ldap_user): void
    {
        if (! $this->getDao()->hasLoginConfirmationDate($ldap_user)) {
            $this->getDao()->createLdapUser($ldap_user->getId(), 0, $ldap_user->getUid());
        }
    }

    /**
     * @throws LDAP_AuthenticationFailedException
     * @throws LDAP_UserNotFoundException
     */
    public function authenticate($username, ConcealedString $password): ?PFUser
    {
        if (! $this->ldap->authenticate($username, $password)) {
            throw new LDAP_AuthenticationFailedException();
        }

        $ldap_user = $this->getUserFromServer($username);
        $user      = $this->getUserManager()->getUserByLdapId($ldap_user->getEdUid());

        if ($user === null) {
            $user = $this->createAccountFromLdap($ldap_user);
        }

        if ($user) {
            $this->synchronizeUser($user, $ldap_user, $password);
            return $user;
        }

        return null;
    }

    private function mergeDefaultAttributesAndSiteAttributes()
    {
        return array_values(
            array_unique(
                array_merge(
                    $this->ldap->getDefaultAttributes(),
                    $this->user_sync->getSyncAttributes($this->ldap)
                )
            )
        );
    }

    private function getUserFromServer($username)
    {
        $ldap_results_iterator = $this->ldap->searchLogin(
            $username,
            $this->mergeDefaultAttributesAndSiteAttributes()
        );

        if ($ldap_results_iterator === false || count($ldap_results_iterator) !== 1) {
            throw new LDAP_UserNotFoundException();
        }

        return $ldap_results_iterator->current();
    }

    /**
     * Synchronize user account with LDAP informations
     */
    public function synchronizeUser(PFUser $user, LDAPResult $lr, ConcealedString $password): void
    {
        $user->setPassword($password);

        $sync = LDAP_UserSync::instance();
        if ($sync->sync($user, $lr) || ! $this->user_password_verifier->verifyPassword($user, $password)) {
            $this->getUserManager()->updateDb($user);
        }

        $user_id = $this->getLdapLoginFromUserIds([$user->getId()])->getRow();
        if ($user_id['ldap_uid'] != $lr->getLogin()) {
            $this->updateLdapUid($user, $lr->getLogin());
            $this->triggerRenameOfUsers();
        }
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
     * @return bool
     */
    public function updateLdapUid(PFUser $user, $ldapUid)
    {
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
    public function getUsersToRename()
    {
        return $this->usersLoginChanged;
    }

    /**
     * Add a user whom login changed to the rename pipe
     *
     * @param PFUser $user A user to rename
     */
    public function addUserToRename(PFUser $user)
    {
        $this->usersLoginChanged[] = $user;
    }

    /**
     * Create PLUGIN_LDAP_UPDATE_LOGIN event if there are user login updates pending
     */
    public function triggerRenameOfUsers()
    {
        if (count($this->usersLoginChanged)) {
            $userIds = [];
            foreach ($this->usersLoginChanged as $user) {
                $userIds[] = $user->getId();
            }
            $sem = $this->getSystemEventManager();
            $sem->createEvent(self::EVENT_UPDATE_LOGIN, implode(SystemEvent::PARAMETER_SEPARATOR, $userIds), SystemEvent::PRIORITY_MEDIUM);
        }
    }

    /**
     * Return array of users that will be suspended
     *
     * @return array of PFUser
     *
     */
    public function getUsersToBeSuspended()
    {
        $users_to_be_suspended = [];
        $active_users          = $this->getDao()->getActiveUsers();
        foreach ($active_users as $active_user) {
            if ($this->isUserDeletedFromLdap($active_user)) {
                $user = new PFUser($active_user);
                array_push($users_to_be_suspended, $user);
            }
        }
        return $users_to_be_suspended;
    }

    /**
     * Return number of active users
     *
     * @return int
     *
     */
    public function getNbrActiveUsers()
    {
        $row = $this->getDao()->getNbrActiveUsers()->getRow();
        return $row["count"];
    }

    /**
     * Return true if users could be suspended
     *
     * @param int $nbr_all_users
     *
     * @return bool
     *
     */
    public function areUsersSupendable($nbr_all_users)
    {
        $nbr_users_to_suspend = count($this->getUsersToBeSuspended());
        if ((! $threshold_users_suspension = $this->ldap->getLDAPParam('threshold_users_suspension')) || $nbr_users_to_suspend == 0) {
            return true;
        }
        return $this->checkThreshold($nbr_users_to_suspend, $nbr_all_users);
    }

    /**
     * Check that threshold is upper then percentage of users that will be suspended
     *
     * @param int $nbr_users_to_suspend
     * @param int $nbr_all_users
     *
     * @return bool
     *
     */
    public function checkThreshold($nbr_users_to_suspend, $nbr_all_users)
    {
        if ($nbr_users_to_suspend == 0 || $nbr_all_users == 0) {
            return true;
        }
        $percentage_users_to_suspend = ($nbr_users_to_suspend / $nbr_all_users) * 100;
        $threshold_users_suspension  = $this->ldap->getLDAPParam('threshold_users_suspension');
        $logger                      = new \Tuleap\LDAP\LdapLogger();
        if ($percentage_users_to_suspend <= $threshold_users_suspension) {
            $logger->info("[LDAP] Percentage of suspended users is ( " . $percentage_users_to_suspend . "% ) and threshold is ( " . $threshold_users_suspension . "% )");
            $logger->info("[LDAP] Number of suspended users is ( " . $nbr_users_to_suspend . " ) and number of active users is ( " . $nbr_all_users . " )");
            return true;
        } else {
            $logger->warning("[LDAP] Users not suspended: the percentage of users to suspend is ( " . $percentage_users_to_suspend . "% ) higher then threshold ( " . $threshold_users_suspension . "% )");
            $logger->warning("[LDAP] Number of users not suspended is ( " . $nbr_users_to_suspend . " ) and number of active users is ( " . $nbr_all_users . " )");
            return false;
        }
    }

    /**
     * Return true if user is deleted from ldap server
     *
     * @param array $row
     *
     * @return bool
     *
     */
    public function isUserDeletedFromLdap($row)
    {
        $ldap_query = $this->ldap->getLDAPParam('eduid') . '=' . ldap_escape($row['ldap_id'], '', LDAP_ESCAPE_FILTER);
        $attributes = $this->user_sync->getSyncAttributes($this->ldap);
        $ldapSearch = false;

        foreach (explode(';', $this->ldap->getLDAPParam('people_dn') ?? '') as $people_dn) {
            $ldapSearch = $this->ldap->search($people_dn, $ldap_query, LDAP::SCOPE_ONELEVEL, $attributes);
            if ($ldapSearch !== false && count($ldapSearch) === 1) {
                break;
            }
        }
        if ($this->ldap->getErrno() === LDAP::ERR_SUCCESS && $ldapSearch) {
            if (count($ldapSearch) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Wrapper for DAO
     *
     * @return LDAP_UserDao
     */
    public function getDao()
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

    /**
     * @return PFUser|null
     */
    public function getUserByIdentifier($identifier)
    {
        $separator_position = strpos($identifier, ':');
        $type               = strtolower(substr($identifier, 0, $separator_position));
        $value              = strtolower(substr($identifier, $separator_position + 1));

        if (! $type) {
            throw new IdentifierTypeNotFoundException();
        }

        $ldap = $this->getLdap();
        $lri  = null;
        switch ($type) {
            case 'ldapid':
                $lri = $ldap->searchEdUid($value);
                break;
            case 'ldapdn':
                $lri = $ldap->searchDn($value);
                break;
            case 'ldapuid':
                $lri = $ldap->searchLogin($value);
                break;
            default:
                throw new IdentifierTypeNotRecognizedException();
        }

        if ($lri === false) {
            return null;
        }

        return $this->getUserFromLdapIterator($lri);
    }

    /**
     * Get a User object from an LDAP iterator
     *
     * @param LDAPResultIterator $lri An LDAP result iterator
     *
     * @return PFUser|null
     */
    public function getUserFromLdapIterator(?LDAPResultIterator $lri = null)
    {
        if ($lri && count($lri) === 1 && (($user = $this->getUserFromLdap($lri->current())) !== false)) {
            return $user;
        }

        return null;
    }
}

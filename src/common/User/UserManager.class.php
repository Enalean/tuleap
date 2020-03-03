<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\CookieManager;
use Tuleap\Dashboard\User\AtUserCreationDefaultWidgetsCreator;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\User\Account\DisplaySecurityController;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use Tuleap\User\InvalidSessionException;
use Tuleap\User\SessionManager;
use Tuleap\User\SessionNotCreatedException;
use Tuleap\User\UserConnectionUpdateEvent;
use Tuleap\User\UserRetrieverByLoginNameEvent;
use Tuleap\Widget\WidgetFactory;

class UserManager // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    /**
     * User with id lower than 100 are considered specials (siteadmin, null,
     * etc).
     */
    public const SPECIAL_USERS_LIMIT = 100;

    public $_users           = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public $_userid_bynames  = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public $_userid_byldapid = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    private $_userdao         = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    private $_currentuser     = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * @var User_PendingUserNotifier
     */
    private $pending_user_notifier;

    public function __construct(User_PendingUserNotifier $pending_user_notifier)
    {
        $this->pending_user_notifier = $pending_user_notifier;
    }

    protected static $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    /**
     * @return UserManager
     */
    public static function instance()
    {
        if (!isset(self::$_instance)) {
            $userManager = self::class;
            self::$_instance = new $userManager(
                new User_PendingUserNotifier()
            );
        }

        return self::$_instance;
    }

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    /**
     * @return UserDao
     */
    protected function getDao()
    {
        if (! $this->_userdao) {
            $this->_userdao = new UserDao();
        }

        return $this->_userdao;
    }

    public function setDao(UserDao $dao)
    {
        $this->_userdao = $dao;
    }

    public function getUserAnonymous()
    {
        return $this->getUserbyId(0);
    }


    /**
     * @param int the user_id of the user to find
     * @return PFUser|null if the user is not found
     */
    public function getUserById($user_id)
    {
        if (!isset($this->_users[$user_id])) {
            if (is_numeric($user_id)) {
                if ($user_id == 0) {
                    $this->_users[$user_id] = $this->getUserInstanceFromRow(array('user_id' => 0));
                } else {
                    $u = $this->getUserByIdWithoutCache($user_id);
                    if ($u) {
                        $this->_users[$u->getId()] = $u;
                        $this->_userid_bynames[$u->getUserName()] = $user_id;
                    } else {
                        $this->_users[$user_id] = null;
                    }
                }
            } else {
                $this->_users[$user_id] = null;
            }
        }
        return $this->_users[$user_id];
    }

    private function getUserByIdWithoutCache($id)
    {
        $dar = $this->getDao()->searchByUserId($id);
        if (count($dar)) {
            return $this->getUserInstanceFromRow($dar->getRow());
        }
        return null;
    }

    public function countAllUsers()
    {
        return $this->getDao()->countAllUsers();
    }

    public function countAllAliveUsers()
    {
        return $this->getDao()->countAllAliveUsers();
    }


    public function countAliveRegisteredUsersBefore($timestamp)
    {
        return $this->getDao()->countAliveUsersRegisteredBefore($timestamp);
    }

    public function countUsersByStatus($status)
    {
        $dar = $this->getDao()->searchByStatus($status);

        return $this->getDao()->foundRows();
    }

    /**
     * @param string the user_name of the user to find
     * @return PFUser or null if the user is not found
     */
    public function getUserByUserName($user_name)
    {
        if (!isset($this->_userid_bynames[$user_name])) {
            $dar = $this->getDao()->searchByUserName($user_name);
            if ($row = $dar->getRow()) {
                $u = $this->getUserInstanceFromRow($row);
                $this->_users[$u->getId()] = $u;
                $this->_userid_bynames[$user_name] = $u->getId();
            } else {
                $this->_userid_bynames[$user_name] = null;
            }
        }
        $user = null;
        if ($this->_userid_bynames[$user_name] !== null) {
            $user = $this->_users[$this->_userid_bynames[$user_name]];
        }
        return $user;
    }

    public function _getUserInstanceFromRow($row) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getUserInstanceFromRow($row);
    }

    public function getUserInstanceFromRow($row)
    {
        if (isset($row['user_id']) && $row['user_id'] < self::SPECIAL_USERS_LIMIT) {
            $user = null;
            EventManager::instance()->processEvent(Event::USER_MANAGER_GET_USER_INSTANCE, array('row' => $row, 'user' => &$user));
            if ($user) {
                return $user;
            }
        }
        return new PFUser($row);
    }

    /**
     * @param  string Ldap identifier
     * @return PFUser or null if the user is not found
     */
    public function getUserByLdapId($ldapId)
    {
        if ($ldapId == null) {
            return null;
        }
        if (!isset($this->_userid_byldapid[$ldapId])) {
            $dar = $this->getDao()->searchByLdapId($ldapId);
            if ($row = $dar->getRow()) {
                $u = $this->getUserInstanceFromRow($row);
                $this->_users[$u->getId()] = $u;
                $this->_userid_byldapid[$ldapId] = $u->getId();
            } else {
                $this->_userid_byldapid[$ldapId] = null;
            }
        }
        $user = null;
        if ($this->_userid_byldapid[$ldapId] !== null) {
            $user = $this->_users[$this->_userid_byldapid[$ldapId]];
        }
        return $user;
    }

    /**
     * Try to find a user that match the given identifier
     *
     * @param String $ident A user identifier
     *
     * @return PFUser
     */
    public function findUser($ident)
    {
        $user = null;
        if (! $ident) {
            return $user;
        }
        $eParams = array('ident' => $ident,
                         'user'  => &$user);
        $this->_getEventManager()->processEvent('user_manager_find_user', $eParams);
        if (!$user) {
            // No valid user found, try an internal lookup for username
            if (preg_match('/^(.*) \((.*)\)$/', $ident, $matches)) {
                if (trim($matches[2]) != '') {
                    $ident = $matches[2];
                } else {
                    //$user  = $this->getUserByCommonName($matches[1]);
                }
            }

            $user = $this->getUserByUserName($ident);
            //@todo: lookup based on email address ?
            //@todo: lookup based on common name ?
        }

        return $user;
    }

    /**
     * Get a user by the string identifier this user uses
     * to log in.
     *
     * @return PFUser|null
     */
    public function getUserByLoginName($login_name)
    {
        $user_retriever_by_login_name_event = new UserRetrieverByLoginNameEvent($login_name);

        $this->_getEventManager()->processEvent($user_retriever_by_login_name_event);

        if ($user_retriever_by_login_name_event->getUser() !== null) {
            return $user_retriever_by_login_name_event->getUser();
        }

        return $this->getUserByUserName($login_name);
    }

/**
 * Returns an array of user ids that match the given string
 *
 * @param String $search comma-separated users' names.
 *
 * @return Array
 */
    public function getUserIdsList($search)
    {
        $userArray = explode(',', $search);
        $users = array();
        foreach ($userArray as $user) {
            $user = $this->findUser($user);
            if ($user) {
                $users[] = $user->getId();
            }
        }
        return $users;
    }

    /**
     * @return PaginatedUserCollection
     */
    public function getPaginatedUsersByUsernameOrRealname($words, $exact, $offset, $limit)
    {
        $users = array();
        foreach ($this->getDao()->searchGlobalPaginated($words, $exact, $offset, $limit) as $user) {
            $users[] = $this->getUserInstanceFromRow($user);
        }
        return new PaginatedUserCollection($users, $this->getDao()->foundRows());
    }

    /**
     * Returns the user that have the given email address.
     * Returns null if no account is found.
     * Throws an exception if several accounts share the same email address.
     *
     * @param String $email mail address of the user to retrieve
     *
     * @return PFUser or null if no user found
     */
    public function getUserByEmail($email)
    {
        return $this->getUserCollectionByEmails([$email])->getUserByEmail($email);
    }

    public function getAllUsersByEmail($email)
    {
        $users = array();
        foreach ($this->getDao()->searchByEmail($email) as $user) {
            $users[] = $this->getUserInstanceFromRow($user);
        }
        return $users;
    }

    /**
     * @return \Tuleap\User\UserEmailCollection
     */
    public function getUserCollectionByEmails(array $emails)
    {
        $users = [];
        foreach ($this->getDao()->searchByEmailList($emails) as $user_row) {
            $users[] = $this->getUserInstanceFromRow($user_row);
        }
        return new \Tuleap\User\UserEmailCollection(...$users);
    }

    /**
     * Returns a user that correspond to an identifier
     * The identifier can be prepended with a type.
     * Ex:
     *     ldapId:ed1234
     *     email:manu@st.com
     *     id:1234
     *     manu (no type specified means that the identifier is a username)
     *
     * @param string $identifier User identifier
     *
     * @return PFUser
     */
    public function getUserByIdentifier($identifier)
    {
        $user = null;

        $em = $this->_getEventManager();
        $tokenFoundInPlugins = false;
        $params = array('identifier' => $identifier,
                        'user'       => &$user,
                        'tokenFound' => &$tokenFoundInPlugins);
        $em->processEvent('user_manager_get_user_by_identifier', $params);

        if (!$tokenFoundInPlugins) {
            // Guess identifier type
            $separatorPosition = strpos($identifier, ':');
            if ($separatorPosition === false) {
                // identifier = username
                $user = $this->getUserByUserName($identifier);
            } else {
                // identifier = type:value
                $identifierType = substr($identifier, 0, $separatorPosition);
                $identifierValue = substr($identifier, $separatorPosition + 1);

                switch ($identifierType) {
                    case 'id':
                        $user = $this->getUserById($identifierValue);
                        break;
                    case 'email': // Use with caution, a same email can be shared between several accounts
                        try {
                            $user = $this->getUserByEmail($identifierValue);
                        } catch (Exception $e) {
                        }
                        break;
                }
            }
        }
        return $user;
    }

    /**
     * Get a user with the string genereated at user creation
     *
     * @param String $hash
     *
     * @return PFUser
     */
    public function getUserByConfirmHash($hash)
    {
        $dar = $this->getDao()->searchByConfirmHash($hash);
        if ($dar->rowCount() !== 1) {
            return null;
        } else {
            return $this->_getUserInstanceFromRow($dar->getRow());
        }
    }

    public function setCurrentUser(PFUser $user)
    {
        $this->_currentuser = $user;
        $this->_users[$user->getId()] = $user;
        $this->_userid_bynames[$user->getUserName()] = $user->getId();

        return $user;
    }

    /**
     * @param $session_hash string Optional parameter. If given, this will force
     *                             the load of the user with the given session_hash.
     *                             else it will check from the user cookies
     * @return PFUser the user currently logged in (who made the request)
     */
    public function getCurrentUser($session_hash = false)
    {
        if (!isset($this->_currentuser) || $session_hash !== false) {
            if ($session_hash === false) {
                $session_hash = $this->getCookieManager()->getCookie('session_hash');
            }
            try {
                $session_manager    = $this->getSessionManager();
                $now                = $_SERVER['REQUEST_TIME'];
                $session_lifetime   = $this->getSessionLifetime();
                $this->_currentuser = $session_manager->getUser($session_hash, $now, $session_lifetime);
                if ($this->_currentuser->isSuspended() || $this->_currentuser->isDeleted()) {
                    $session_manager->destroyAllSessions($this->_currentuser);
                    $this->_currentuser = null;
                } else {
                    $accessInfo = $this->getUserAccessInfo($this->_currentuser);
                    $now = $_SERVER['REQUEST_TIME'];
                    $break_time = $now - ($accessInfo['last_access_date'] ?? 0);
                    //if the access is not later than 6 hours, it is not necessary to log it
                    if ($break_time > ForgeConfig::get('last_access_resolution')) {
                        $this->_getEventManager()->processEvent(new UserConnectionUpdateEvent($this->_currentuser));
                        $this->getDao()->storeLastAccessDate($this->_currentuser->getId(), $now);
                    }
                }
            } catch (InvalidSessionException $e) {
                $this->_currentuser = null;
            }

            if (! isset($this->_currentuser)) {
                //No valid session_hash/ip found. User is anonymous
                $this->_currentuser = $this->getUserInstanceFromRow(array('user_id' => 0));
            }
            //cache the user
            $this->_users[$this->_currentuser->getId()] = $this->_currentuser;
            $this->_userid_bynames[$this->_currentuser->getUserName()] = $this->_currentuser->getId();
        }
        return $this->_currentuser;
    }

    /**
     * @return Array of User
     */
    public function getUsersWithSshKey()
    {
        return $this->getDao()->searchSSHKeys()->instanciateWith(array($this, 'getUserInstanceFromRow'));
    }

    /**
     * @return PaginatedUserCollection
     */
    public function getPaginatedUsersWithSshKey($offset, $limit)
    {
        $users = array();
        foreach ($this->getDao()->searchPaginatedSSHKeys($offset, $limit) as $user) {
            $users[] = $this->getUserInstanceFromRow($user);
        }

        return new PaginatedUserCollection($users, $this->getDao()->foundRows());
    }

    /**
     * Logout the current user
     * - remove the cookie
     * - clear the session hash
     */
    public function logout()
    {
        $user = $this->getCurrentUser();
        if ($user->getSessionHash()) {
            $this->getSessionManager()->destroyCurrentSession($user);
            $this->getCookieManager()->removeCookie('session_hash');
            $this->destroySession();
        }
    }

    protected function destroySession()
    {
        $session = new Codendi_Session();
        $session->destroy();
    }

    /**
     * Return the user acess information for a given user
     *
     * @param PFUser $user
     *
     * @return array{last_auth_success: string, last_auth_failure: string, nb_auth_failure: string, prev_auth_success: string}
     */
    public function getUserAccessInfo($user)
    {
        return $this->getDao()->getUserAccessInfo($user->getId());
    }

    /**
     * Login the user
     *
     * @deprected
     * @param $name string The login name submitted by the user
     * @param $pwd string The password submitted by the user
     * @param $allowpending boolean True if pending users are allowed (for verify.php). Default is false
     * @return PFUser Registered user or anonymous if the authentication failed
     */
    public function login($name, $pwd, $allowpending = false)
    {
        try {
            $password_expiration_checker = new User_PasswordExpirationChecker();
            $password_handler            = PasswordHandlerFactory::getPasswordHandler();
            $login_manager               = new User_LoginManager(
                EventManager::instance(),
                $this,
                new \Tuleap\User\PasswordVerifier($password_handler),
                $password_expiration_checker,
                $password_handler
            );
            $status_manager              = new User_UserStatusManager();

            $user = $login_manager->authenticate($name, $pwd);
            if ($allowpending) {
                $status_manager->checkStatusOnVerifyPage($user);
            } else {
                $status_manager->checkStatus($user);
            }

            $this->openWebSession($user);
            $password_expiration_checker->checkPasswordLifetime($user);
            $password_expiration_checker->warnUserAboutPasswordExpiration($user);
            $this->warnUserAboutAuthenticationAttempts($user);
            $this->warnUserAboutAdminReadOnlyPermission($user);

            $this->getDao()->storeLoginSuccess($user->getId(), $_SERVER['REQUEST_TIME']);

            \Tuleap\User\LoginInstrumentation::increment('success');
            return $this->setCurrentUser($user);
        } catch (User_InvalidPasswordWithUserException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $this->getDao()->storeLoginFailure($name, $_SERVER['REQUEST_TIME']);
        } catch (User_InvalidPasswordException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (User_PasswordExpiredException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $GLOBALS['Response']->redirect(DisplaySecurityController::URL);
        } catch (User_StatusInvalidException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (SessionNotCreatedException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (User_LoginException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        }

        \Tuleap\User\LoginInstrumentation::increment('failure');
        return $this->setCurrentUser($this->createAnonymousUser());
    }

    /**
     * @return PFUser
     */
    private function createAnonymousUser()
    {
        return $this->_getUserInstanceFromRow(array('user_id' => 0));
    }

    private function openWebSession(PFUser $user)
    {
        $session_manager    = $this->getSessionManager();
        $request            = HTTPRequest::instance();
        $session_identifier = $session_manager->createSession($user, $request, $request->getFromServer('REQUEST_TIME'));

        $this->getCookieManager()->setCookie(
            'session_hash',
            $session_identifier,
            $this->getExpireTimestamp($user)
        );
    }

    private function getExpireTimestamp(PFUser $user)
    {
        // If permanent login configured then cookie expires in one year from now
        $expire = 0;

        if ($user->getStickyLogin()) {
            $expire = $_SERVER['REQUEST_TIME'] + $this->getSessionLifetime();
        }

        return $expire;
    }

    /**
     * @return Rest_TokenManager
     */
    protected function getTokenManager()
    {
        $dao = new Rest_TokenDao();

        return new Rest_TokenManager(
            $dao,
            new Rest_TokenFactory($dao),
            $this
        );
    }

    /**
     * Populate response with details about login attempts.
     *
     * Always display the last succefull log-in. But if there was errors (number of
     * bad attempts > 0) display the number of bad attempts and the last
     * error. Moreover, in case of errors, messages are displayed as warning
     * instead of info.
     *
     */
    private function warnUserAboutAuthenticationAttempts(PFUser $user)
    {
        $access_info = $this->getUserAccessInfo($user);
        $level = 'info';
        if ($access_info['nb_auth_failure'] > 0) {
            $level = 'warning';
            $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_last_failure') . ' ' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $access_info['last_auth_failure']));
            $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_nb_failure') . ' ' . $access_info['nb_auth_failure']);
        }
        // Display nothing if no previous record.
        if ($access_info['last_auth_success'] > 0) {
            $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_prev_success') . ' ' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $access_info['last_auth_success']));
        }
    }

    private function warnUserAboutAdminReadOnlyPermission(PFUser $user): void
    {
        $permission = new RestReadOnlyAdminPermission();

        if ($this->getForgeUserGroupPermissionsManager()->doesUserHavePermission($user, new RestReadOnlyAdminPermission())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('You should not browse the platform with this user, you may experience inconsistent behaviour (%s)'),
                    $permission->getName()
                )
            );
        }
    }

    /**
    * loginAs allows the siteadmin to log as someone else
    *
    * @param string $name
    *
    * @return string a session hash
    */
    public function loginAs($name)
    {
        if (! $this->getCurrentUser()->isSuperUser()) {
            throw new UserNotAuthorizedException();
        }

        $user_login_as = $this->findUser($name);
        if (!$user_login_as) {
            throw new UserNotExistException();
        }
        $status_manager = new User_UserStatusManager();
        try {
            $status_manager->checkStatus($user_login_as);
            $request         = HTTPRequest::instance();
            $session_manager = $this->getSessionManager();
            return $session_manager->createSession($user_login_as, $request, $request->getFromServer('REQUEST_TIME'));
        } catch (User_StatusInvalidException $exception) {
            throw new UserNotActiveException();
        }
    }

    /**
     * Open a session for user
     *
     * @return type
     * @throws UserNotExistException
     * @throws UserNotActiveException
     * @throws SessionNotCreatedException
     */
    public function openSessionForUser(PFUser $user)
    {
        if (!$user) {
            throw new UserNotExistException();
        }
        try {
            $status_manager = new User_UserStatusManager();
            $status_manager->checkStatus($user);
            $this->openWebSession($user);
        } catch (User_StatusInvalidException $exception) {
            throw new UserNotActiveException();
        }
    }

    /**
     * Force the login of the user.
     *
     * Do not delegate auth to plugins (ldap, ...)
     * Do not check the status
     * Do not check password expiration
     * Do not create the session
     *
     * @throws Exception when not in IS_SCRIPT
     *
     * @param $name string The login name submitted by the user
     *
     * @return PFUser Registered user or anonymous if nothing match
     */
    public function forceLogin($name)
    {
        if (!IS_SCRIPT) {
            throw new Exception("Can't log in the user when not is script");
        }

        //If nobody answer success, look for the user into the db
        if ($row = $this->getDao()->searchByUserName($name)->getRow()) {
            $this->_currentuser = $this->getUserInstanceFromRow($row);
        } else {
            $this->_currentuser = $this->getUserInstanceFromRow(array('user_id' => 0));
        }

        //cache the user
        $this->_users[$this->_currentuser->getId()] = $this->_currentuser;
        $this->_userid_bynames[$this->_currentuser->getUserName()] = $this->_currentuser->getId();
        return $this->_currentuser;
    }

    /**
     * isUserLoadedById
     *
     * @param int $user_id
     * @return bool true if the user is already loaded
     */
    public function isUserLoadedById($user_id)
    {
        return isset($this->_users[$user_id]);
    }

    /**
     * isUserLoadedByUserName
     *
     * @param string $user_name
     * @return bool true if the user is already loaded
     */
    public function isUserLoadedByUserName($user_name)
    {
        return isset($this->_userid_bynames[$user_name]);
    }

    /**
     * @return CookieManager
     */
    protected function getCookieManager()
    {
        return new CookieManager();
    }

    /**
     * @return SessionManager
     */
    protected function getSessionManager()
    {
        return new SessionManager(
            $this,
            new SessionDao(),
            new RandomNumberGenerator()
        );
    }

    /**
     * @return EventManager
     */
    protected function _getEventManager()  // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return EventManager::instance();
    }

    private function getSessionLifetime()
    {
        return ForgeConfig::get('sys_session_lifetime');
    }

    protected function _getPasswordLifetime() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $GLOBALS['sys_password_lifetime'];
    }

    /**
     * Update db entry of 'user' table with values in object
     */
    public function updateDb(PFUser $user)
    {
        if (!$user->isAnonymous()) {
            $old_user = $this->getUserByIdWithoutCache($user->getId());
            $userRow = $user->toRow();
            if ($user->getPassword() != '') {
                $password_handler = PasswordHandlerFactory::getPasswordHandler();
                if (!$password_handler->verifyHashPassword($user->getPassword(), $user->getUserPw()) ||
                        $password_handler->isPasswordNeedRehash($user->getUserPw())) {
                    // Update password
                    $userRow['clear_password'] = $user->getPassword();
                }
            }
            if ($user->getLegacyUserPw() !== '') {
                $userRow['user_pw'] = '';
            }
            $result = $this->getDao()->updateByRow($userRow);
            if ($result) {
                if ($user->isSuspended() || $user->isDeleted()) {
                    $session_manager = $this->getSessionManager();
                    $session_manager->destroyAllSessions($user);
                }
                $this->_getEventManager()->processEvent(Event::USER_MANAGER_UPDATE_DB, array('old_user' => $old_user, 'new_user' => &$user));
            }
            return $result;
        }
        return false;
    }

    private function getSSHKeyValidator()
    {
        return new User_SSHKeyValidator($this, $this->_getEventManager());
    }

    public function addSSHKeys(PFUser $user, $new_ssh_keys)
    {
        $user_keys = $user->getAuthorizedKeysArray();
        $all_keys  = array_merge(
            $user_keys,
            preg_split("%(\r\n|\n)%", trim($new_ssh_keys))
        );

        $valid_keys = $this->getSSHKeyValidator()->validateAllKeys($all_keys);

        $this->updateUserSSHKeys($user, $valid_keys);
    }

    public function deleteSSHKeys(PFUser $user, array $ssh_key_index_to_delete)
    {
        $user_keys_to_keep = $user->getAuthorizedKeysArray();

        foreach ($ssh_key_index_to_delete as $ssh_key_index) {
            unset($user_keys_to_keep[$ssh_key_index]);
        }

        $this->updateUserSSHKeys($user, array_values($user_keys_to_keep));
    }

    /**
     * Update ssh keys for a user
     *
     * Should probably be merged with updateDb but I don't know the impact of
     * validating keys each time we update a user
     *
     * @param string[] $keys
     */
    public function updateUserSSHKeys(PFUser $user, array $keys)
    {
        $original_authorised_keys = $user->getAuthorizedKeysRaw();

        $user->setAuthorizedKeys(implode(PFUser::SSH_KEY_SEPARATOR, $keys));

        if ($this->updateDb($user)) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('account_editsshkeys', 'update_filesystem'));

            $event_parameters = array(
                'user_id'       => $user->getId(),
                'original_keys' => $original_authorised_keys,
            );

            $this->_getEventManager()->processEvent(Event::EDIT_SSH_KEYS, $event_parameters);
        }
    }

    /**
     * Assign to given user the next available unix_uid
     *
     * We need to pass the whole user object and to modify it in this
     * method to avoid conflicts if updateDb is used after this call. As
     * updateDb will perform a select on user table to check what changed
     * between the user table and the user object, the user object must contains
     * what was updated by this method.
     *
     * @param PFUser $user A user object to update
     *
     * @return bool
     */
    public function assignNextUnixUid($user)
    {
        $newUid = $this->getDao()->assignNextUnixUid($user->getId());
        if ($newUid !== false) {
            $user->setUnixUid($newUid);
            return true;
        }
        return false;
    }

    /**
     * Create new account
     *
     * @param PFUser $user
     *
     * @return PFUser
     */
    public function createAccount($user)
    {
        $dao = $this->getDao();
        $user_id = $dao->create(
            $user->getUserName(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getRealName(),
            $user->getRegisterPurpose(),
            $user->getStatus(),
            $user->getShell(),
            $user->getUnixStatus(),
            $user->getUnixUid(),
            $user->getUnixBox(),
            $user->getLdapId(),
            $_SERVER['REQUEST_TIME'],
            $user->getConfirmHash(),
            $user->getMailSiteUpdates(),
            $user->getMailVA(),
            $user->getStickyLogin(),
            $user->getAuthorizedKeysRaw(),
            $user->getNewMail(),
            $user->getTimezone(),
            $user->getLanguageID(),
            $user->getExpiryDate(),
            $_SERVER['REQUEST_TIME']
        );
        if (!$user_id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_exit', 'error'));
            return 0;
        } else {
            $user->setId($user_id);
            $this->assignNextUnixUid($user);

            $em = $this->_getEventManager();

            $em->processEvent(Event::USER_MANAGER_CREATE_ACCOUNT, array('user' => $user));

            $this->getDefaultWidgetCreator()->createDefaultDashboard($user);

            switch ($user->getStatus()) {
                case PFUser::STATUS_PENDING:
                    if (ForgeConfig::get('sys_user_approval')) {
                        $this->pending_user_notifier->notifyAdministrator($user);
                    }
                    break;
                case PFUser::STATUS_ACTIVE:
                case PFUser::STATUS_RESTRICTED:
                    $em->processEvent('project_admin_activate_user', array('user_id' => $user_id));
                    break;
            }

            return $user;
        }
    }

    /**
     * For testing purpose
     * @return AtUserCreationDefaultWidgetsCreator
     */
    protected function getDefaultWidgetCreator()
    {
        $factory = new WidgetFactory(
            $this,
            $this->getForgeUserGroupPermissionsManager(),
            EventManager::instance()
        );

        return new AtUserCreationDefaultWidgetsCreator(
            new DashboardWidgetDao(
                $factory
            ),
            EventManager::instance()
        );
    }

    /**
     * protected for testing purpose
     * @return User_ForgeUserGroupPermissionsManager
     */
    protected function getForgeUserGroupPermissionsManager()
    {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * Update user name in different tables containing the old user name
     * @param PFUser $user
     * @param String $newName
     * @return bool
     */
    public function renameUser($user, $newName)
    {
        $dao = $this->getDao();
        if ($dao->renameUser($user, $newName)) {
            $wiki = new WikiDao(CodendiDataAccess::instance());
            if ($wiki->updatePageName($user, $newName)) {
                $user->setUserName($newName);
                return ($this->updateDb($user));
            }
        }
        return false;
    }

    public function removeConfirmHash($confirm_hash)
    {
        $dao = $this->getDao();
        $dao->removeConfirmHash($confirm_hash);
    }
}

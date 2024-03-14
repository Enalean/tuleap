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

use Tuleap\Config\ConfigKey;
use Tuleap\CookieManager;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Dashboard\User\AtUserCreationDefaultWidgetsCreator;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\HelpDropdown\ReleaseNoteManager;
use Tuleap\User\Account\AccountCreated;
use Tuleap\User\Account\DisplaySecurityController;
use Tuleap\User\FindUserByEmailEvent;
use Tuleap\User\ForceLogin;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use Tuleap\User\ICreateAccount;
use Tuleap\User\InvalidSessionException;
use Tuleap\User\LogUser;
use Tuleap\User\ProvideAnonymousUser;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\User\ProvideCurrentUserWithLoggedInInformation;
use Tuleap\User\ProvideUserFromRow;
use Tuleap\User\RetrievePasswordlessOnlyState;
use Tuleap\User\RetrieveUserByEmail;
use Tuleap\User\RetrieveUserById;
use Tuleap\User\RetrieveUserByUserName;
use Tuleap\User\SessionManager;
use Tuleap\User\SessionNotCreatedException;
use Tuleap\User\SwitchPasswordlessOnlyState;
use Tuleap\User\UserConnectionUpdateEvent;
use Tuleap\User\UserRetrieverByLoginNameEvent;
use Tuleap\Widget\WidgetFactory;

class UserManager implements ProvideCurrentUser, ProvideCurrentUserWithLoggedInInformation, ProvideAnonymousUser, RetrieveUserById, RetrieveUserByEmail, RetrieveUserByUserName, ProvideUserFromRow, ICreateAccount, LogUser, SwitchPasswordlessOnlyState, RetrievePasswordlessOnlyState, ForceLogin // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * User with id lower than 100 are considered specials (siteadmin, null,
     * etc).
     */
    public const SPECIAL_USERS_LIMIT = 100;

    #[ConfigKey("Should user be approved by site admin (1) or auto approved (0)")]
    public const CONFIG_USER_APPROVAL = 'sys_user_approval';

    /**
     * @psalm-var array<int|string,PFUser|null>
     */
    public array $_users           = []; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public array $_userid_bynames  = []; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public array $_userid_byldapid = []; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    private UserDao|null $_userdao                                             = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    private \Tuleap\User\CurrentUserWithLoggedInInformation|null $current_user = null;

    /**
     * @var User_PendingUserNotifier
     */
    private $pending_user_notifier;

    public function __construct(User_PendingUserNotifier $pending_user_notifier)
    {
        $this->pending_user_notifier = $pending_user_notifier;
    }

    protected static ?self $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    /**
     * @return UserManager
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $userManager     = self::class;
            self::$_instance = new $userManager(
                new User_PendingUserNotifier(),
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

    public function getUserAnonymous(): PFUser
    {
        $anonymous_user = $this->getUserById(0);
        assert($anonymous_user !== null);
        return $anonymous_user;
    }

    /**
     * @param int the user_id of the user to find
     * @return PFUser|null if the user is not found
     */
    public function getUserById($user_id)
    {
        if (! isset($this->_users[$user_id])) {
            if (is_numeric($user_id)) {
                if ($user_id == 0) {
                    $this->_users[$user_id] = $this->getUserInstanceFromRow(['user_id' => 0]);
                } else {
                    $u = $this->getUserByIdWithoutCache($user_id);
                    if ($u) {
                        $this->_users[$u->getId()]                = $u;
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
        $row = $this->getDao()->searchByUserId($id);
        if ($row !== null) {
            return $this->getUserInstanceFromRow($row);
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
     * @param string $user_name the user_name of the user to find
     */
    public function getUserByUserName(string $user_name): PFUser|null
    {
        if (! isset($this->_userid_bynames[$user_name])) {
            $row = $this->getDao()->searchByUserName($user_name);
            if ($row !== null) {
                $u                                 = $this->getUserInstanceFromRow($row);
                $this->_users[$u->getId()]         = $u;
                $this->_userid_bynames[$user_name] = $u->getId();
            } else {
                $this->_userid_bynames[$user_name] = null;
            }
        }
        $user    = null;
        $user_id = $this->_userid_bynames[$user_name];
        if ($user_id !== null) {
            $user = $this->_users[(int) $user_id];
        }
        return $user;
    }

    public function _getUserInstanceFromRow($row) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getUserInstanceFromRow($row);
    }

    public function getUserInstanceFromRow($row): PFUser
    {
        if (isset($row['user_id']) && $row['user_id'] < self::SPECIAL_USERS_LIMIT) {
            $user = null;
            EventManager::instance()->processEvent(Event::USER_MANAGER_GET_USER_INSTANCE, ['row' => $row, 'user' => &$user]);
            if ($user) {
                return $user;
            }
        }
        return new PFUser($row);
    }

    /**
     * @param  string Ldap identifier
     * @return PFUser|null null if the user is not found
     */
    public function getUserByLdapId($ldapId)
    {
        if ($ldapId == null) {
            return null;
        }
        if (! isset($this->_userid_byldapid[$ldapId])) {
            $rows = $this->getDao()->searchByLdapId($ldapId);
            $row  = array_shift($rows);
            if ($row !== null) {
                $u                               = $this->getUserInstanceFromRow($row);
                $this->_users[$u->getId()]       = $u;
                $this->_userid_byldapid[$ldapId] = $u->getId();
            } else {
                $this->_userid_byldapid[$ldapId] = null;
            }
        }
        $user    = null;
        $user_id = $this->_userid_byldapid[$ldapId];
        if ($user_id !== null) {
            $user = $this->_users[$user_id];
        }
        return $user;
    }

    /**
     * Try to find a user that match the given identifier
     *
     * @param String $ident A user identifier
     *
     * @return PFUser|null
     */
    public function findUser($ident)
    {
        $user = null;
        if (! $ident) {
            return $user;
        }
        $eParams = ['ident' => $ident,
            'user'  => &$user,
        ];
        $this->_getEventManager()->processEvent('user_manager_find_user', $eParams);

        if (! $user && preg_match("/^\d+$/", $ident)) {
            $user = $this->getUserById((int) $ident);
        }

        if (! $user) {
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
        $users     = [];
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
        $users = [];
        foreach ($this->getDao()->searchGlobalPaginated($words, $exact, $offset, $limit) as $user) {
            $users[] = $this->getUserInstanceFromRow($user);
        }
        return new PaginatedUserCollection($users, $this->getDao()->foundRows());
    }

    public function getUserByEmail(string $email): ?PFUser
    {
        return $this->getUserCollectionByEmails([$email])->getUserByEmail($email);
    }

    /**
     * @return PFUser[]
     */
    public function getAllUsersByEmail($email): array
    {
        $users = [];
        foreach ($this->getDao()->searchByEmail($email) as $user) {
            $users[] = $this->getUserInstanceFromRow($user);
        }
        return $users;
    }

    /**
     * @return PFUser[]
     */
    public function getAndEventuallyCreateUserByEmail(string $email): array
    {
        $users = [];
        foreach ($this->getDao()->searchByEmail($email) as $user) {
            $users[] = $this->getUserInstanceFromRow($user);
        }
        if (count($users) > 0) {
            return $users;
        }

        return EventManager::instance()->dispatch(new FindUserByEmailEvent($email))->getUsers();
    }

    /**
     * @return PFUser[]
     */
    public function getAllUsersByLdapID(string $ldap_id): array
    {
        $users = [];
        foreach ($this->getDao()->searchByLdapId($ldap_id) as $user) {
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
     * @return PFUser|null
     */
    public function getUserByIdentifier($identifier)
    {
        $user = null;

        $em                  = $this->_getEventManager();
        $tokenFoundInPlugins = false;
        $params              = ['identifier' => $identifier,
            'user'       => &$user,
            'tokenFound' => &$tokenFoundInPlugins,
        ];
        $em->processEvent('user_manager_get_user_by_identifier', $params);

        if (! $tokenFoundInPlugins) {
            // Guess identifier type
            $separatorPosition = strpos($identifier, ':');
            if ($separatorPosition === false) {
                // identifier = username
                $user = $this->getUserByUserName($identifier);
            } else {
                // identifier = type:value
                $identifierType  = substr($identifier, 0, $separatorPosition);
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
        $row = $this->getDao()->searchByConfirmHash($hash);
        if ($row === null) {
            return null;
        }
        return $this->_getUserInstanceFromRow($row);
    }

    public function setCurrentUser(\Tuleap\User\CurrentUserWithLoggedInInformation $current_user): void
    {
        $this->current_user                          = $current_user;
        $user                                        = $current_user->user;
        $this->_users[$user->getId()]                = $user;
        $this->_userid_bynames[$user->getUserName()] = $user->getId();
    }

    /**
     * @param $session_hash string Optional parameter. If given, this will force
     *                             the load of the user with the given session_hash.
     *                             else it will check from the user cookies
     * @return PFUser the user currently logged in (who made the request)
     */
    public function getCurrentUser($session_hash = false): PFUser
    {
        return $this->getCurrentUserWithLoggedInInformation($session_hash)->user;
    }

    public function getCurrentUserWithLoggedInInformation(string|false $session_hash = false): \Tuleap\User\CurrentUserWithLoggedInInformation
    {
        if ($this->current_user === null || $session_hash !== false) {
            if ($session_hash === false) {
                $session_hash = $this->getCookieManager()->getCookie('session_hash');
            }
            try {
                $session_manager    = $this->getSessionManager();
                $now                = $_SERVER['REQUEST_TIME'] ?? ((new DateTimeImmutable())->getTimestamp());
                $user_agent         = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $session_lifetime   = $this->getSessionLifetime();
                $user_from_session  = $session_manager->getUser($session_hash, $now, $session_lifetime, $user_agent);
                $this->current_user = \Tuleap\User\CurrentUserWithLoggedInInformation::fromLoggedInUser($user_from_session);
                if ($this->current_user->user->isSuspended() || $this->current_user->user->isDeleted()) {
                    $session_manager->destroyAllSessions($this->current_user->user);
                    $this->current_user = null;
                } else {
                    $accessInfo = $this->getUserAccessInfo($this->current_user->user);
                    $break_time = $now - ($accessInfo['last_access_date'] ?? 0);
                    //if the access is not later than 6 hours, it is not necessary to log it
                    if ($break_time > ForgeConfig::get('last_access_resolution')) {
                        $this->_getEventManager()->processEvent(new UserConnectionUpdateEvent($this->current_user->user));
                        $this->getDao()->storeLastAccessDate($this->current_user->user->getId(), $now);
                    }
                }
            } catch (InvalidSessionException $e) {
                $this->current_user = null;
            }

            if ($this->current_user === null) {
                //No valid session_hash/ip found. User is anonymous
                $this->current_user = \Tuleap\User\CurrentUserWithLoggedInInformation::fromAnonymous($this);
            } elseif ($this->current_user->user->isFirstTimer()) {
                $this->getDao()->userWillNotBeAnymoreAFirstTimer((int) $this->current_user->user->getId());
            }
            //cache the user
            $this->_users[$this->current_user->user->getId()]                = $this->current_user->user;
            $this->_userid_bynames[$this->current_user->user->getUserName()] = $this->current_user->user->getId();
        }
        return $this->current_user;
    }

    /**
     * @return PFUser[]
     */
    public function getUsersWithSshKey(): array
    {
        $users = [];
        foreach ($this->getDao()->searchSSHKeys() as $user_row) {
            $users[] = $this->getUserInstanceFromRow($user_row);
        }
        return $users;
    }

    /**
     * @return PaginatedUserCollection
     */
    public function getPaginatedUsersWithSshKey($offset, $limit)
    {
        $users = [];
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
     * @return PFUser Registered user or anonymous if the authentication failed
     */
    public function login(string $name, ConcealedString $pwd): PFUser
    {
        try {
            $password_expiration_checker = new User_PasswordExpirationChecker();
            $password_handler            = PasswordHandlerFactory::getPasswordHandler();
            $login_manager               = new User_LoginManager(
                EventManager::instance(),
                $this,
                $this,
                new \Tuleap\User\PasswordVerifier($password_handler),
                $password_expiration_checker,
                $password_handler
            );
            $status_manager              = new User_UserStatusManager();

            $user = $login_manager->authenticate($name, $pwd);
            $status_manager->checkStatus($user);

            $this->openWebSession($user);
            $password_expiration_checker->checkPasswordLifetime($user);
            $password_expiration_checker->warnUserAboutPasswordExpiration($user);
            $this->warnUserAboutAuthenticationAttempts($user);
            $this->warnUserAboutAdminReadOnlyPermission($user);

            $user->setIsFirstTimer(
                $this->getDao()->storeLoginSuccess(
                    $user->getId(),
                    \Tuleap\Request\RequestTime::getTimestamp()
                )
            );

            \Tuleap\User\LoginInstrumentation::increment('success');
            $this->setCurrentUser(\Tuleap\User\CurrentUserWithLoggedInInformation::fromLoggedInUser($user));
            return $user;
        } catch (User_InvalidPasswordWithUserException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $this->getDao()->storeLoginFailure($name, \Tuleap\Request\RequestTime::getTimestamp());
        } catch (User_InvalidPasswordException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (User_PasswordExpiredException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $GLOBALS['Response']->redirect(DisplaySecurityController::URL);
        } catch (User_StatusSuspendedException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('Your account has been suspended. If you have questions regarding your suspension, please email <a href="mailto:%s">the site administrators</a>.'),
                    ForgeConfig::get('sys_email_admin')
                ),
                CODENDI_PURIFIER_LIGHT
            );
        } catch (User_StatusInvalidException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (SessionNotCreatedException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (User_LoginException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        }

        \Tuleap\User\LoginInstrumentation::increment('failure');
        $current_user = \Tuleap\User\CurrentUserWithLoggedInInformation::fromAnonymous($this);
        $this->setCurrentUser($current_user);
        return $current_user->user;
    }

    private function openWebSession(PFUser $user): void
    {
        $session_manager    = $this->getSessionManager();
        $request            = HTTPRequest::instance();
        $session_identifier = $session_manager->createSession($user, $request, $request->getFromServer('REQUEST_TIME'));

        $this->getCookieManager()->setCookie(
            'session_hash',
            $session_identifier,
            $this->getExpireTimestamp($user)
        );
        PHP_Session::regenerateID();

        $this->markReleaseNoteAsSeenTheFirstTimeTheWebSessionIsOpened($user);
    }

    private function getExpireTimestamp(PFUser $user)
    {
        // If permanent login configured then cookie expires in one year from now
        $expire = 0;

        if ($user->getStickyLogin()) {
            $expire = (\Tuleap\Request\RequestTime::getTimestamp()) + $this->getSessionLifetime();
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
        $level       = 'info';
        if ($access_info['nb_auth_failure'] > 0) {
            $level = 'warning';
            $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_last_failure') . ' ' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $access_info['last_auth_failure']));
            $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_nb_failure') . ' ' . $access_info['nb_auth_failure']);
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

    private function markReleaseNoteAsSeenTheFirstTimeTheWebSessionIsOpened(PFUser $user): void
    {
        $access_info = $this->getUserAccessInfo($user);
        if ($access_info['last_auth_success'] === '0') {
            $user->setPreference(ReleaseNoteManager::USER_PREFERENCE_NAME_RELEASE_NOTE_SEEN, '1');
        }
    }

    /**
     * Open a session for user
     *
     * @throws UserNotExistException
     * @throws UserNotActiveException
     * @throws SessionNotCreatedException
     */
    public function openSessionForUser(PFUser $user): void
    {
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
    public function forceLogin(string $name): PFUser
    {
        if (! IS_SCRIPT) {
            throw new Exception("Can't log in the user when not is script");
        }

        //If nobody answer success, look for the user into the db
        if ($row = $this->getDao()->searchByUserName($name)) {
            $this->setCurrentUser(\Tuleap\User\CurrentUserWithLoggedInInformation::fromLoggedInUser($this->getUserInstanceFromRow($row)));
        } else {
            $this->setCurrentUser(\Tuleap\User\CurrentUserWithLoggedInInformation::fromAnonymous($this));
        }

        return $this->getCurrentUser();
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
        return ForgeConfig::get('sys_password_lifetime');
    }

    /**
     * Update db entry of 'user' table with values in object
     */
    public function updateDb(PFUser $user)
    {
        if (! $user->isAnonymous()) {
            $old_user      = $this->getUserByIdWithoutCache($user->getId());
            $userRow       = $user->toRow();
            $user_password = $user->getPassword();
            if ($user_password !== null) {
                $user_password_hash = $user->getUserPw();
                $password_handler   = PasswordHandlerFactory::getPasswordHandler();
                if (
                    $user_password_hash === null ||
                    ! $password_handler->verifyHashPassword($user_password, $user_password_hash) ||
                    $password_handler->isPasswordNeedRehash($user_password_hash)
                ) {
                    // Update password
                    $userRow['clear_password'] = $user->getPassword();
                }
            }
            $result = $this->getDao()->updateByRow($userRow);
            if ($result) {
                if ($user->isSuspended() || $user->isDeleted()) {
                    $session_manager = $this->getSessionManager();
                    $session_manager->destroyAllSessions($user);
                }
                $this->_getEventManager()->processEvent(Event::USER_MANAGER_UPDATE_DB, ['old_user' => $old_user, 'new_user' => &$user]);
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
            $GLOBALS['Response']->addFeedback('info', _('SSH key(s) updated in database, will be propagated on filesystem in a few minutes, please be patient.'));

            $event_parameters = [
                'user_id'       => $user->getId(),
                'original_keys' => $original_authorised_keys,
            ];

            $this->_getEventManager()->processEvent(Event::EDIT_SSH_KEYS, $event_parameters);
        }
    }

    /**
     * Create new account
     */
    public function createAccount(PFUser $user): ?PFUser
    {
        $dao          = $this->getDao();
        $request_time = \Tuleap\Request\RequestTime::getTimestamp();
        $user_id      = $dao->create(
            $user->getUserName(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getRealName(),
            $user->getRegisterPurpose(),
            $user->getStatus(),
            $user->getLdapId(),
            $request_time,
            $user->getConfirmHash(),
            $user->getMailSiteUpdates(),
            $user->getMailVA(),
            $user->getStickyLogin(),
            $user->getAuthorizedKeysRaw(),
            $user->getNewMail(),
            $user->getTimezone(),
            $user->getLanguageID(),
            $user->getExpiryDate(),
            $request_time
        );
        if (! $user_id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_exit', 'error'));
            return null;
        } else {
            $user->setId($user_id);

            $em = $this->_getEventManager();

            $em->dispatch(new AccountCreated($user));

            $this->getDefaultWidgetCreator()->createDefaultDashboard($user);

            switch ($user->getStatus()) {
                case PFUser::STATUS_PENDING:
                    if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1) {
                        $this->pending_user_notifier->notifyAdministrator($user);
                    }
                    break;
                case PFUser::STATUS_ACTIVE:
                case PFUser::STATUS_RESTRICTED:
                    $em->processEvent('project_admin_activate_user', ['user_id' => $user_id]);
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

    public function switchPasswordlessOnly(PFUser $user, bool $passwordless_only): void
    {
        $this->getDao()->switchPasswordlessOnlyAuth((int) $user->getId(), $passwordless_only);
    }

    public function isPasswordlessOnly(PFUser $user): bool
    {
        return $this->getDao()->isPasswordlessOnlyAuth((int) $user->getId());
    }
}

<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use FastRoute\RouteCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\LDAP\Exception\IdentifierTypeNotFoundException;
use Tuleap\LDAP\Exception\IdentifierTypeNotRecognizedException;
use Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager;
use Tuleap\LDAP\LdapLogger;
use Tuleap\LDAP\LinkModalContentPresenter;
use Tuleap\LDAP\NonUniqueUidRetriever;
use Tuleap\LDAP\Project\UGroup\Binding\AdditionalModalPresenterBuilder;
use Tuleap\LDAP\ProjectGroupManagerRestrictedUserFilter;
use Tuleap\Project\Admin\ProjectMembers\MembersEditProcessAction;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersAdditionalModalCollectionPresenter;
use Tuleap\Project\Admin\ProjectUGroup\BindingAdditionalModalPresenterCollection;
use Tuleap\Project\Admin\ProjectUGroup\UGroupEditProcessAction;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\svn\Event\GetSVNLoginNameEvent;
use Tuleap\SystemEvent\RootDailyStartEvent;
use Tuleap\User\Account\AccountInformationCollection;
use Tuleap\User\Account\AccountInformationPresenter;
use Tuleap\User\Account\PasswordPreUpdateEvent;
use Tuleap\User\Admin\UserDetailsPresenter;
use Tuleap\User\UserRetrieverByLoginNameEvent;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class LdapPlugin extends Plugin
{
    /**
     * @type LDAP
     */
    private $ldapInstance;

    /**
     * @var LDAP
     */
    private $ldap_write_instance;

    /**
     * @type LDAP_UserManager
     */
    private $_ldapUmInstance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    public function __construct($id)
    {
        parent::__construct($id);

        bindTextDomain('tuleap-ldap', LDAP_SITE_CONTENT_DIR);
    }

    public function getHooksAndCallbacks()
    {
        // Layout
        $this->addHook('display_newaccount', 'forbidIfLdapAuth', false);
        $this->addHook('before_register', 'before_register', false);

        // Search
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::SEARCH_TYPE);

        // Authentication
        $this->addHook(Event::SESSION_BEFORE_LOGIN, 'authenticate', false);
        $this->addHook(Event::SESSION_AFTER_LOGIN, 'allowCodendiLogin', false);

        // Login
        $this->addHook('login_presenter');
        $this->addHook('display_lostpw_createaccount', 'forbidIfLdapAuth', false);
        $this->addHook('account_redirect_after_login', 'account_redirect_after_login', false);

        // User finder
        $this->addHook('user_manager_find_user', 'user_manager_find_user', false);
        $this->addHook('user_manager_get_user_by_identifier', 'user_manager_get_user_by_identifier', false);
        $this->addHook(UserRetrieverByLoginNameEvent::NAME);

        // User Home
        $this->addHook('user_home_pi_entry', 'personalInformationEntry', false);

        // User account
        $this->addHook('before_lostpw-confirm', 'cancelChange', false);
        $this->addHook('before_lostpw', 'cancelChange', false);
        $this->addHook(PasswordPreUpdateEvent::NAME);
        $this->addHook(AccountInformationCollection::NAME);

        // User group
        $this->addHook('project_admin_ugroup_deletion');

        // Site Admin
        $this->addHook('before_admin_change_pw', 'warnNoPwChange', false);
        $this->addHook('usergroup_update_form', 'addLdapInput', false);
        $this->addHook('usergroup_update', 'updateLdapID', false);

        // Project admin
        $this->addHook(ProjectMembersAdditionalModalCollectionPresenter::NAME);
        $this->addHook(Event::UGROUP_UPDATE_USERS_ALLOWED, 'ugroup_update_users_allowed', false);

        // Svn intro
        $this->addHook(Event::SVN_INTRO);
        $this->addHook('svn_check_access_username', 'svn_check_access_username', false);

        // Search as you type user
        $this->addHook('ajax_search_user', 'ajax_search_user', false);

        // Project creation
        $this->addHook(Event::REGISTER_PROJECT_CREATION);

        // Backend SVN
        $this->addHook('backend_factory_get_svn', 'backend_factory_get_svn', false);
        $this->addHook(Event::SVN_APACHE_AUTH, 'svn_apache_auth', false);
        $this->addHook(GetSVNLoginNameEvent::NAME);

        // Daily codendi job
        $this->addHook('codendi_daily_start', 'codendi_daily_start', false);
        $this->addHook(RootDailyStartEvent::NAME);

        // SystemEvent
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'get_system_event_class', false);

        // Ask for LDAP Username of a User
        $this->addHook(Event::GET_LDAP_LOGIN_NAME_FOR_USER);

        // User profile creation/update
        $this->addHook(Event::USER_MANAGER_UPDATE_DB);
        $this->addHook(Event::USER_MANAGER_CREATE_ACCOUNT);

        if (defined('GIT_EVENT_PLATFORM_CAN_USE_GERRIT')) {
            $this->addHook(GIT_EVENT_PLATFORM_CAN_USE_GERRIT);
        }

        $this->addHook(UserDetailsPresenter::ADDITIONAL_DETAILS);
        $this->addHook('ugroup_duplication');
        $this->addHook(BindingAdditionalModalPresenterCollection::NAME);
        $this->addHook(UGroupEditProcessAction::NAME);
        $this->addHook(MembersEditProcessAction::NAME);

        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);

        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return LdapPluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof LdapPluginInfo) {
            $this->pluginInfo = new LdapPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @return LDAP
     */
    public function getLdap()
    {
        if (!isset($this->ldapInstance)) {
            $this->ldapInstance = $this->instanciateLDAP();
        }
        return $this->ldapInstance;
    }

    private function instanciateLDAP()
    {
        return new LDAP(
            $this->getLDAPParams(),
            $this->getLogger()
        );
    }

    public function getLogger(): \Psr\Log\LoggerInterface
    {
        return new LdapLogger();
    }

    /**
     * @return LDAP
     */
    public function getLDAPWrite()
    {
        if (! isset($this->ldap_write_instance)) {
            $ldap_params = $this->getLDAPParams();
            if (isset($ldap_params['server_type']) && $ldap_params['server_type'] == LDAP::SERVER_TYPE_ACTIVE_DIRECTORY) {
                throw new LDAP_Exception_NoWriteException();
            } elseif (isset($ldap_params['write_server']) && trim($ldap_params['write_server']) != '') {
                $this->ldap_write_instance = $this->instanciateLDAP();
            } else {
                throw new LDAP_Exception_NoWriteException();
            }
        }
        return $this->ldap_write_instance;
    }

    private function hasLDAPWrite()
    {
        try {
            $this->getLDAPWrite();
            return true;
        } catch (LDAP_Exception_NoWriteException $ex) {
        }
        return false;
    }

    private function getLDAPParams()
    {
        $ldap_params = array();
        $keys = $this->getPluginInfo()->propertyDescriptors->getKeys()->iterator();
        foreach ($keys as $k) {
            $nk = str_replace('sys_ldap_', '', $k);
            $ldap_params[$nk] = $this->getPluginInfo()->getPropertyValueForName($k);
        }
        return $ldap_params;
    }

    /**
     * Wrapper
     *
     * @return LDAP_UserManager
     */
    public function getLdapUserManager()
    {
        if (!isset($this->_ldapUmInstance)) {
            $this->_ldapUmInstance = new LDAP_UserManager($this->getLdap(), LDAP_UserSync::instance());
        }
        return $this->_ldapUmInstance;
    }

    /**
     * Hook
     *
     * IN  $params['type_of_search']
     * OUT $params['output']
     *
     * @param Array $params
     *
     * @return void
     */
    public function layout_search_entry($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['search_entries'][] = array(
            'value'    => 'people_ldap',
            'label'    => $GLOBALS['Language']->getText('plugin_ldap', 'people_ldap'),
            'selected' => $params['type_of_search'] == 'people_ldap',
        );
    }

    /**
     * Hook
     *
     * IN  $params['codendiUserOnly']
     * IN  $params['limit']
     * IN  $params['searchToken']
     * IN  $params['validEmail']
     * OUT $params['userList']
     * OUT $params['pluginAnswered']
     *
     * @param Array $params
     *
     * @return void
     */
    public function ajax_search_user($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLDAPUserManagementEnabled() && !$params['codendiUserOnly']) {
            $params['pluginAnswered'] = true;

            $validEmail = isset($params['validEmail']) ? $params['validEmail'] : false;

            $ldap         = $this->getLdap();
            $lri          = $ldap->searchUserAsYouType($params['searchToken'], $params['limit'], $validEmail);
            $user_manager = UserManager::instance();
            $sync         = LDAP_UserSync::instance();
            foreach ($lri as $lr) {
                if ($lr->exist() && $lr->valid()) {
                    $tuleap_user_id = null;
                    $tuleap_user    = $user_manager->getUserByLdapId($lr->getEdUid());
                    if ($tuleap_user !== null) {
                        $params['userList'][] = array(
                            'display_name' => $tuleap_user->getRealName() . ' (' . $tuleap_user->getUserName() . ')',
                            'login'        => $tuleap_user->getUserName(),
                            'user_id'      => $tuleap_user->getId(),
                            'has_avatar'   => $tuleap_user->hasAvatar(),
                            'avatar_url'   => $tuleap_user->getAvatarUrl()
                        );
                    } else {
                        $params['userList'][] = array(
                            'display_name' => $sync->getCommonName($lr) . ' (' . $lr->getLogin() . ')',
                            'login'        => $lr->getLogin(),
                            'user_id'      => $tuleap_user_id,
                            'has_avatar'   => false,
                            'avatar_url'   => ''
                        );
                    }
                }
            }
            if ($ldap->getErrno() == LDAP::ERR_SIZELIMIT) {
                $params['has_more'] = true;
                if (! $params['json_format']) {
                    $params['userList'][] = "<strong>...</strong>";
                }
            }
        }
    }

    /**
     * @see Event::SEARCH_TYPE
     */
    public function search_type($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $query  = $params['query'];
        $result = $params['results'];

        if ($this->isLdapAuthType() && $query->getTypeOfSearch() == Search_SearchPeople::NAME) {
            $search = new LDAP_SearchPeople(UserManager::instance(), $this->getLdap());
            $presenter = $search->search($query, $result);
            $result->setResultsHtml($this->getSearchTemplateRenderer()->renderToString($presenter->getTemplate(), $presenter));
        }
    }

    public function getSearchTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(
            array(
                dirname(__FILE__) . '/../templates',
                ForgeConfig::get('codendi_dir') . '/src/templates/search',
            )
        );
    }

    /**
     * Hook
     *
     * @params $params $params['login']
     *                 $params['password']
     *                 $params['auth_success']
     *                 $params['auth_user_id']
     *                 $params['auth_user_status']
     */
    public function authenticate($params)
    {
        if ($this->isLdapAuthType()) {
            try {
                $params['auth_success'] = false;

                $user = $this->getLdapUserManager()->authenticate($params['loginname'], $params['passwd']);
                if ($user) {
                    $params['auth_user_id']     = $user->getId();
                    $params['auth_user_status'] = $user->getStatus();
                    $params['auth_success']     = true;
                }
            } catch (LDAP_UserNotFoundException $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            } catch (LDAP_AuthenticationFailedException $exception) {
                $logger = $this->getLogger();
                $logger->info("[LDAP] User " . $params['loginname'] . " failed to authenticate");
            }
        }
    }

    /** Hook
     * When redirection after login happens, check if user as already filled
     * his personal info or not. If it's not the case, it means that the
     * account was automatically created and user must complete his
     * registeration.
     */
    public function account_redirect_after_login($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $ldapUserDao = new LDAP_UserDao(CodendiDataAccess::instance());
            if (!$ldapUserDao->alreadyLoggedInOnce(UserManager::instance()->getCurrentUser()->getId())) {
                $return_to_arg = "";
                if ($params['return_to']) {
                    $return_to_arg = '?return_to=' . urlencode($params['return_to']);
                    if (isset($pv) && $pv == 2) {
                        $return_to_arg .= '&pv=' . $pv;
                    }
                } else {
                    if (isset($pv) && $pv == 2) {
                        $return_to_arg .= '?pv=' . $pv;
                    }
                }
                $params['return_to'] = '/plugins/ldap/welcome' . $return_to_arg;
            }
        }
    }

    /**
     * @params $params $params['user'] IN
     *                 $params['allow_codendi_login'] IN/OUT
     */
    public function allowCodendiLogin($params)
    {
        if ($this->isLdapAuthType()) {
            if ($params['user']->getLdapId() != null) {
                $params['allow_codendi_login'] = false;
                return;
            }

            $ldapUm = $this->getLdapUserManager();
            $lr = $ldapUm->getLdapFromUserId($params['user']->getId());
            if ($lr) {
                $params['allow_codendi_login'] = false;
                $GLOBALS['feedback'] .= ' ' . $GLOBALS['Language']->getText(
                    'plugin_ldap',
                    'login_pls_use_ldap',
                    array($GLOBALS['sys_name'])
                );
            } else {
                $params['allow_codendi_login'] = true;
            }
        }

        if ($this->hasLDAPWrite() && $params['user']->getLdapId() == null) {
            try {
                $this->getLDAPUserWrite()->updateWithUser($params['user']);
            } catch (Exception $exception) {
                $this->getLogger()->error('An error occured while registering user (session_after_login): ' . $exception->getMessage());
            }
        }
    }

    /**
     * Hook
     * Params:
     *  IN  $params['ident']
     *  IN/OUT  $params['user'] User object if found or null.
     */
    public function user_manager_find_user($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLDAPUserManagementEnabled()) {
            $ldap = $this->getLdap();
            // First, test if its provided by autocompleter: "Common Name (login name)"
            $matches = array();
            if (preg_match('/^(.*) \((.*)\)$/', $params['ident'], $matches)) {
                if (trim($matches[2]) != '') {
                    $lri  = $ldap->searchLogin($matches[2]);
                } else {
                    $lri  = $ldap->searchCommonName($matches[1]);
                }
            } else {
                // Otherwise, search as defined in config most of the time
                // (uid, email, common name)
                $lri  = $ldap->searchUser($params['ident']);
            }
            if ($lri !== false) {
                $params['user'] = $this->getLdapUserManager()->getUserFromLdapIterator($lri);
            } else {
                $params['user'] = null;
            }
        }
    }

    public function user_manager_get_user_by_identifier($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType() && $this->isLDAPUserManagementEnabled()) {
            try {
                $tuleap_user = $this->getLdapUserManager()->getUserByIdentifier($params['identifier']);

                $params['tokenFound'] = true;
                $params['user']       = $tuleap_user;
            } catch (IdentifierTypeNotFoundException $e) {
                // Do nothing
            } catch (IdentifierTypeNotRecognizedException $e) {
                // Do nothing
            }
        }
    }

    public function getUserByLoginName(UserRetrieverByLoginNameEvent $event)
    {
        if ($this->isLdapAuthType() && $this->isLDAPUserManagementEnabled()) {
            $lri  = $this->getLdap()->searchLogin($event->getLoginName());
            if ($lri === false) {
                return;
            }
            $user = $this->getLdapUserManager()->getUserFromLdapIterator($lri);
            if ($user !== null) {
                $event->setUser($user);
            }
        }
    }

    /**
     * Hook
     * Params:
     *  IN  $params['user_id']
     *  OUT $params['entry_label']
     *  OUT $params['entry_value']
     */
    public function personalInformationEntry($params)
    {
        if ($this->isLdapAuthType()) {
            $params['entry_label'][$this->getId()] = $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login');

            $login_info = $this->getLdapLoginInfo($params['user_id']);
            if (! $login_info) {
                $login_info = $GLOBALS['Language']->getText('plugin_ldap', 'no_ldap_login_found');
            }
            $params['entry_value'][$this->getId()] = $login_info;
        }
    }

    /** @see UserDetailsPresenter::ADDITIONAL_DETAILS */
    public function additional_details($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $user = $params['user'];

            $ldap_id_label = $GLOBALS['Language']->getText('admin_usergroup', 'ldap_id');
            $ldap_id       = $user->getLdapId();

            $login_label    = $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login');
            $login_info     = $this->getLdapLoginInfo($user->getId());
            $has_login_info = true;
            if (! $login_info) {
                $has_login_info = false;
                $login_info     = $GLOBALS['Language']->getText('plugin_ldap', 'no_ldap_login_found');
            }

            $params['additional_details'][] = array(
                'login_label'    => $login_label,
                'login_info'     => $login_info,
                'ldap_id_label'  => $ldap_id_label,
                'ldap_id'        => $ldap_id,
                'has_login_info' => $has_login_info
            );
        }
    }

    private function getLdapLoginInfo($user_id)
    {
        $ldap_result = $this->getLdapUserManager()->getLdapFromUserId($user_id);
        if ($ldap_result !== false) {
            return $this->buildLinkToDirectory($ldap_result, $ldap_result->getLogin());
        }
    }

    /**
     * Hook
     */
    public function buildLinkToDirectory(LDAPResult $lr, $value = '')
    {
        if ($value === '') {
            $value = $lr->getLogin();
        }

        include_once($GLOBALS['Language']->getContent('directory_redirect', 'en_US', 'ldap'));
        if (function_exists('custom_build_link_to_directory')) {
            $link = custom_build_link_to_directory($lr, $value);
        } else {
            $link = $value;
        }
        return $link;
    }

    /**
     * Hook
     */
    public function cancelChange($params)
    {
        if ($this->isLdapAuthType()) {
            exit_permission_denied();
        }
    }

    public function before_register($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType() && ! $this->hasLDAPWrite()) {
            util_return_to('/account/login.php');
        }
    }

    public function warnNoPwChange($params)
    {
        global $Language;
        if ($this->isLdapAuthType()) {
            $params['additional_password_messages'][] = '<div class="tlp-alert-warning">' . $Language->getText('admin_user_changepw', 'ldap_warning') . '</div>';
        }
    }

    public function addLdapInput($params)
    {
        global $Language;
        if ($this->isLdapAuthType()) {
            echo $Language->getText('admin_usergroup', 'ldap_id') . ': <INPUT TYPE="TEXT" NAME="ldap_id" VALUE="' . $params['row_user']['ldap_id'] . '" SIZE="35" MAXLENGTH="55">
<P>';
        }
    }

    /**
     * Hook
     *
     * $params['user_id']
     *
     * @param $params
     *
     * @return void
     */
    public function updateLdapID($params)
    {
        global $Language;
        if ($this->isLdapAuthType()) {
            $request = HTTPRequest::instance();
            $ldapId = $request->getValidated('ldap_id', 'string', false);
            if ($ldapId !== false) {
                $result = db_query("UPDATE user SET ldap_id='" . db_es($ldapId) . "' WHERE user_id=" . db_ei($params['user_id']));
            }
            if (!$result) {
                $GLOBALS['feedback'] .= ' ' . $Language->getText('admin_usergroup', 'error_upd_u');
                echo db_error();
            } else {
                $GLOBALS['feedback'] .= ' ' . $Language->getText('admin_usergroup', 'success_upd_u');
            }
        }
    }

    /**
     * Hook
     *
     * $params['allow']
     *
     * @param Array $params
     *
     * @return void
     */
    public function forbidIfLdapAuth($params)
    {
        if ($this->isLdapAuthType()) {
            if (! $this->hasLDAPWrite()) {
                $params['allow'] = false;
            }
        }
    }

    /**
     * Hook
     *
     * OUT $params['allow']
     *
     * @param Array $params
     *
     * @return void
     */
    public function forbidIfLdapAuthAndUserLdap($params)
    {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        if ($this->isLdapAuthType() && $user->getLdapId() != '') {
            if (! $this->hasLDAPWrite()) {
                $params['allow'] = false;
            }
        }
    }

    public function passwordPreUpdateEvent(PasswordPreUpdateEvent $event)
    {
        if ($this->isLdapAuthType() && $event->getUser()->getLdapId() !== '' && ! $this->hasLDAPWrite()) {
            $event->forbidUserToChangePassword();
        }
    }

    public function accountInformationCollection(AccountInformationCollection $account_information)
    {
        if ($this->isLdapAuthType()) {
            if ($account_information->getUser()->getLdapId() !== '' && ! $this->hasLDAPWrite()) {
                $account_information->disableChangeRealName();
                $account_information->disableChangeEmail();
            }
            $ldap_result = $this->getLdapUserManager()->getLdapFromUserId($account_information->getUser()->getId());
            if ($ldap_result) {
                $account_information->addInformation(
                    new AccountInformationPresenter(
                        $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login'),
                        $ldap_result->getLogin(),
                    )
                );
            } else {
                $account_information->addInformation(
                    new AccountInformationPresenter(
                        $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login'),
                        $GLOBALS['Language']->getText('plugin_ldap', 'no_ldap_login_found'),
                    )
                );
            }
        }
    }

    public function project_admin_ugroup_deletion($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ldap_usergroup_manager = $this->getLdapUserGroupManager();
        $ldap_usergroup_manager->setId($params['ugroup_id']);

        $ldap_usergroup_manager->unbindFromBindLdap();
    }

    /**
     * @see Event::SVN_INTRO
     */
    public function svn_intro($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ldap_project_manager = new LDAP_ProjectManager();

        if ($this->isLdapAuthType() &&
           isset($params['group_id']) &&
           $ldap_project_manager->hasSVNLDAPAuth($params['group_id'])
        ) {
            $params['svn_intro_in_plugin'] = true;
            $params['svn_intro_info']      = $this->getLdapUserManager()->getLdapFromUserId(
                $params['user_id']
            );
        }
    }

    /**
     * Modify the user name before to check if user has access to given
     * ldap ressource (because users in .SVNAccessFile are defined with their
     * ldap login
     *
     * $params['project_svnroot']
     * $params['username']
     */
    public function svn_check_access_username($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $svnProjectManager = new LDAP_ProjectManager();
        if ($this->isLdapAuthType()
           && isset($params['project_svnroot'])
           && $svnProjectManager->hasSVNLDAPAuthByName(basename($params['project_svnroot']))) {
               $ldapUm = $this->getLdapUserManager();
               $lr     = $ldapUm->getLdapFromUserName($params['username']);
            if ($lr !== false) {
                // Must lower the username because LDAP is case insensitive
                // while svn permission comparator is case sensitive and in
                // backend the .SVNAccessFile is generated with lowercase
                // usernames
                $params['username'] = strtolower($lr->getLogin());
            }
        }
    }

    /**
     * Collects additional modals to display in project-admin > members
     *
     *
     * @return void
     */
    public function projectAdminMembersAdditionalModal(ProjectMembersAdditionalModalCollectionPresenter $collector)
    {
        if ($this->isLDAPGroupsUsageEnabled()) {
            $project_members_manager = $this->getLdapProjectGroupManager();
            $project_id              = $collector->getProject()->getID();
            $ldap_group              = $project_members_manager->getLdapGroupByGroupId($project_id);

            if ($ldap_group) {
                $group_name = $ldap_group->getGroupCommonName();
                $display_name = $ldap_group->getGroupDisplayName();
                $is_linked  = true;
            } else {
                $group_name = '';
                $display_name = '';
                $is_linked  = false;
            }

            $synchro_checked = $project_members_manager->isProjectBindingSynchronized($project_id);
            $bind_checked    = $project_members_manager->doesProjectBindingKeepUsers($project_id);

            $mustache_renderer = TemplateRendererFactory::build()->getRenderer(LDAP_TEMPLATE_DIR);

            $action_label = ($ldap_group)
                ? sprintf(dgettext('tuleap-ldap', "Update directory group binding (%s)"), $display_name)
                : dgettext('tuleap-ldap', "Set directory group binding");

            $modal_button = $mustache_renderer->renderToString(
                'project-members-ldap-link-modal-button',
                array('label' => $action_label)
            );

            $modal_content = $mustache_renderer->renderToString(
                'project-members-ldap-link-modal',
                new LinkModalContentPresenter(
                    $group_name,
                    $collector->getProject(),
                    $bind_checked,
                    $synchro_checked,
                    $is_linked,
                    $action_label,
                    $collector->getCurrentLocale(),
                    $collector->getCSRF(),
                    $display_name
                )
            );

            $collector->addModalButton($modal_button);

            $collector->addModalContent($modal_content);

            $collector->setJavascriptFile($this->getAssets()->getFileURL('project-admin-members.js'));
            $collector->setCssAsset(new \Tuleap\Layout\CssAsset($this->getAssets(), 'style'));
        }
    }

    public function burningParrotGetJavascriptFiles(array $params): void
    {
        if ($this->currentRequestIsForProjectUgroupAdmin()) {
            $params['javascript_files'][] = $this->getAssets()->getFileURL('project-admin-ugroups.js');
        }
    }

    public function burningParrotGetStylesheets(array $params): void
    {
        if ($this->currentRequestIsForProjectUgroupAdmin()) {
            $variant                 = $params['variant'];
            $params['stylesheets'][] = $this->getAssets()->getFileURL('style-' . $variant->getName() . '.css');
        }
    }

    /**
     * @psalm-mutation-free
     */
    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/ldap',
            '/assets/ldap'
        );
    }

    /**
     * Check if adding or deleting users from the ugroup is allowed
     *
     * @param Array $params
     *
     * @return Void
     */
    public function ugroup_update_users_allowed(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['ugroup_id']) {
            $ldapUserGroupManager = $this->getLdapUserGroupManager();
            if (!$ldapUserGroupManager->isMembersUpdateAllowed($params['ugroup_id'])) {
                $params['allowed'] = false;
            }
        }
    }

    /**
     * Project creation hook
     *
     * If set, activate LDAP based authentication for this new project
     *
     * @param Array $params
     */
    public function register_project_creation(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType() && $this->getLdap()->getLDAPParam('svn_auth') == 1) {
            $svnProjectManager = new LDAP_ProjectManager();
            $svnProjectManager->setLDAPAuthForSVN($params['group_id']);
        }
    }

    /**
     * Hook
     *
     * @param Array $params
     *
     * @return void
     */
    public function backend_factory_get_svn(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $params['base']  = 'LDAP_BackendSVN';
            $params['setup'] = array($this->getLdap());
        }
    }

    public function svn_apache_auth($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $ldapProjectManager = new LDAP_ProjectManager();
            if ($ldapProjectManager->hasSVNLDAPAuth($params['project_info']['group_id'])) {
                $params['svn_apache_auth'] = new LDAP_SVN_Apache_ModPerl(
                    $this->getLdap(),
                    $params['cache_parameters'],
                    $params['project_info']
                );
            }
        }
    }

    /**
     * @see \Tuleap\svn\Event\GetSVNLoginNameEvent
     */
    public function getSvnLoginName(GetSVNLoginNameEvent $event)
    {
        if (! $this->isLdapAuthType()) {
            return;
        }
        $ldap_project_manager = new LDAP_ProjectManager();
        if (! $ldap_project_manager->hasSVNLDAPAuth($event->getProject()->getID())) {
            return;
        }

        $user_name = $event->getUsername();
        $ldap_user = $this->getLdapUserManager()->getLdapLoginFromUserIds([$event->getUser()->getId()])->getRow();
        if ($ldap_user['ldap_uid'] !== false) {
            $user_name = $ldap_user['ldap_uid'];
        }

        $ldap_result_iterator = $this->getLdap()->searchLogin($user_name);
        if ($ldap_result_iterator && count($ldap_result_iterator) === 1) {
            $event->setUsername($ldap_result_iterator->current()->getLogin());
        } else {
            $event->setUsername('');
        }
    }

    /**
     * Hook
     *
     * @param Array $params
     *
     * @return void
     */
    public function codendi_daily_start($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType() && $this->isDailySyncEnabled()) {
            $this->getLogger()->info('Starting LDAP daily synchronisation');

            $ldapQuery = new LDAP_DirectorySynchronization($this->getLdap(), $this->getLogger());
            $ldapQuery->syncAll();

            $retentionPeriod = $this->getLdap()->getLDAPParam('daily_sync_retention_period');
            if ($retentionPeriod != null && $retentionPeriod != "") {
                $ldapCleanUpManager = new LDAP_CleanUpManager(
                    $this->getUserRemover(),
                    $retentionPeriod
                );

                $ldapCleanUpManager->cleanAll();
            }

            $this->synchronizeProjectMembers();
            $this->synchronizeStaticUgroupMembers();

            $this->getLogger()->info('LDAP daily synchronisation done');
            return true;
        }
    }

    private function synchronizeProjectMembers()
    {
        $this->getLogger()->info('LDAP daily synchronisation: project members');

        $ldap_project_group_manager = $this->buildLdapProjectGroupManager($this->getGroupSyncNotificationsManager());
        $ldap_project_group_manager->synchronize();
    }

    private function synchronizeStaticUgroupMembers()
    {
        $this->getLogger()->info('LDAP daily synchronisation: static ugroup members');

        $ldapUserGroupManager = $this->getLdapUserGroupManager();
        $ldapUserGroupManager->synchronizeUgroups();
    }

    public function rootDailyStart(RootDailyStartEvent $event)
    {
        if ($this->isLdapAuthType()) {
            $retriever       = new NonUniqueUidRetriever(new LDAP_UserDao());
            $non_unique_uids = $retriever->getNonUniqueLdapUid();
            if ($non_unique_uids) {
                $event->addWarning('The following ldap_uids are non unique: ' . implode(', ', $non_unique_uids)
                                      . PHP_EOL . ' This might lead to some SVN misbehaviours for concerned users');
            }
        }
    }

    private function isLdapAuthType()
    {
        return ForgeConfig::get('sys_auth_type') === ForgeConfig::AUTH_TYPE_LDAP;
    }

    /**
     * The daily synchro is enabled if the variable is not defined or if the variable is defined to 1
     *
     * This is for backward compatibility (when daily_sync was not yet defined).
     *
     * @return bool
     */
    protected function isDailySyncEnabled()
    {
        return $this->isParamEnabled('daily_sync');
    }

    protected function isLDAPUserManagementEnabled()
    {
        return $this->isParamEnabled('user_management');
    }

    protected function isLDAPGroupsUsageEnabled()
    {
        return $this->isParamEnabled('grp_enabled');
    }

    /**
     * Return true if the parameter is defined and enabled or not defined at all.
     *
     * @param String $key
     *
     * @return bool
     */
    protected function isParamEnabled($key)
    {
        $value = $this->getLDAP()->getLDAPParam($key);
        if ($value === null || $value == 1) {
            return true;
        }
        return false;
    }

    public function system_event_get_types_for_default_queue($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][] = 'PLUGIN_LDAP_UPDATE_LOGIN';
    }

    public function get_system_event_class($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        switch ($params['type']) {
            case 'PLUGIN_LDAP_UPDATE_LOGIN':
                include_once dirname(__FILE__) . '/system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php';
                $params['class'] = 'SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN';
                $params['dependencies'] = array(
                    UserManager::instance(),
                    Backend::instance(Backend::SVN),
                    ProjectManager::instance(),
                    new LDAP_ProjectManager()
                );
                break;
        }
    }

    public function get_ldap_login_name_for_user($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $params['ldap_user'] = $this->getLdapUserManager()->getLDAPUserFromUser($params['user']);
        }
    }

    public function login_presenter($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            include_once dirname(__FILE__) . '/LoginPresenter.class.php';
            $params['authoritative'] = true;
            $params['presenter']     = new LDAP_LoginPresenter($params['presenter']);
        }
    }

    /**
     * @see Event::USER_MANAGER_UPDATE_DB
     */
    public function user_manager_update_db(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        try {
            $this->getLDAPUserWrite()->updateWithPreviousUser($params['old_user'], $params['new_user']);
        } catch (LDAP_Exception_NoWriteException $exception) {
            $this->getLogger()->debug('User info not updated in LDAP, no write LDAP configured');
        } catch (Exception $exception) {
            $this->getLogger()->error('An error occured while updating user settings (user_manager_update_db): ' . $exception->getMessage());
        }
    }

    /**
     *
     * @see Event::USER_MANAGER_CREATE_ACCOUNT
     */
    public function user_manager_create_account(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        try {
            $this->getLDAPUserWrite()->updateWithUser($params['user']);
        } catch (LDAP_Exception_NoWriteException $exception) {
            $this->getLogger()->debug('User info not updated in LDAP, no write LDAP configured');
        } catch (Exception $exception) {
            $this->getLogger()->error('An error occured while activating user as site admin (project_admin_activate_user): ' . $exception->getMessage());
        }
    }

    private function getLDAPUserWrite()
    {
        return new LDAP_UserWrite(
            $this->getLDAPWrite(),
            UserManager::instance(),
            new UserDao(),
            new LDAP_UserDao(),
            $this->getLogger()
        );
    }

    /**
     * @see GIT_EVENT_PLATFORM_CAN_USE_GERRIT
     */
    public function git_event_platform_can_use_gerrit($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ldap_params = $this->getLDAPParams();

        $platform_uses_ldap_for_authentication = $this->isLdapAuthType();
        $ldap_write_server_is_configured       = isset($ldap_params['write_server']) && trim($ldap_params['write_server']) != '';

        if ($platform_uses_ldap_for_authentication || $ldap_write_server_is_configured) {
            $params['platform_can_use_gerrit'] = true;
        }
    }

    public function getAdministrationOptions()
    {
        $this->updateAdministrationOptions();

        if (! file_exists($this->getConfigFilePath())) {
            $presenter = new LDAP_AdministrationPresenter($this->getId());
            $renderer  = TemplateRendererFactory::build()->getRenderer(LDAP_TEMPLATE_DIR);
            return $renderer->renderToString('ldap-administration', $presenter);
        }
    }

    private function updateAdministrationOptions()
    {
        $request   = HTTPRequest::instance();
        $ldap_type = $request->getValidated('ldap_type', 'string', false);

        if (! $ldap_type) {
            return;
        }

        if ($ldap_type === LDAP::SERVER_TYPE_ACTIVE_DIRECTORY) {
            $config_file = $this->getEtcDir() . LDAP::SERVER_TYPE_ACTIVE_DIRECTORY . '.inc';
        } else {
            $config_file = $this->getEtcDir() . LDAP::SERVER_TYPE_OPEN_LDAP . '.inc';
        }

        if (! file_exists($this->getConfigFilePath())) {
            copy($config_file, $this->getConfigFilePath());
            $GLOBALS['Response']->redirect('/plugins/pluginsadministration//?view=properties&plugin_id=' . $this->getId());
        }
    }

    private function getEtcDir()
    {
        return $GLOBALS['sys_custompluginsroot'] . 'ldap/etc/';
    }

    private function getConfigFilePath()
    {
        return $this->getEtcDir() . 'ldap.inc';
    }

    public function ugroup_duplication($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $dao              = $this->getUserGroupDao();
        $source_ugroup_id = $params['source_ugroup']->getId();
        $new_ugroup_id    = $params['new_ugroup_id'];

        $dao->duplicateLdapBinding($source_ugroup_id, $new_ugroup_id);
    }

    private function isGroupSyncAdminNotificationsEnabled(): bool
    {
        return (bool) $this->getLdap()->getLDAPParam('grp_sync_admin_notifications_enabled');
    }

    /**
     * @return \Tuleap\LDAP\GroupSyncNotificationsManager
     * */
    private function getGroupSyncNotificationsManager()
    {
        if ($this->isGroupSyncAdminNotificationsEnabled()) {
            return new GroupSyncAdminEmailNotificationsManager(
                $this->getLdapUserManager(),
                new \Codendi_Mail(),
                \UserManager::instance()
            );
        }
        return $this->getSilentNotificationsManager();
    }

    /**
     * @return \Tuleap\LDAP\GroupSyncNotificationsManager
     * */
    private function getSilentNotificationsManager()
    {
        return new \Tuleap\LDAP\GroupSyncSilentNotificationsManager();
    }

    /**
     * @return LDAP_UserGroupManager
     */
    public function getLdapUserGroupManager()
    {
        return new LDAP_UserGroupManager(
            $this->getLdap(),
            $this->getLdapUserManager(),
            $this->getUserGroupDao(),
            ProjectManager::instance(),
            $this->getLogger(),
            $this->getSilentNotificationsManager()
        );
    }

    /**
     * @return LDAP_UserGroupDao
     */
    private function getUserGroupDao()
    {
        return new LDAP_UserGroupDao(CodendiDataAccess::instance());
    }

    /**
     * @return LDAP_ProjectGroupManager
     * */
    public function getLdapProjectGroupManager()
    {
        return $this->buildLdapProjectGroupManager($this->getSilentNotificationsManager());
    }

    /**
     * @return LDAP_ProjectGroupManager
     */
    private function buildLdapProjectGroupManager(\Tuleap\LDAP\GroupSyncNotificationsManager $notifications_manager)
    {
        $user_manager    = UserManager::instance();
        $project_manager = ProjectManager::instance();
        $manager = new LDAP_ProjectGroupManager(
            $this->getLdap(),
            $this->getLdapUserManager(),
            $this->getLdapProjectGroupDao(),
            $project_manager,
            $user_manager,
            $notifications_manager,
            new ProjectGroupManagerRestrictedUserFilter($user_manager)
        );
        return $manager;
    }

    /**
     * @return LDAP_ProjectGroupDao
     */
    private function getLdapProjectGroupDao()
    {
        return new LDAP_ProjectGroupDao(
            CodendiDataAccess::instance(),
            $this->getUserRemover()
        );
    }

    private function getUserRemover()
    {
        return new UserRemover(
            ProjectManager::instance(),
            EventManager::instance(),
            new ArtifactTypeFactory(false),
            new UserRemoverDao(),
            UserManager::instance(),
            new ProjectHistoryDao(),
            new UGroupManager()
        );
    }

    private function currentRequestIsForProjectUgroupAdmin()
    {
        return strpos($_SERVER['REQUEST_URI'], '/project/admin/editugroup') === 0;
    }

    public function bindingAdditionalModalPresenterCollection(BindingAdditionalModalPresenterCollection $collection)
    {
        $request = HTTPRequest::instance();
        $builder = new AdditionalModalPresenterBuilder($this->getLdapUserGroupManager(), $request);
        $collection->addModal(
            $builder->build(
                $collection->getUgroup(),
                $this->getBindOption($request),
                $this->getSynchro($request),
                $collection->getCSRF()
            )
        );
    }

    public function ugroupEditProcessAction(UGroupEditProcessAction $event)
    {
        $request = $event->getRequest();
        $ugroup  = $event->getUGroup();
        $csrf    = $event->getCSRF();

        $ldapUserGroupManager = $this->getLdapUserGroupManager();
        $ldapUserGroupManager->setId($ugroup->getId());
        $ldapUserGroupManager->setProjectId($ugroup->getProjectId());

        switch ($request->get('action')) {
            case 'ldap_remove_binding':
                $event->setHasBeenHandledToTrue();
                $csrf->check(UGroupRouter::getUGroupUrl($ugroup));
                $ldapUserGroupManager->setGroupName($request->get('previous_bind_with_group'));
                if ($ldapUserGroupManager->unbindFromBindLdap()) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_manager_unlink')
                    );
                    $event->getEditEventLauncher()->launch($ugroup);
                }
                break;
            case 'ldap_add_binding':
                $event->setHasBeenHandledToTrue();
                $csrf->check(UGroupRouter::getUGroupUrl($ugroup));
                $ldap_group_name = $request->get('bind_with_group');
                $ldapUserGroupManager->setGroupName($ldap_group_name);
                if ($ldapUserGroupManager->getGroupDn()) {
                    $ldapUserGroupManager->bindWithLdap($this->getBindOption($request), $this->getSynchro($request));
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('project_ugroup_binding', 'link_ldap_group', $ldap_group_name)
                    );
                    $event->getEditEventLauncher()->launch($ugroup);
                } else {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('project_ugroup_binding', 'ldap_group_error', $ldap_group_name)
                    );
                }
                break;
        }
    }

    private function getSynchro(Codendi_Request $request)
    {
        $synchro = LDAP_GroupManager::NO_SYNCHRONIZATION;
        if ($request->get('synchronize')) {
            $synchro = LDAP_GroupManager::AUTO_SYNCHRONIZATION;
        }

        return $synchro;
    }

    private function getBindOption(Codendi_Request $request)
    {
        $bind_option = LDAP_GroupManager::BIND_OPTION;
        if ($request->get('preserve_members')) {
            $bind_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
        }

        return $bind_option;
    }

    public function membersEditProcessAction(MembersEditProcessAction $event)
    {
        $request = $event->getRequest();
        $csrf    = $event->getCSRF();

        $ldap_group_manager = $this->getLdapProjectGroupManager();

        $ldap_group_manager->setId($event->getProject()->getID());
        $ldap_group_manager->setGroupName($request->get('ldap_group'));

        switch ($request->get('action')) {
            case 'ldap_add_binding':
                $csrf->check();
                $event->setHasBeenHandledToTrue();
                $ldap_group_manager->bindWithLdap(
                    $this->getBindOption($request),
                    $this->getSynchro($request)
                );
                break;
            case 'ldap_remove_binding':
                $csrf->check();
                $event->setHasBeenHandledToTrue();
                $ldap_group_manager->unbindFromBindLdap();
                break;
        }
    }

    public function routeGetWelcome() : DispatchableWithRequest
    {
        return new \Tuleap\LDAP\WelcomeDisplayController($this->getLdapUserManager(), Codendi_HTMLPurifier::instance(), $this->getPluginPath());
    }

    public function routePostWelcome() : DispatchableWithRequest
    {
        return new \Tuleap\LDAP\WelcomeUpdateController(UserManager::instance(), new LDAP_UserDao(), new Account_TimezonesCollection());
    }

    public function routeGetAutocomplete() : DispatchableWithRequest
    {
        return new \Tuleap\LDAP\GroupAutocompleteController($this->getLdap());
    }

    public function routeGetBindUgroupConfirm() : DispatchableWithRequest
    {
        return new \Tuleap\LDAP\BindUgroupConfirmController(new UGroupManager(), $this->getLdapUserGroupManager(), UserManager::instance(), UserHelper::instance());
    }

    public function routeGetBindMembersConfirm() : DispatchableWithRequest
    {
        return new \Tuleap\LDAP\BindMembersConfirmController($this->getLdapProjectGroupManager(), UserManager::instance(), UserHelper::instance(), new \Tuleap\Project\Admin\MembershipDelegationDao());
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->get('/welcome', $this->getRouteHandler('routeGetWelcome'));
            $r->post('/welcome', $this->getRouteHandler('routePostWelcome'));

            $r->get('/autocomplete', $this->getRouteHandler('routeGetAutocomplete'));
            $r->get('/bind-ugroup-confirm', $this->getRouteHandler('routeGetBindUgroupConfirm'));
            $r->get('/bind-members-confirm', $this->getRouteHandler('routeGetBindMembersConfirm'));
        });
    }
}

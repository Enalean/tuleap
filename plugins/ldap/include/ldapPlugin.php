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
use Tuleap\LDAP\User\UserDao;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\SVNCore\SVNAccessFileDefaultBlockOverride;
use Tuleap\CLI\Command\ConfigDumpEvent;
use Tuleap\Git\Git\RemoteServer\GerritCanMigrateEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\LDAP\Exception\IdentifierTypeNotFoundException;
use Tuleap\LDAP\Exception\IdentifierTypeNotRecognizedException;
use Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager;
use Tuleap\LDAP\LdapLogger;
use Tuleap\LDAP\LinkModalContentPresenter;
use Tuleap\LDAP\NonUniqueUidDAO;
use Tuleap\LDAP\Project\UGroup\Binding\AdditionalModalPresenterBuilder;
use Tuleap\LDAP\ProjectGroupManagerRestrictedUserFilter;
use Tuleap\LDAP\SVN\SVNAccessFileDefaultBlockForLDAP;
use Tuleap\LDAP\User\AccountCreation;
use Tuleap\LDAP\User\CreateUserFromEmail;
use Tuleap\Project\Admin\ProjectMembers\MembersEditProcessAction;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersAdditionalModalCollectionPresenter;
use Tuleap\Project\Admin\ProjectUGroup\BindingAdditionalModalPresenterCollection;
use Tuleap\Project\Admin\ProjectUGroup\UGroupEditProcessAction;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\REST\UserGroupAdditionalInformationEvent;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\SVNCore\Event\GetSVNLoginNameEvent;
use Tuleap\SystemEvent\RootDailyStartEvent;
use Tuleap\User\Account\AccountCreated;
use Tuleap\User\Account\AccountInformationCollection;
use Tuleap\User\Account\AccountInformationPresenter;
use Tuleap\User\Account\AuthenticationMeanName;
use Tuleap\User\Account\PasswordPreUpdateEvent;
use Tuleap\User\Account\RedirectAfterLogin;
use Tuleap\User\Account\RegistrationGuardEvent;
use Tuleap\User\Admin\UserDetailsPresenter;
use Tuleap\User\AfterLocalStandardLogin;
use Tuleap\User\BeforeStandardLogin;
use Tuleap\User\FindUserByEmailEvent;
use Tuleap\User\PasswordVerifier;
use Tuleap\User\UserNameNormalizer;
use Tuleap\User\UserRetrieverByLoginNameEvent;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class LdapPlugin extends Plugin
{
    /**
     * @type LDAP
     */
    private $ldapInstance;

    /**
     * @type LDAP_UserManager
     */
    private $_ldapUmInstance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    public function __construct($id)
    {
        parent::__construct($id);

        bindTextDomain('tuleap-ldap', LDAP_SITE_CONTENT_DIR);
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
        if (! isset($this->ldapInstance)) {
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

    private function getLDAPParams(): array
    {
        $this->getPluginInfo()->loadProperties();
        $ldap_params = [];
        foreach (LDAP::CONFIGURATION_VARIABLES as $configuration_variable) {
            $ldap_params[str_replace('sys_ldap_', '', $configuration_variable)] = ForgeConfig::get(
                $configuration_variable,
                null
            );
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
        if (! isset($this->_ldapUmInstance)) {
            $this->_ldapUmInstance = new LDAP_UserManager(
                $this->getLdap(),
                LDAP_UserSync::instance(),
                new UserNameNormalizer(
                    new Rule_UserName(),
                    new Cocur\Slugify\Slugify()
                ),
                new PasswordVerifier(new StandardPasswordHandler()),
            );
        }
        return $this->_ldapUmInstance;
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
     */
    #[\Tuleap\Plugin\ListeningToEventName('ajax_search_user')]
    public function ajaxSearchUser($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLDAPUserManagementEnabled() && ! $params['codendiUserOnly']) {
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
                        $params['userList'][] = [
                            'display_name' => $tuleap_user->getRealName() . ' (' . $tuleap_user->getUserName() . ')',
                            'login'        => $tuleap_user->getUserName(),
                            'user_id'      => $tuleap_user->getId(),
                            'has_avatar'   => $tuleap_user->hasAvatar(),
                            'avatar_url'   => $tuleap_user->getAvatarUrl(),
                        ];
                    } else {
                        $params['userList'][] = [
                            'display_name' => $sync->getCommonName($lr) . ' (' . $lr->getLogin() . ')',
                            'login'        => $lr->getLogin(),
                            'user_id'      => $tuleap_user_id,
                            'has_avatar'   => false,
                            'avatar_url'   => '',
                        ];
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

    #[\Tuleap\Plugin\ListeningToEventName(Event::SEARCH_TYPE)]
    public function searchType($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $query  = $params['query'];
        $result = $params['results'];

        if ($this->isLdapAuthType() && $query->getTypeOfSearch() == Search_SearchPeople::NAME) {
            $search    = new LDAP_SearchPeople(UserManager::instance(), $this->getLdap());
            $presenter = $search->search($query, $result);
            $result->setResultsHtml($this->getSearchTemplateRenderer()->renderToString($presenter->getTemplate(), $presenter));
        }
    }

    public function getSearchTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(
            [
                dirname(__FILE__) . '/../templates',
                ForgeConfig::get('codendi_dir') . '/src/templates/search',
            ]
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function beforeLogin(BeforeStandardLogin $event): void
    {
        if ($this->isLdapAuthType()) {
            try {
                $user = $this->getLdapUserManager()->authenticate($event->getLoginName(), $event->getPassword());
                if ($user) {
                    $event->setUser($user);
                }
            } catch (LDAP_UserNotFoundException $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            } catch (LDAP_AuthenticationFailedException $exception) {
                $this->getLogger()->info("[LDAP] User " . $event->getLoginName() . " failed to authenticate");
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function beforeSVNLogin(\Tuleap\SVNCore\AccessControl\BeforeSVNLogin $event): void
    {
        if ($this->isLdapAuthType() && (new LDAP_ProjectManager())->hasSVNLDAPAuth((int) $event->project->getID())) {
            try {
                $event->setUser($this->getLdapUserManager()->authenticate($event->getLoginName(), $event->getPassword()));
            } catch (LDAP_UserNotFoundException | LDAP_AuthenticationFailedException $ex) {
                // Do nothing, user will not be able to login
            }
        }
    }

    /**
     * When redirection after login happens, check if user as already filled
     * his personal info or not. If it's not the case, it means that the
     * account was automatically created and user must complete his
     * registration.
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function redirectAfterLogin(RedirectAfterLogin $event): void
    {
        if ($this->isLdapAuthType()) {
            $ldapUserDao = new UserDao();
            if (! $ldapUserDao->alreadyLoggedInOnce((int) $event->user->getId())) {
                $return_to_arg = "";
                if ($event->getReturnTo()) {
                    $return_to_arg = '?return_to=' . urlencode($event->getReturnTo());
                    if ($event->is_pv2) {
                        $return_to_arg .= '&pv=2';
                    }
                } elseif ($event->is_pv2) {
                    $return_to_arg .= '?pv=2';
                }
                $event->setReturnTo('/plugins/ldap/welcome' . $return_to_arg);
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function afterLocalLogin(AfterLocalStandardLogin $event): void
    {
        if ($this->isLdapAuthType()) {
            if ($event->user->getLdapId() != null) {
                $this->getLogger()->info(
                    sprintf(
                        "User %s was found in LDAP but LDAP authentication failed. No fallback on local login.",
                        $event->user->getUserName(),
                    )
                );
                $event->refuseLogin(_('Invalid Password Or User Name'));
                return;
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function afterLocalSVNLogin(\Tuleap\SVNCore\AccessControl\AfterLocalSVNLogin $event): void
    {
        if ($this->isLdapAuthType() && (new LDAP_ProjectManager())->hasSVNLDAPAuth((int) $event->project->getID())) {
            if ($event->user->getLdapId() !== null) {
                $this->getLogger()->info(
                    sprintf(
                        "User %s was found in LDAP but LDAP authentication failed. No fallback on SVN login.",
                        $event->user->getUserName(),
                    )
                );
                $event->refuseLogin(_('Invalid Password Or User Name'));
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function retrieveUserBySVNLoginName(\Tuleap\SVNCore\AccessControl\UserRetrieverBySVNLoginNameEvent $event): void
    {
        if ($this->isLdapAuthType() && (new LDAP_ProjectManager())->hasSVNLDAPAuth((int) $event->project->getID())) {
            $event->can_user_be_provided_by_other_means = false;
            $event->user                                = $this->getLdapUserManager()->getUserByIdentifier('ldapuid:' . $event->login_name);
        }
    }

    /**
     * Hook
     * Params:
     *  IN  $params['ident']
     *  IN/OUT  $params['user'] User object if found or null.
     */
    #[\Tuleap\Plugin\ListeningToEventName('user_manager_find_user')]
    public function userManagerFindUser($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLDAPUserManagementEnabled()) {
            $ldap = $this->getLdap();

            if (preg_match("/^\d+$/", $params["ident"])) {
                $params["user"] = $this->getUserManager()->getUserById((int) $params["ident"]);
            }

            if ($params["user"] !== null) {
                return;
            }
            // First, test if its provided by autocompleter: "Common Name (login name)"
            $matches = [];
            if (preg_match('/^(.*) \((.*)\)$/', $params['ident'], $matches)) {
                if (trim($matches[2]) != '') {
                    $lri = $ldap->searchLogin($matches[2]);
                } else {
                    $lri = $ldap->searchCommonName($matches[1]);
                }
            } else {
                // Otherwise, search as defined in config most of the time
                // (uid, email, common name)
                $lri = $ldap->searchUser($params['ident']);
            }
            if ($lri !== false) {
                $params['user'] = $this->getLdapUserManager()->getUserFromLdapIterator($lri);
            } else {
                $params['user'] = null;
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('user_manager_get_user_by_identifier')]
    public function userManagerGetUserByIdentifier($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getUserByLoginName(UserRetrieverByLoginNameEvent $event): void
    {
        if ($this->isLdapAuthType() && $this->isLDAPUserManagementEnabled()) {
            $lri = $this->getLdap()->searchLogin($event->getLoginName());
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
    #[\Tuleap\Plugin\ListeningToEventName('user_home_pi_entry')]
    public function userHomePiEntry($params): void
    {
        if ($this->isLdapAuthType()) {
            $params['entry_label'][$this->getId()] = sprintf(dgettext('tuleap-ldap', '%1$s login'), $this->getLDAPServerCommonName());

            $login_info = $this->getLdapLoginInfo($params['user_id']);
            if (! $login_info) {
                $login_info = sprintf(dgettext('tuleap-ldap', 'No %1$s login found'), $this->getLDAPServerCommonName());
            }
            $params['entry_value'][$this->getId()] = $login_info;
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(UserDetailsPresenter::ADDITIONAL_DETAILS)]
    public function additionalDetails($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $user = $params['user'];

            $ldap_id_label = $GLOBALS['Language']->getText('admin_usergroup', 'ldap_id');
            $ldap_id       = $user->getLdapId();

            $login_label    = sprintf(dgettext('tuleap-ldap', '%1$s login'), $this->getLDAPServerCommonName());
            $login_info     = $this->getLdapLoginInfo($user->getId());
            $has_login_info = true;
            if (! $login_info) {
                $has_login_info = false;
                $login_info     = sprintf(dgettext('tuleap-ldap', 'No %1$s login found'), $this->getLDAPServerCommonName());
            }

            $params['additional_details'][] = [
                'login_label'    => $login_label,
                'login_info'     => $login_info,
                'ldap_id_label'  => $ldap_id_label,
                'ldap_id'        => $ldap_id,
                'has_login_info' => $has_login_info,
            ];
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

    #[\Tuleap\Plugin\ListeningToEventName('before_lostpw')]
    public function beforeLostpw($params): void
    {
        if ($this->isLdapAuthType()) {
            exit_permission_denied();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('before_lostpw-confirm')]
    public function beforeLostpwConfirm($params): void
    {
        if ($this->isLdapAuthType()) {
            exit_permission_denied();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function registrationGuardEvent(RegistrationGuardEvent $event): void
    {
        if ($this->isLdapAuthType()) {
            $event->disableRegistration();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('before_admin_change_pw')]
    public function beforeAdminChangePw($params): void
    {
        global $Language;
        if ($this->isLdapAuthType()) {
            $params['additional_password_messages'][] = '<div class="tlp-alert-warning">' . $Language->getText('admin_user_changepw', 'ldap_warning') . '</div>';
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('usergroup_update_form')]
    public function usergroupUpdateForm($params): void
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
     */
    #[\Tuleap\Plugin\ListeningToEventName('usergroup_update')]
    public function usergroupUpdate($params): void
    {
        global $Language;
        if ($this->isLdapAuthType()) {
            $request = HTTPRequest::instance();
            $ldapId  = $request->getValidated('ldap_id', 'string', false);
            if ($ldapId !== false) {
                $result = db_query("UPDATE user SET ldap_id='" . db_es($ldapId) . "' WHERE user_id=" . db_ei($params['user_id']));
            }
            if (! $result) {
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
     */
    #[\Tuleap\Plugin\ListeningToEventName('display_lostpw_createaccount')]
    public function displayLostpwCreateaccount($params): void
    {
        if ($this->isLdapAuthType()) {
            $params['allow'] = false;
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
        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        if ($this->isLdapAuthType() && $user->getLdapId() != '') {
            $params['allow'] = false;
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function passwordPreUpdateEvent(PasswordPreUpdateEvent $event): void
    {
        if ($this->isLdapAuthType() && $event->getUser()->getLdapId() !== '') {
            $event->forbidUserToChangePassword();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function accountInformationCollection(AccountInformationCollection $account_information): void
    {
        if ($this->isLdapAuthType()) {
            if ($account_information->getUser()->getLdapId() !== '') {
                $account_information->disableChangeRealName();
                $account_information->disableChangeEmail();
            }
            $ldap_result = $this->getLdapUserManager()->getLdapFromUserId($account_information->getUser()->getId());
            if ($ldap_result) {
                $account_information->addInformation(
                    new AccountInformationPresenter(
                        sprintf(dgettext('tuleap-ldap', '%1$s login'), $this->getLDAPServerCommonName()),
                        $ldap_result->getLogin(),
                    )
                );
            } else {
                $account_information->addInformation(
                    new AccountInformationPresenter(
                        sprintf(dgettext('tuleap-ldap', '%1$s login'), $this->getLDAPServerCommonName()),
                        sprintf(dgettext('tuleap-ldap', 'No %1$s login found'), $this->getLDAPServerCommonName()),
                    )
                );
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('project_admin_ugroup_deletion')]
    public function projectAdminUgroupDeletion($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ldap_usergroup_manager = $this->getLdapUserGroupManager();
        $ldap_usergroup_manager->setId($params['ugroup_id']);

        $ldap_usergroup_manager->unbindFromBindLdap();
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::SVN_INTRO)]
    public function svnIntro($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ldap_project_manager = new LDAP_ProjectManager();

        if (
            $this->isLdapAuthType() &&
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
    #[\Tuleap\Plugin\ListeningToEventName('svn_check_access_username')]
    public function svnCheckAccessUsername($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $svnProjectManager = new LDAP_ProjectManager();
        if (
            $this->isLdapAuthType()
            && isset($params['project_svnroot'])
            && $svnProjectManager->hasSVNLDAPAuthByName(basename($params['project_svnroot']))
        ) {
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
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectAdminMembersAdditionalModal(ProjectMembersAdditionalModalCollectionPresenter $collector): void
    {
        if ($this->isLDAPGroupsUsageEnabled()) {
            $project_members_manager = $this->getLdapProjectGroupManager();
            $project_id              = $collector->getProject()->getID();
            $ldap_group              = $project_members_manager->getLdapGroupByGroupId($project_id);

            if ($ldap_group) {
                $group_name   = $ldap_group->getGroupCommonName();
                $display_name = $ldap_group->getGroupDisplayName();
                $is_linked    = true;
            } else {
                $group_name   = '';
                $display_name = '';
                $is_linked    = false;
            }

            $synchro_checked = $project_members_manager->isProjectBindingSynchronized($project_id);
            $bind_checked    = $project_members_manager->doesProjectBindingKeepUsers($project_id);

            $mustache_renderer = TemplateRendererFactory::build()->getRenderer(LDAP_TEMPLATE_DIR);

            $action_label = ($ldap_group)
                ? sprintf(dgettext('tuleap-ldap', "Update directory group binding (%s)"), $display_name)
                : dgettext('tuleap-ldap', "Set directory group binding");

            $modal_button = $mustache_renderer->renderToString(
                'project-members-ldap-link-modal-button',
                ['label' => $action_label]
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
                    $display_name,
                    $this->getLDAPServerCommonName(),
                )
            );

            $collector->addModalButton($modal_button);

            $collector->addModalContent($modal_content);

            $collector->setJavascriptFile($this->getAssets()->getFileURL('project-admin-members.js'));
            $collector->setCssAsset(new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'ldap-style'));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES)]
    public function burningParrotGetJavascriptFiles(array $params): void
    {
        if ($this->currentRequestIsForProjectUgroupAdmin()) {
            $params['javascript_files'][] = $this->getAssets()->getFileURL('project-admin-ugroups.js');
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_STYLESHEETS)]
    public function burningParrotGetStylesheets(array $params): void
    {
        if ($this->currentRequestIsForProjectUgroupAdmin()) {
            $params['stylesheets'][] = $this->getAssets()->getFileURL('ldap-style.css');
        }
    }

    /**
     * @psalm-mutation-free
     */
    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
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
    #[\Tuleap\Plugin\ListeningToEventName(Event::UGROUP_UPDATE_USERS_ALLOWED)]
    public function ugroupUpdateUsersAllowed(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['ugroup_id']) {
            $ldapUserGroupManager = $this->getLdapUserGroupManager();
            if (! $ldapUserGroupManager->isMembersUpdateAllowed($params['ugroup_id'])) {
                $params['allowed'] = false;
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        if ($this->isLdapAuthType() && $this->getLdap()->getLDAPParam('svn_auth') == 1) {
            $svnProjectManager = new LDAP_ProjectManager();
            $svnProjectManager->setLDAPAuthForSVN((int) $event->getJustCreatedProject()->getID());
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function svnAccessFileDefaultBlockOverride(SVNAccessFileDefaultBlockOverride $event): void
    {
        if (! $this->isLdapAuthType()) {
            return;
        }
        $provider = new SVNAccessFileDefaultBlockForLDAP($this->getLdapUserManager(), new LDAP_ProjectManager());
        $provider->handle($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getSvnLoginName(GetSVNLoginNameEvent $event): void
    {
        if (! $this->isLdapAuthType()) {
            return;
        }
        $ldap_project_manager = new LDAP_ProjectManager();
        if (! $ldap_project_manager->hasSVNLDAPAuth((int) $event->getProject()->getID())) {
            return;
        }

        $user_name = $event->getUsername();
        $ldap_user = $this->getLdapUserManager()->getLdapLoginFromUserIds([(int) $event->getUser()->getId()])[0];
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
     */
    #[\Tuleap\Plugin\ListeningToEventName('codendi_daily_start')]
    public function codendiDailyStart($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
        }
    }

    private function synchronizeProjectMembers(): void
    {
        $this->getLogger()->info('LDAP daily synchronisation: project members');

        $ldap_project_group_manager = $this->buildLdapProjectGroupManagerWithoutPermissionsCheckOnProjectMemberAdd(
            $this->getGroupSyncNotificationsManager(),
        );

        $ldap_project_group_manager->synchronize();
    }

    private function synchronizeStaticUgroupMembers()
    {
        $this->getLogger()->info('LDAP daily synchronisation: static ugroup members');

        $ldapUserGroupManager = $this->getLdapUserGroupManager();
        $ldapUserGroupManager->synchronizeUgroups();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function rootDailyStart(RootDailyStartEvent $event): void
    {
        if (! $this->isLdapAuthType()) {
            return;
        }

        $non_unique_uid_dao = new NonUniqueUidDAO();
        $non_unique_uids    = $non_unique_uid_dao->searchNonUniqueLdapUid();
        if (empty($non_unique_uids)) {
            return;
        }

        $message = 'The following ldap_uids are non unique:' . PHP_EOL;
        foreach ($non_unique_uids as $non_unique_uid => $non_unique_user_names) {
            $user_names = [];
            foreach ($non_unique_user_names as $non_unique_user_name) {
                $user_names[] = $non_unique_user_name['user_name'];
            }
            $message .= "\"$non_unique_uid\" (" . implode(', ', $user_names) . ")" . PHP_EOL;
        }

        $message .= 'This might lead to some SVN misbehaviours for concerned users';

        $event->addWarning($message);
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

    #[\Tuleap\Plugin\ListeningToEventName(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE)]
    public function systemEventGetTypesForDefaultQueue($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][] = 'PLUGIN_LDAP_UPDATE_LOGIN';
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::GET_SYSTEM_EVENT_CLASS)]
    public function getSystemEventClass($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        switch ($params['type']) {
            case 'PLUGIN_LDAP_UPDATE_LOGIN':
                include_once dirname(__FILE__) . '/system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php';
                $params['class']        = 'SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN';
                $params['dependencies'] = [
                    UserManager::instance(),
                    Backend::instance(Backend::SVN),
                    ProjectManager::instance(),
                    new LDAP_ProjectManager(),
                ];
                break;
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::GET_LDAP_LOGIN_NAME_FOR_USER)]
    public function getLdapLoginNameForUser($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $params['ldap_user'] = $this->getLdapUserManager()->getLDAPUserFromUser($params['user']);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('login_presenter')]
    public function loginPresenter($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isLdapAuthType()) {
            $params['authoritative'] = true;
            $params['presenter']     = new LDAP_LoginPresenter($params['presenter'], $this->getLDAPServerCommonName());
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function accountCreated(AccountCreated $account_created): void
    {
        (
            new AccountCreation(
                $this->getLogger(),
                $this->getLdapUserManager()
            )
        )->associateWithLDAPAccount($account_created);
    }


    #[\Tuleap\Plugin\ListeningToEventClass]
    public function gerritCanMigrateEvent(GerritCanMigrateEvent $event): void
    {
        $ldap_params = $this->getLDAPParams();

        $platform_uses_ldap_for_authentication = $this->isLdapAuthType();

        if ($platform_uses_ldap_for_authentication) {
            $event->platformCanUseGerrit();
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
        return ForgeConfig::get('sys_custompluginsroot') . 'ldap/etc/';
    }

    private function getConfigFilePath()
    {
        return $this->getEtcDir() . 'ldap.inc';
    }

    #[\Tuleap\Plugin\ListeningToEventName('ugroup_duplication')]
    public function ugroupDuplication(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
        $manager         = new LDAP_ProjectGroupManager(
            $this->getLdap(),
            $this->getLdapUserManager(),
            $this->getLdapProjectGroupDao(),
            $project_manager,
            $user_manager,
            $notifications_manager,
            new ProjectGroupManagerRestrictedUserFilter($user_manager),
            new \Tuleap\LDAP\Project\ProjectMemberAdder($this->getLdapProjectGroupDao()),
        );
        return $manager;
    }

    private function buildLdapProjectGroupManagerWithoutPermissionsCheckOnProjectMemberAdd(
        \Tuleap\LDAP\GroupSyncNotificationsManager $notifications_manager,
    ): LDAP_ProjectGroupManager {
        $user_manager    = UserManager::instance();
        $project_manager = ProjectManager::instance();
        return new LDAP_ProjectGroupManager(
            $this->getLdap(),
            $this->getLdapUserManager(),
            $this->getLdapProjectGroupDao(),
            $project_manager,
            $user_manager,
            $notifications_manager,
            new ProjectGroupManagerRestrictedUserFilter($user_manager),
            new \Tuleap\LDAP\Project\DailySyncProjectMemberAdder(
                $user_manager,
                ProjectMemberAdderWithStatusCheckAndNotifications::buildWithoutPermissionsChecks(),
            ),
        );
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

    private function getUserRemover(): UserRemover
    {
        return new UserRemover(
            ProjectManager::instance(),
            EventManager::instance(),
            new ArtifactTypeFactory(false),
            new UserRemoverDao(),
            UserManager::instance(),
            new ProjectHistoryDao(),
            new UGroupManager(),
            new UserPermissionsDao(),
        );
    }

    private function currentRequestIsForProjectUgroupAdmin()
    {
        return strpos($_SERVER['REQUEST_URI'], '/project/admin/editugroup') === 0;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function bindingAdditionalModalPresenterCollection(BindingAdditionalModalPresenterCollection $collection): void
    {
        $request = HTTPRequest::instance();
        $builder = new AdditionalModalPresenterBuilder($this->getLdapUserGroupManager(), $request, $this->getLDAPServerCommonName());
        $collection->addModal(
            $builder->build(
                $collection->getUgroup(),
                $this->getBindOption($request),
                $this->getSynchro($request),
                $collection->getCSRF()
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function ugroupEditProcessAction(UGroupEditProcessAction $event): void
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
                        dgettext('tuleap-ldap', 'User group no longer linked with the directory')
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function membersEditProcessAction(MembersEditProcessAction $event): void
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

    public function routeGetWelcome(): DispatchableWithRequest
    {
        return new \Tuleap\LDAP\WelcomeDisplayController($this->getLdapUserManager(), Codendi_HTMLPurifier::instance(), $this->getPluginPath());
    }

    public function routePostWelcome(): DispatchableWithRequest
    {
        return new \Tuleap\LDAP\WelcomeUpdateController(UserManager::instance(), new UserDao(), new Account_TimezonesCollection());
    }

    public function routeGetAutocomplete(): DispatchableWithRequest
    {
        return new \Tuleap\LDAP\GroupAutocompleteController($this->getLdap());
    }

    public function routeGetBindUgroupConfirm(): DispatchableWithRequest
    {
        return new \Tuleap\LDAP\BindUgroupConfirmController(new UGroupManager(), $this->getLdapUserGroupManager(), UserManager::instance(), UserHelper::instance());
    }

    public function routeGetBindMembersConfirm(): DispatchableWithRequest
    {
        return new \Tuleap\LDAP\BindMembersConfirmController($this->getLdapProjectGroupManager(), UserManager::instance(), UserHelper::instance(), new \Tuleap\Project\Admin\MembershipDelegationDao());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->get('/welcome', $this->getRouteHandler('routeGetWelcome'));
            $r->post('/welcome', $this->getRouteHandler('routePostWelcome'));

            $r->get('/autocomplete', $this->getRouteHandler('routeGetAutocomplete'));
            $r->get('/bind-ugroup-confirm', $this->getRouteHandler('routeGetBindUgroupConfirm'));
            $r->get('/bind-members-confirm', $this->getRouteHandler('routeGetBindMembersConfirm'));
        });
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function authenticationMeanName(AuthenticationMeanName $event): void
    {
        if ($this->isLdapAuthType()) {
            $event->setName($this->getLDAPServerCommonName());
        }
    }

    private function getLDAPServerCommonName(): string
    {
        $params = $this->getLDAPParams();
        if (! isset($params['server_common_name'])) {
            return 'LDAP';
        }
        return (string) $params['server_common_name'];
    }

    private function getUserManager(): UserManager
    {
        return UserManager::instance();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function configDumpEvent(ConfigDumpEvent $event): void
    {
        // load properties
        $this->getPluginInfo();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function addAdditionalInformation(UserGroupAdditionalInformationEvent $event): void
    {
        (
            new \Tuleap\LDAP\REST\LDAPUserGroupRepresentationInformationAdder(
                $this->getLdapProjectGroupManager(),
                $this->getLdapProjectGroupDao(),
                $this->getLdapUserGroupManager(),
                $this->getUserGroupDao()
            )
        )->addAdditionalUserGroupInformation($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function findUserByEmailEvent(FindUserByEmailEvent $event): void
    {
        (new CreateUserFromEmail($this->getLdap(), $this->getLdapUserManager(), $this->getLogger()))->process($event);
    }
}

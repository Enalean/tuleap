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

require_once 'autoload.php';

class LdapPlugin extends Plugin {
    /**
     * @type LDAP
     */
    private $ldapInstance;

    /**
     * @type LDAP_UserManager
     */
    private $_ldapUmInstance;

    function __construct($id) {
        parent::__construct($id);

        // Layout
        $this->_addHook('display_newaccount', 'forbidIfLdapAuth', false);
        
        // Search
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::SEARCH_TYPE);

        // Authentication
        $this->_addHook(Event::SESSION_BEFORE_LOGIN, 'authenticate', false);
        $this->_addHook(Event::SESSION_AFTER_LOGIN, 'allowCodendiLogin', false);

        // Login
        $this->addHook('login_presenter');
        $this->_addHook('display_lostpw_createaccount', 'forbidIfLdapAuth', false);
        $this->_addHook('account_redirect_after_login', 'account_redirect_after_login', false);

        // User finder
        $this->_addHook('user_manager_find_user', 'user_manager_find_user', false);
        $this->_addHook('user_manager_get_user_by_identifier', 'user_manager_get_user_by_identifier', false);

        // User Home
        $this->_addHook('user_home_pi_entry', 'personalInformationEntry', false);  
        $this->_addHook('user_home_pi_tail', 'personalInformationTail', false);

        // User account
        $this->_addHook('account_pi_entry', 'accountPiEntry', false);
        $this->_addHook('before_change_email-complete', 'cancelChangeAndUserLdap', false);
        $this->_addHook('before_change_email-confirm', 'cancelChangeAndUserLdap', false);
        $this->_addHook('before_change_email', 'cancelChangeAndUserLdap', false);
        $this->_addHook('before_change_pw', 'cancelChangeAndUserLdap', false);
        $this->_addHook('before_change_realname', 'cancelChangeAndUserLdap', false);
        $this->_addHook('before_lostpw-confirm', 'cancelChange', false);
        $this->_addHook('before_lostpw', 'cancelChange', false);
        $this->_addHook('display_change_password', 'forbidIfLdapAuthAndUserLdap', false);
        $this->_addHook('display_change_email', 'forbidIfLdapAuthAndUserLdap', false);
        // Comment if want to allow real name change in LDAP mode
        $this->_addHook('display_change_realname', 'forbidIfLdapAuthAndUserLdap', false);

        // Site Admin
        $this->_addHook('before_admin_change_pw', 'warnNoPwChange', false);
        $this->_addHook('usergroup_update_form', 'addLdapInput', false);
        $this->_addHook('usergroup_update', 'updateLdapID', false);

        // Project admin
        $this->_addHook('ugroup_table_row',                 'ugroup_table_row',            false);
        $this->_addHook('project_admin_add_user_form',      'project_admin_add_user_form', false);
        $this->_addHook(Event::UGROUP_UPDATE_USERS_ALLOWED, 'ugroup_update_users_allowed', false);

        // Svn intro
        $this->_addHook('svn_intro', 'svn_intro', false);
        $this->_addHook('svn_check_access_username', 'svn_check_access_username', false);

        $this->_addHook('before_register', 'redirectToLogin', false);

        // Search as you type user
        $this->_addHook('ajax_search_user', 'ajax_search_user', false);
        
        // Project creation
        $this->_addHook('register_project_creation', 'register_project_creation', false);
        
        // Backend SVN
        $this->_addHook('backend_factory_get_svn', 'backend_factory_get_svn', false);
        $this->_addHook(Event::SVN_APACHE_AUTH,    'svn_apache_auth',         false);

        // Daily codendi job
        $this->_addHook('codendi_daily_start', 'codendi_daily_start', false);
        
        // SystemEvent
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'get_system_event_class', false);

        // Ask for LDAP Username of a User
        $this->_addHook(Event::GET_LDAP_LOGIN_NAME_FOR_USER);
    }
    
    /**
     * @return LdapPluginInfo
     */
    function getPluginInfo() {
        if (! $this->pluginInfo instanceof LdapPluginInfo) {
            $this->pluginInfo = new LdapPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @return LDAP
     */
    function getLdap() {
        if (!isset($this->ldapInstance)) {
            $ldapParams = array();
            $keys = $this->getPluginInfo()->propertyDescriptors->getKeys()->iterator();
            foreach ($keys as $k) {
                $nk = str_replace('sys_ldap_', '', $k);
                $ldapParams[$nk] = $this->getPluginInfo()->getPropertyValueForName($k);
            }
            $this->ldapInstance = new LDAP($ldapParams);
        }
        return $this->ldapInstance;
    }

    /**
     * Wrapper
     *
     * @return LDAP_UserManager
     */
    public function getLdapUserManager() {
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
    function layout_search_entry($params) {
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
    function ajax_search_user($params) {
        if($this->isLDAPUserManagementEnabled() && !$params['codendiUserOnly']) {
            $params['pluginAnswered'] = true;

            $validEmail = isset($params['validEmail']) ? $params['validEmail'] : false;

            $ldap = $this->getLdap();
            $lri  = $ldap->searchUserAsYouType($params['searchToken'], $params['limit'], $validEmail);
            $sync = LDAP_UserSync::instance();
            foreach($lri as $lr) {
                if ($lr->exist() && $lr->valid()) {
                    $params['userList'][] = $sync->getCommonName($lr).' ('.$lr->getLogin().')';
                }
            }
            if($ldap->getErrno() == LDAP::ERR_SIZELIMIT) {
                $params['userList'][] = "<strong>...</strong>";
            }
        }
    }

    /**
     * @see Event::SEARCH_TYPE
     */
    public function search_type($params) {
        $query  = $params['query'];
        $result = $params['results'];

        if ($GLOBALS['sys_auth_type'] == 'ldap' && $query->getTypeOfSearch() == Search_SearchPeople::NAME) {
            $search = new LDAP_SearchPeople(UserManager::instance(), $this->getLdap());
            $presenter = $search->search($query, $query->getNumberOfResults(), $result);
            $result->setResultsHtml($this->getSearchTemplateRenderer()->renderToString($presenter->getTemplate(), $presenter));
        }
    }

    public function getSearchTemplateRenderer() {
        return TemplateRendererFactory::build()->getRenderer(
            array(
                dirname(__FILE__).'/../templates',
                Config::get('codendi_dir') .'/src/templates/search',
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
    function authenticate($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
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
                $logger = new BackendLogger();
                $logger->info("[LDAP] User ".$params['loginname']." failed to authenticate");
            }
        }
    }

    /** Hook
     * When redirection after login happens, check if user as already filled
     * his personal info or not. If it's not the case, it means that the
     * account was automatically created and user must complete his
     * registeration.
     */
    function account_redirect_after_login($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $ldapUserDao = new LDAP_UserDao(CodendiDataAccess::instance());
            if(!$ldapUserDao->alreadyLoggedInOnce(user_getid())) {
                $return_to_arg = "";
                if($params['request']->existAndNonEmpty('return_to')) {
                    $return_to_arg ='?return_to='.urlencode($params['request']->get('return_to'));
                    if (isset($pv) && $pv == 2) $return_to_arg .= '&pv='.$pv;
                } else {
                    if (isset($pv) && $pv == 2) $return_to_arg .= '?pv='.$pv;
                }
                $params['request']->set('return_to', '/plugins/ldap/welcome.php'.$return_to_arg);
            }
        }
    }

    /**
     * @params $params $params['user'] IN
     *                 $params['allow_codendi_login'] IN/OUT
     */
    function allowCodendiLogin($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {

            if ($params['user']->getLdapId() != null) {
                $params['allow_codendi_login'] = false;
                return;
            }

            $ldapUm = $this->getLdapUserManager();
            $lr = $ldapUm->getLdapFromUserId($params['user']->getId());
            if($lr) {
                $params['allow_codendi_login'] = false;
                $GLOBALS['feedback'] .= ' '.$GLOBALS['Language']->getText('plugin_ldap',
                                                                          'login_pls_use_ldap',
                                                                          array($GLOBALS['sys_name']));
            }
            else {
                $params['allow_codendi_login'] = true;
            }
        }
    }

    /**
     * Get a User object from an LDAP iterator
     * 
     * @param LDAPResultIterator $lri An LDAP result iterator
     * 
     * @return PFUser
     */
    protected function getUserFromLdapIterator($lri) {
        if($lri && count($lri) === 1) {
            $ldapUm = $this->getLdapUserManager();
            return $ldapUm->getUserFromLdap($lri->current()); 
        }
        return null;
    }

    /**
     * Hook
     * Params:
     *  IN  $params['ident']
     *  IN/OUT  $params['user'] User object if found or null.
     */
    function user_manager_find_user($params) {
        if ($this->isLDAPUserManagementEnabled()) {
            $ldap = $this->getLdap();
            // First, test if its provided by autocompleter: "Common Name (login name)"
            $matches = array();
            if(preg_match('/^(.*) \((.*)\)$/', $params['ident'], $matches)) {
                if(trim($matches[2]) != '') {
                    $lri  = $ldap->searchLogin($matches[2]);
                } else {
                    $lri  = $ldap->searchCommonName($matches[1]);
                }
            } else {
                // Otherwise, search as defined in config most of the time
                // (uid, email, common name)
                $lri  = $ldap->searchUser($params['ident']);
            }
            $params['user'] = $this->getUserFromLdapIterator($lri);
        }
    }

    /**
     * $params['identifier'] IN
     * $params['user'] OUT
     * $params['tokenFound'] OUT
     *
     * @param unknown_type $params
     */
    function user_manager_get_user_by_identifier($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap' && $this->isLDAPUserManagementEnabled()) {
            // identifier = type:value
            $separatorPosition = strpos($params['identifier'], ':');
            $type = strtolower(substr($params['identifier'], 0, $separatorPosition));
            $value = strtolower(substr($params['identifier'], $separatorPosition + 1));
            
            $ldap = $this->getLdap();
            $lri = null;
            switch ($type) {
                case 'ldapid':
                    $params['tokenFound'] = true;
                    $lri = $ldap->searchEdUid($value);
                    break;
                case 'ldapdn':
                    $params['tokenFound'] = true;
                    $lri = $ldap->searchDn($value);
                    break;
                case 'ldapuid':
                    $params['tokenFound'] = true;
                    $lri = $ldap->searchLogin($value);
                    break;
            }
            $params['user'] = $this->getUserFromLdapIterator($lri);
        }
    }

    /**
     * Hook
     * Params:
     *  IN  $params['user_id']
     *  OUT $params['entry_label']
     *  OUT $params['entry_value']
     */
    function personalInformationEntry($params) {
        if($GLOBALS['sys_auth_type'] == 'ldap') {
            $params['entry_label'][$this->getId()] = $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login');
            $ldapUm = $this->getLdapUserManager();
            $lr = $ldapUm->getLdapFromUserId($params['user_id']);
            if($lr) {
                $link = $this->buildLinkToDirectory($lr, $lr->getLogin());
                $params['entry_value'][$this->getId()] = $link;
            }
            else {
                $params['entry_value'][$this->getId()] = $GLOBALS['Language']->getText('plugin_ldap', 'no_ldap_login_found');
            }
        }
    }     

    /**
     * Hook
     * Params:
     *  IN  $params['user_id']
     *  OUT $params['entry_label']
     *  OUT $params['entry_value']
     *  OUT $params['entry_change']
     */
    function accountPiEntry($params) {
        if($GLOBALS['sys_auth_type'] == 'ldap') {
            $ldapUm = $this->getLdapUserManager();
            $lr = $ldapUm->getLdapFromUserId($params['user']->getId());
            if($lr) {
                $params['user_info'][] = new User_ImmutableInfoPresenter(
                    $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login'),
                    $lr->getLogin()
                );
            } else {
                $params['user_info'][] = new User_ImmutableInfoPresenter(
                    $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login'),
                    $GLOBALS['Language']->getText('plugin_ldap', 'no_ldap_login_found')
                );
            }
        }
    }

    /**
     * Hook
     */
    function personalInformationTail($params) {
        print '<TR>';
        $this->displayUserDetails($params['showdir']
                                  ,$params['user_name']);
        print '</TR>';
    }

    function buildLinkToDirectory(&$lr, $value='') {
        if($value === '') {
            $value = $lr->getLogin();
        }

        include_once($GLOBALS['Language']->getContent('directory_redirect', 'en_US', 'ldap'));
        if(function_exists('custom_build_link_to_directory')) {
            $link = custom_build_link_to_directory($lr, $value);
        }
        else {
            $link = $value;
        }
        return $link;
    }

    function displayUserDetails($showdir, $user_name) {
        include($GLOBALS['Language']->getContent('user_home', null, 'ldap'));

        if (!$showdir && $my_html_ldap_format) {
            echo '<td colspan="2" align="center"><a href="/users/'.$user_name.'/?showdir=1"><hr>[ '.$GLOBALS['Language']->getText('plugin_ldap','more_from_directory',$GLOBALS['sys_org_name']).'... ]</a><td>';

        } else {
            $ldapUm = $this->getLdapUserManager();
            $lr = $ldapUm->getLdapFromUserName($user_name);

            if (!$lr) {
                //$feedback = $GLOBALS['sys_org_name'].' '.$Language->getText('plugin_ldap','directory').': '.$ldap->getErrorMessage();
                echo '<td colspan="2" align="center"><hr><b>'.$feedback.'</b></td>';
            } else {
                // Format LDAP output based on templates given in user_home.php

                if ($my_html_ldap_format) {
                    preg_match_all("/%([\w\d\-\_]+)%/", $my_html_ldap_format, $matches);
                    foreach($matches[1] as $field) {
                        $value = $lr->get($field) ? $lr->get($field) : "-";
                        $my_html_ldap_format  = str_replace("%$field%", $value, $my_html_ldap_format);
                    }
                    echo $my_html_ldap_format;
                }
            }
        }
    }

    /**
     * Hook
     */
    function cancelChange($params) {
        if($GLOBALS['sys_auth_type'] == 'ldap') {
            exit_permission_denied();
        }
    }

    /**
     * Hook
     */
    function cancelChangeAndUserLdap($params) {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        if($GLOBALS['sys_auth_type'] == 'ldap' && $user->getLdapId() != '') {
            exit_permission_denied();
        }
    }
    

    function redirectToLogin($params) {
        if($GLOBALS['sys_auth_type'] == 'ldap') {
            if (isset($GLOBALS['sys_https_host']) && ($GLOBALS['sys_https_host'] != "")) {
                $host = 'https://'.$GLOBALS['sys_https_host'];
            } else {
                $host = 'http://'.$GLOBALS['sys_default_domain'];
            }
            util_return_to($host.'/account/login.php');
        }
    }

    function warnNoPwChange($params) {
        global $Language;
        if($GLOBALS['sys_auth_type'] == 'ldap') {
            // Won't change the LDAP password!
            echo "<p><b><span class=\"feedback\">".$Language->getText('admin_user_changepw','ldap_warning')."</span></b>";
        }
    }

    function addLdapInput($params) {
        global $Language;
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            echo $Language->getText('admin_usergroup','ldap_id').': <INPUT TYPE="TEXT" NAME="ldap_id" VALUE="'.$params['row_user']['ldap_id'].'" SIZE="35" MAXLENGTH="55">
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
    function updateLdapID($params) {
        global $Language;
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $request = HTTPRequest::instance();
            $ldapId = $request->getValidated('ldap_id', 'string', false);
            if($ldapId !== false) {
                $result = db_query("UPDATE user SET ldap_id='".db_es($ldapId)."' WHERE user_id=".db_ei($params['user_id']));
            }
            if (!$result) {
                $GLOBALS['feedback'] .= ' '.$Language->getText('admin_usergroup','error_upd_u');
                echo db_error();
            } else {
                $GLOBALS['feedback'] .= ' '.$Language->getText('admin_usergroup','success_upd_u');
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
    function forbidIfLdapAuth($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $params['allow']=false;
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
    function forbidIfLdapAuthAndUserLdap($params) {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        if ($GLOBALS['sys_auth_type'] == 'ldap' && $user->getLdapId() != '') {
            $params['allow']=false;
        }
    }

    /**
     * Replace the default svn message.
     *
     * $params['svn_intro_in_plugin'] OUT: set it to true if output sth here
     * $params['user_id']
     * $params['group_id']
     * $params['svn_url']
     */
    function svn_intro($params) {
        $svnProjectManager = new LDAP_ProjectManager();
        if($GLOBALS['sys_auth_type'] == 'ldap' && isset($params['group_id']) && $svnProjectManager->hasSVNLDAPAuth($params['group_id'])) {
            $svn_url = $params['svn_url'];
            $ldapUm = $this->getLdapUserManager();
            $lr = $ldapUm->getLdapFromUserId($params['user_id']);
            require($GLOBALS['Language']->getContent('svn_intro', null, 'ldap'));
            $params['svn_intro_in_plugin'] = true;
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
    function svn_check_access_username($params) {
        $svnProjectManager = new LDAP_ProjectManager();
        if($GLOBALS['sys_auth_type'] == 'ldap'
           && isset($params['project_svnroot'])
           && $svnProjectManager->hasSVNLDAPAuthByName(basename($params['project_svnroot']))) {
               $ldapUm = $this->getLdapUserManager();
               $lr     = $ldapUm->getLdapFromUserName($params['username']);
               if($lr !== false) {
                   // Must lower the username because LDAP is case insensitive
                   // while svn permission comparator is case sensitive and in
                   // backend the .SVNAccessFile is generated with lowercase
                   // usernames
                   $params['username'] = strtolower($lr->getLogin());
               }
        }
    }

    /**
     * Hook in upgroup edition
     * $params['row'] A row from ugroup table
     *
     * @param Array $params
     */
    function ugroup_table_row($params) {
        if($GLOBALS['sys_auth_type'] == 'ldap' && $this->isLDAPGroupsUsageEnabled()) {
            // No ldap for project 100
            if($params['row']['group_id'] != 100) {
                $hp = Codendi_HTMLPurifier::instance();
                $ldapUserGroupManager = new LDAP_UserGroupManager($this->getLdap());
                
                $baseUrl = $this->getPluginPath().'/ugroup_edit.php?ugroup_id='.$params['row']['ugroup_id'];

                $urlAdd = $this->getPluginPath().'/ugroup_add_user.php?ugroup_id='.$params['row']['ugroup_id'].'&func=add_user';
                $linkAdd = '<a href="'.$urlAdd.'">- '.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_users').'</a><br/>';
                if (!$ldapUserGroupManager->isMembersUpdateAllowed($params['row']['ugroup_id'])) {
                    $linkAdd = '';
                }

                $ldapGroup = $ldapUserGroupManager->getLdapGroupByGroupId($params['row']['ugroup_id']);
                if($ldapGroup !== null) {
                    $grpName = $hp->purify($ldapGroup->getCommonName());
                    $title = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_upd_binding', $grpName);
                } else {
                    $title = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_set_binding');
                }
                
                $urlBind = $this->getPluginPath().'/ugroup_edit.php?ugroup_id='.$params['row']['ugroup_id'].'&func=bind_with_group';
                $linkBind = '<a href="'.$urlBind.'">- '.$title.'</a>';
                
                $params['html'] .= '<br />'.$linkAdd.$linkBind;
            }
        }
    }

    /**
     * Display form elements to bind project members and an LDAP group
     *
     * @param array $params
     * 
     * @return void
     */
    function project_admin_add_user_form(array $params) {
        if ($this->isLDAPGroupsUsageEnabled()) {
            $projectMembersManager = new LDAP_ProjectGroupManager($this->getLdap());
            $ldapGroup = $projectMembersManager->getLdapGroupByGroupId($params['groupId']);
            if ($ldapGroup) {
                $groupName = $ldapGroup->getCommonName();
            } else {
                $groupName = '';
            }

            $html = '<hr />'.PHP_EOL;

            $html .= '<form method="post" class="link-with-ldap" action="'.$this->getPluginPath().'/admin.php?group_id='.$params['groupId'].'">'.PHP_EOL;
            $html .= '<div class="control-group">
                        <label class="control-label" for="add_user">'.$GLOBALS['Language']->getText('plugin_ldap', 'project_admin_add_ugroup').'</label>
                        <div class="controls">
                            <input type="text" value="'.$groupName.'" name="ldap_group" id="project_admin_add_ldap_group" size="60" />
                        </div>
                    </div>';
            $html .= '<label class="checkbox" for="preserve_members"><input type="checkbox" id="preserve_members" name="preserve_members" checked="checked" />'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_info').')</label>'.PHP_EOL;
            $html .= '<br />'.PHP_EOL;
            $html .= '<input type="submit" name="delete" value="'.$GLOBALS['Language']->getText('global', 'btn_delete').'" />'.PHP_EOL;
            $html .= '<input type="submit" name="check" value="'.$GLOBALS['Language']->getText('global', 'btn_update').'" />'.PHP_EOL;
            $html .= '</form>'.PHP_EOL;

            $GLOBALS['Response']->includeFooterJavascriptFile($this->getPluginPath().'/scripts/autocomplete.js');
            $js = "new LdapGroupAutoCompleter('project_admin_add_ldap_group',
                            '".$this->getPluginPath()."',
                            '".util_get_dir_image_theme()."',
                            'project_admin_add_ldap_group',
                            false);";
            $GLOBALS['Response']->includeFooterJavascriptSnippet($js);

            echo $html;
        }
    }

    /**
     * Check if adding or deleting users from the ugroup is allowed
     *
     * @param Array $params
     *
     * @return Void
     */
    function ugroup_update_users_allowed(array $params) {
        if ($params['ugroup_id']) {
            $ldapUserGroupManager = new LDAP_UserGroupManager($this->getLdap());
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
    function register_project_creation(array $params)
    {
        if($GLOBALS['sys_auth_type'] == 'ldap' && $this->getLdap()->getLDAPParam('svn_auth') == 1) {
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
    function backend_factory_get_svn(array $params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $params['base']  = 'LDAP_BackendSVN';
            $params['setup'] = array($this->getLdap());
        }
    }
    
    function svn_apache_auth($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $ldapProjectManager = new LDAP_ProjectManager();
            if ($ldapProjectManager->hasSVNLDAPAuth($params['project_info']['group_id'])) {
                $params['svn_apache_auth'] = new LDAP_SVN_Apache($this->getLdap(), $params['project_info']);
            }
        }
    }

    /**
     * Hook
     *
     * @param Array $params
     *
     * @return void
     */
    function codendi_daily_start($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap' && $this->isDailySyncEnabled()) {
            $ldapQuery = new LDAP_DirectorySynchronization($this->getLdap());
            $ldapQuery->syncAll();

            $retentionPeriod = $this->getLdap()->getLDAPParam('daily_sync_retention_period');
            if($retentionPeriod != NULL && $retentionPeriod!= "") {
                $ldapCleanUpManager = new LDAP_CleanUpManager($retentionPeriod);
                $ldapCleanUpManager->cleanAll();
            }

            //Synchronize the ugroups with the ldap ones
            $ldapUserGroupManager = new LDAP_UserGroupManager($this->getLdap());
            $ldapUserGroupManager->synchronizeUgroups();
            return true;
        }
    }

    /**
     * The daily synchro is enabled if the variable is not defined or if the variable is defined to 1
     * 
     * This is for backward compatibility (when daily_sync was not yet defined).
     * 
     * @return Boolean
     */
    protected function isDailySyncEnabled() {
        return $this->isParamEnabled('daily_sync');
    }
    
    protected function isLDAPUserManagementEnabled() {
        return $this->isParamEnabled('user_management');
    }
    
    protected function isLDAPGroupsUsageEnabled() {
        return $this->isParamEnabled('grp_enabled');
    }
    
    /**
     * Return true if the parameter is defined and enabled or not defined at all.
     * 
     * @param String $key
     * 
     * @return Boolean 
     */
    protected function isParamEnabled($key) {
        $value = $this->getLDAP()->getLDAPParam($key);
        if ($value === null || $value == 1) {
            return true;
        }
        return false;
    }
    
    public function system_event_get_types_for_default_queue($params) {
        $params['types'][] = 'PLUGIN_LDAP_UPDATE_LOGIN';
    }
    
    public function get_system_event_class($params) {
        switch($params['type']) {
            case 'PLUGIN_LDAP_UPDATE_LOGIN' :
                include_once dirname(__FILE__).'/system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php';
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

    public function get_ldap_login_name_for_user($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $params['ldap_user'] = $this->getLdapUserManager()->getLDAPUserFromUser($params['user']);
        }
    }

    public function login_presenter($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            include_once dirname(__FILE__).'/LoginPresenter.class.php';
            $params['authoritative'] = true;
            $params['presenter']     = new LDAP_LoginPresenter($params['presenter']);
        }
    }
}

?>

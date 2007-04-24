<?php

require_once('common/plugin/Plugin.class.php');
require_once('LDAP.class.php');
require_once('UserLdap.class.php');

class LdapPlugin extends Plugin {
	
	function LdapPlugin($id) {
		$this->Plugin($id);

        // Layout
        $this->_addHook('display_newaccount', 'forbidIfLdapAuth', false);

        // Search
        $this->_addHook('search_type_entry', 'ldapSearchEntry', false);
        $this->_addHook('search_type', 'ldapSearch', false);

        // Authentication
        $this->_addHook('session_before_login', 'authenticate', false);
        $this->_addHook('session_after_login', 'allowCodexLogin', false);

        // Login
        $this->_addHook('display_lostpw_createaccount', 'forbidIfLdapAuth', false);
        $this->_addHook('login_after_form', 'loginAfterForm', false);

        // User finder
        $this->_addHook('user_finder', 'userFinder', false);

        // User Home
        $this->_addHook('user_home_pi_entry', 'personalInformationEntry', false);  
        $this->_addHook('user_home_pi_tail', 'personalInformationTail', false);

        // User account
        $this->_addHook('account_pi_entry', 'accountPiEntry', false);
        $this->_addHook('before_change_email-complete', 'cancelChange', false);
        $this->_addHook('before_change_email-confirm', 'cancelChange', false);
        $this->_addHook('before_change_email', 'cancelChange', false);
        $this->_addHook('before_change_pw', 'cancelChange', false);
        $this->_addHook('before_change_realname', 'cancelChange', false);
        $this->_addHook('before_lostpw-confirm', 'cancelChange', false);
        $this->_addHook('before_lostpw', 'cancelChange', false);
        $this->_addHook('display_change_password', 'forbidIfLdapAuth', false);
        $this->_addHook('display_change_email', 'forbidIfLdapAuth', false);
        // Comment if want to allow real name change in LDAP mode
        $this->_addHook('display_change_realname', 'forbidIfLdapAuth', false);

        // Site Admin
        $this->_addHook('before_admin_change_pw', 'warnNoPwChange', false);
        $this->_addHook('usergroup_update_form', 'addLdapInput', false);
        $this->_addHook('usergroup_update', 'updateLdapID', false);



        $this->_addHook('before_register', 'redirectToLogin', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'LdapPluginInfo')) {
            require_once('LdapPluginInfo.class.php');
            $this->pluginInfo =& new LdapPluginInfo($this);
        }
        return $this->pluginInfo;
    }

	function CallHook($hook, $params) {
        // do nothing		
	}
    
    function ldapSearchEntry($params) {        
        // IN  $params['type_of_search']
        // OUT $params['output']

        $GLOBALS['Language']->loadLanguageMsg('ldap', 'ldap');
        $params['output'] .= "\t<OPTION value=\"people_ldap\"".( $params['type_of_search'] == "people_ldap" ? " SELECTED" : "" ).">".$GLOBALS['Language']->getText('plugin_ldap', 'people_ldap')."</OPTION>\n";
    }

    function ldapSearch($params) {
        // params
        // IN  $params['words']
        // IN  $params['offset'];
        // IN  $params['nbRows'];
        // IN  $params['type_of_search']
        // OUT $params['search_type']
        // OUT $params['rows_returned']
        // OUT $params['rows']
        global $Language;

        if($params['type_of_search'] === "people_ldap") {
            $params['search_type'] = true;
            
            $ldap =& LDAP::instance();
            $lri  =& $ldap->searchUser($params['words']);
            $rows = $rows_returned = $lri->count();
  
            if ($rows < 1) {
                $no_rows = 1;
                echo '<H2>'.$Language->getText('search_index','no_match_found',$params['words']).'</H2>';
            } else {

                if ( $rows_returned > $params['nbRows']) {
                    $rows = $params['nbRows'];
                }

                echo '<H3>'.$Language->getText('search_index','search_res', array($params['words'], $rows_returned))."</H3><P>\n\n";

                $title_arr = array();
                $title_arr[] = $Language->getText('search_index','real_n');
                $title_arr[] = $Language->getText('search_index','user_n');
                echo html_build_list_table_top ($title_arr);

                echo "\n";
	
                $lri->seek($params['offset']);
                $i = $params['nbRows'];
                while(($ldapres = $lri->iterate()) && $i > 0) {

                    print "<TR class=\"". html_get_alt_row_color($i) ."\">\n";

                    print "<TD>";
                    print $this->buildLinkToDirectory($ldapres, $ldapres->getCommonName());
                    print "</TD>\n";
		 
                    print "<TD>";

                    $dbres = UserLdap::getUserResultSet($ldapres->getEdUid());
                    if($dbres && db_numrows($dbres) === 1) {
                        $user_name = db_result($dbres, 0, 'user_name');
                        print '<a href="/users/'.$user_name.'">'
                            .'<img src="'.util_get_image_theme('msg.png').'" border="0" height="12" width="10" />&nbsp;'
                            .$user_name
                            .'</a>';
                    }
                    print "</TD>\n";				  

                    print "</TR>\n";

                    $i--;
                }
                echo "</TABLE>\n";
            }
            $params['rows'] = $rows;
            $params['rows_returned'] = $rows_returned;
        }
    }
       
    /**
     * @params $params $params['login']
     *                 $params['password']
     *                 $params['auth_success']
     *                 $params['auth_user_id']
     *                 $params['auth_user_status']
     */
    function authenticate($params) {       
        global $Language,$pv;

        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            
            $params['auth_success'] = false;
            
            $ldap =& LDAP::instance();
            
            // Perform LDAP authentication        
            if ($ldap->authenticate($params['loginname'], rtrim($params['passwd']))) {
                $lri =& $ldap->searchLogin($params['loginname']);
                if($lri->count() === 1) {
                    // Check if this user is a codex user or not. 
                    $lr = $lri->get(0);	    
                    $qry = "SELECT user_id,status"
                        . " FROM user"
                        . " WHERE ldap_id='".$lr->getEdUid()."'";
                    $res = db_query($qry);
                    if (!$res || db_numrows($res) < 1) {
                        // Authenticated user
                        // without codex account
                        // create account!                        
                        UserLdap::register($lr, 
                                           $params['passwd'], 
                                           $params['auth_user_id'], 
                                           $params['auth_user_status'],
                                           $params['auth_success']);
                        
                        // WARNING HACK: Here we are modifing a query argument
                        // (that should be read only I guess) but this is very
                        // practical to use the generic return_to mecanisme
                        // even for account creation.
                        $return_to_arg = '';;
                        if(array_key_exists('return_to', $_REQUEST) && $_REQUEST['return_to'] != '') {
                            $return_to_arg ='?return_to='.urlencode($_REQUEST['return_to']);
			    if (isset($pv) && $pv == 2) $return_to_arg .= '&pv='.$pv;
                        } else {
                            if (isset($pv) && $pv == 2) $return_to_arg .= '?pv='.$pv;
			}                        
                        $_REQUEST['return_to'] = '/plugins/ldap/welcome.php'.$return_to_arg;
                    }
                    else {
                        UserLdap::synchronizeUserWithLdap(db_result($res,0,'user_id'), $lr, $params['passwd']);

                        $params['auth_user_id']      = db_result($res,0,'user_id');
                        $params['auth_user_status']  = db_result($res,0,'status');
                        $params['auth_success']      = true;
                    }
                }	  
                else {
                    // @todo: transfert to plugin site-content
                    $GLOBALS['feedback'] .= $Language->getText('include_session','invalid_ldap_name') .'. ';
                }
            }
            else {
                // password is invalid or user does not exist

                // @todo: transfert to plugin site-content
                $GLOBALS['feedback'] .= $GLOBALS['sys_org_name'].' '.$Language->getText('include_session','dir_authent').': '.$ldap->getErrorMessage() .'. ';
            }
        }
    }
    
    
    /**
     * @params $params $params['user_id'] IN
     *                 $params['allow_codex_login'] IN/OUT
     */
    function allowCodexLogin($params) {
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            if(UserLdap::isLdapUser($params['user_id'])) {
                $params['allow_codex_login'] = false;
                $GLOBALS['Language']->loadLanguageMsg('ldap', 'ldap');
                $GLOBALS['feedback'] .= ' '.$GLOBALS['Language']->getText('plugin_ldap',
                                                                          'login_pls_use_ldap',
                                                                          array($GLOBALS['sys_name']));
            }
            else {
                $params['allow_codex_login'] = true;
            }
        }
    }

    /**
     * Hook
     */
    function loginAfterForm($params) {
        if($GLOBALS['sys_auth_type'] == 'ldap') {
            $GLOBALS['Language']->loadLanguageMsg('ldap', 'ldap');
            echo $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login_help', array($GLOBALS['sys_email_admin'], $GLOBALS['sys_name']));
        }
    }

    /**
     * Hook
     * Params:
     *  IN  $params['ident']
     *  OUT $params['best_codex_identifier']
     */
    function userFinder($params) {
        $ldap =& LDAP::instance();
        $lri  =& $ldap->searchUser($params['ident']);
        
        $bestCodexIdentifier = '';
        if(!$ldap->isError() && ($lri->count() == 1)) {	      
            $lr =& $lri->get(0);

            $res1 = UserLdap::getUserResultSet($lr->getEdUid());
            
            if(db_numrows($res1) === 1) {
                $bestCodexIdentifier = db_result($res1, 0, 'user_name');
            }
            else {
                $bestCodexIdentifier = $lr->getEmail();
            }
        }
        if($bestCodexIdentifier != '') {
            $params['best_codex_identifier'] = $bestCodexIdentifier;
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

            $lr =& UserLdap::getLdapResultSetFromUserId($params['user_id']);
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
            $GLOBALS['Language']->loadLanguageMsg('ldap', 'ldap');
            if(UserLdap::isLdapUser($params['user_id'])) {
                $lr =& UserLdap::getLdapResultSetFromUserId($params['user_id']);

                $params['entry_label'][$this->getId()] = $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login');
                $params['entry_value'][$this->getId()] = $lr->getLogin();
                $params['entry_change'][$this->getId()] = '';
            }
            else {
                $params['entry_label'][$this->getId()] = $GLOBALS['Language']->getText('plugin_ldap', 'ldap_login');
                $params['entry_value'][$this->getId()] = $GLOBALS['Language']->getText('plugin_ldap', 'no_ldap_login_found');
                $params['entry_change'][$this->getId()] = '';
            }
        }      
    }

    /**
     * Hook
     */
    function personalInformationTail($params) {
        global $Language;

        print '<TR>';        
        $this->displayUserDetails($params['showdir']
                                  ,$params['user_name']);
        print '</TR>';
    }



    function buildLinkToDirectory(&$lr, $value='') {
        global $Language;        

        if($value === '') {
            $value = $lr->getLogin();
        }

        include_once($Language->getContent('ldap/directory_redirect'));
        if(function_exists('custom_build_link_to_directory')) {
            $link = custom_build_link_to_directory($lr, $value);
        }
        else {
            $link = $value;
        }
        return $link;
    }

    function displayUserDetails($showdir, $user_name) {
        global $Language;

        if (!$showdir) {
            echo '<td colspan="2" align="center"><a href="/users/'.$user_name.'/?showdir=1"><hr>[ '.$Language->getText('plugin_ldap','more_from_directory',$GLOBALS['sys_org_name']).'... ]</a><td>';
            
        } else {
            $res_user = user_get_result_set_from_unix($user_name);

            include($Language->getContent('include/user_home'));
            
            if ($GLOBALS['sys_ldap_filter']) {
                $ldap_filter = $GLOBALS['sys_ldap_filter'];
            } else {
                $ldap_filter = "mail=%email%";
            }
            preg_match_all("/%([\w\d\-\_]+)%/", $ldap_filter, $match);
            while (list(,$v) = each($match[1])) {
                $ldap_filter = str_replace("%$v%", db_result($res_user,0,$v),$ldap_filter);
            }
            
            $ldap =& LDAP::instance();
            $info = $ldap->search($GLOBALS['sys_ldap_dn'],$ldap_filter);
            if (!$info) {
                $feedback = $GLOBALS['sys_org_name'].' '.$Language->getText('plugin_ldap','directory').': '.$ldap->getErrorMessage();
            } else {
                // Format LDAP output based on templates given in user_home.txt
                if ( $my_html_ldap_format ) {
                    preg_match_all("/%([\w\d\-\_]+)%/", $my_html_ldap_format, $match);
                    $html = $my_html_ldap_format;
                    while (list(,$v) = each($match[1])) {
                        $value = (isset($info[0][$v]) ? $info[0][$v][0] : "-");
                        $value = $info[0][$v][0];
                        $html = str_replace("%$v%", $value, $html);
                    }
                    print $html;
                } else {
                    // if no html template then produce a raw output
                    print '<td colspan="2" align="center"><hr><td>';
                    print '<tr valign="top"><td colspan="2">'.$Language->getText('plugin_ldap','total_entries').': '.$info["count"]."</td></tr>";
                    
                    for ($i=0; $i<$info["count"]; $i++) {
                        print '<tr valign="top"><td colspan="2"><b>'.$Language->getText('plugin_ldap','entry_#').' '.$i."</b></td></tr>";
                        print '<tr valign="top"><td>&nbsp;&nbsp;'.$Language->getText('plugin_ldap','entry_dn').' </td><td>'.$info[$i]["dn"]."</td></tr>";
                        print '<tr valign="top"><td>&nbsp;&nbsp;# '.$Language->getText('plugin_ldap','attributes').' </td><td>'.$info[$i]["count"]."</td></tr>";
                    
                        for ($j=0; $j<$info[$i]["count"]; $j++) {
                            $attrib_name = $info[$i][$j];
                            $nb_values = $info[$i][$attrib_name]["count"];
                            unset($info[$i][$attrib_name]["count"]);
                            print '<tr valign="top"><td>&nbsp;&nbsp;'.$attrib_name.'</td><td>'.join('<br>',$info[$i][$attrib_name])."</td></tr>";
                        }
                    }
                }
            }

            if ($feedback)
                echo '<td colspan="2" align="center"><hr><b>'.$feedback.'</b></td>';
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
            echo $Language->getText('admin_usergroup','ldap_id').': <INPUT TYPE="TEXT" NAME="ldap_id" VALUE="'.$row_user['ldap_id'].'" SIZE="35" MAXLENGTH="55">
<P>';
        }
    }

    function updateLdapID($params) {
        //$params['HTTP_POST_VARS']
        //$params['user_id']
        global $Language;
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $result=db_query("UPDATE user SET ldap_id='".$params['HTTP_POST_VARS']['ldap_id']."' WHERE user_id=".$params['user_id']);
            if (!$result) {
		$GLOBALS['feedback'] .= ' '.$Language->getText('admin_usergroup','error_upd_u');
                echo db_error();
            } else {
		$GLOBALS['feedback'] .= ' '.$Language->getText('admin_usergroup','success_upd_u');
            }
        }
    }


    function forbidIfLdapAuth($params) {
        //$params['allow']
        if ($GLOBALS['sys_auth_type'] == 'ldap') {
            $params['allow']=false;
        }
    }
}

?>

<?php

require_once('pre.php');
require_once('user.php');

require_once('LDAP.class.php');
require_once('LDAPResult.class.php');

class UserLdap {
   
    function &getLdapResultSet($ldapId) {
        $ldap =& LDAP::instance();
        $lri =& $ldap->searchEdUid($ldapId);
        if(!$ldap->isError() && $lri->count() === 1) {
            $lr =& $lri->get(0);
            return $lr;
        }
        else {
            return null;
        }
    }
    
    /**
     *
     * Should be transfered in user mgmt.
     *
     * Create a common set of user result sets,
     * so it doesn't have to be fetched each time
     */
    function getUserResultSet($ldapId) {        		
        global $USER_RES;
        
        $res = db_query("SELECT * FROM user WHERE ldap_id='".db_es($ldapId)."'");
        $user_id = db_result($res,0,'user_id');
        $USER_RES["_".$user_id."_"] = $res;
        return $USER_RES["_".$user_id."_"];
    }     

    function isLdapUser($user_id) {
        $sql = 'SELECT user_id'
            .' FROM user'
            .' WHERE user_id='.db_ei($user_id)
            .' AND ldap_id != ""'
            .' AND ldap_id IS NOT NULL';
        $res = db_query($sql);
        if(db_numrows($res) === 1) {
            return true;
        }
        return false;
    }

    /**
     * 
     */
    function &getLdapResultSetFromUserId($userId) {
        $res = user_get_result_set($userId);        
        return UserLdap::getLdapResultSet(db_result($res,0,'ldap_id'));
    }

    function synchronizeUserWithLdap($userId, &$lr, $password) {
        $GLOBALS['Language']->loadLanguageMsg('ldap', 'ldap');

        if($lr !== null) {
            // {{ This part should be tranfered in a dedicated code
            //    User class or function in user.php
            $qry1 = "UPDATE user SET user_pw='" . md5($password) . "'"
                . ", unix_pw='" . account_genunixpw($password) . "'"
                . ", windows_pw='" . account_genwinpw($password) . "'"
                . ", realname='".db_es($lr->getCommonName())."'"
                . ", email='".db_es($lr->getEmail())."'"
                . " WHERE user_id=" . $userId;
            $res1 = db_query($qry1);
            if ($res1 && db_affected_rows($res1) === 1) {
                return true;
            }
            else {
                $feedback .= $GLOBALS['Language']->getText('plugin_ldap', 'err_sync');
                return false;
            }
            // }}
        }
        else {
            $feedback .= $GLOBALS['Language']->getText('plugin_ldap', 'dir_user_notfound');
            return false;
        }
    }

    function register(&$lr, $password, &$user_id, &$user_status, &$success) {
        $form_loginname = $lr->getLogin();
        include($GLOBALS['Language']->getContent('ldap/register_ldap_get_data'));

        // Create codex account
        if($new_userid = account_create($form_loginname
                                        ,$password
                                        ,$lr->getEdUid()
                                        ,$lr->getCommonName()
                                        ,'LDAP'
                                        ,$lr->getEmail()
                                        ,'A'
                                        ,''
                                        ,1
                                        ,0
                                        ,'None'
                                        ,$GLOBALS['Language']->getText('conf','language_id')
                                        ,account_nextuid()
                                        ,'A')) {

            $user_id     = $new_userid;
            $user_status = 'A';
            $success     = true;                	  
        }
        else {
            $feedback .= $GLOBALS['Language']->getText('plugin_ldap', 'err_account_creation');
        }	          
    }

}


?>
<?php //-*-php-*-
rcs_id('$Id: PdoDb.php,v 1.2 2005/06/10 06:12:36 rurban Exp $');
/* Copyright (C) 2004, 2005 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

class _PdoDbPassUser
extends _DbPassUser
/**
 * PDO DB methods (PHP5)
 *   prepare, bind, execute.
 * We use numrical FETCH_MODE_ROW, so we don't need aliases in the auth_* SQL statements.
 *
 * @tables: user
 * @tables: pref
 */
{
    var $_authmethod = 'PDODb';

    function __construct($UserName='', $prefs=false) {

        if (!$this->_prefs and isa($this,"_PdoDbPassUser")) {
            if ($prefs) $this->_prefs = $prefs;
        }
        if (!isset($this->_prefs->_method))
            _PassUser::_PassUser($UserName);
        elseif (!$this->isValidName($UserName)) {
            trigger_error(_("Invalid username."), E_USER_WARNING);
            return false;
        }
        $this->_userid = $UserName;
        // make use of session data. generally we only initialize this every time, 
        // but do auth checks only once
        $this->_auth_crypt_method = $GLOBALS['request']->_dbi->getAuthParam('auth_crypt_method');
        return $this;
    }

    function getPreferences() {
        // override the generic slow method here for efficiency and not to 
        // clutter the homepage metadata with prefs.
        _AnonUser::getPreferences();
        $this->getAuthDbh();
        if (isset($this->_prefs->_select)) {
            $dbh =& $this->_auth_dbi;
            $db_result = $dbh->query(sprintf($this->_prefs->_select, $dbh->quote($this->_userid)));
            // patched by frederik@pandora.be
            $prefs = $db_result->fetch(PDO_FETCH_BOTH);
            $prefs_blob = @$prefs["prefs"]; 
            if ($restored_from_db = $this->_prefs->retrieve($prefs_blob)) {
                $updated = $this->_prefs->updatePrefs($restored_from_db);
                //$this->_prefs = new UserPreferences($restored_from_db);
                return $this->_prefs;
            }
        }
        if ($this->_HomePagehandle) {
            if ($restored_from_page = $this->_prefs->retrieve
                ($this->_HomePagehandle->get('pref'))) {
                $updated = $this->_prefs->updatePrefs($restored_from_page);
                //$this->_prefs = new UserPreferences($restored_from_page);
                return $this->_prefs;
            }
        }
        return $this->_prefs;
    }

    function setPreferences($prefs, $id_only=false) {
        // if the prefs are changed
        if ($count = _AnonUser::setPreferences($prefs, 1)) {
            $this->getAuthDbh();
            $packed = $this->_prefs->store();
            if (!$id_only and isset($this->_prefs->_update)) {
                $dbh =& $this->_auth_dbi;
                try {
                    $sth = $dbh->prepare($this->_prefs->_update);
                    $sth->bindParam("prefs", $packed);
                    $sth->bindParam("user",  $this->_userid);
                    $sth->execute();
                }
                catch (PDOException $e) {
                    trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                    return false;
                }
                //delete pageprefs:
                if ($this->_HomePagehandle and $this->_HomePagehandle->get('pref'))
                    $this->_HomePagehandle->set('pref', '');
            } else {
                //store prefs in homepage, not in cookie
                if ($this->_HomePagehandle and !$id_only)
                    $this->_HomePagehandle->set('pref', $packed);
            }
            return $count;
        }
        return 0;
    }

    function userExists() {
        $this->getAuthDbh();
        $dbh = &$this->_auth_dbi;
        if (!$dbh) { // needed?
            return $this->_tryNextUser();
        }
        if (!$this->isValidName()) {
            trigger_error(_("Invalid username."),E_USER_WARNING);
            return $this->_tryNextUser();
        }
        $dbi =& $GLOBALS['request']->_dbi;
        if ($dbi->getAuthParam('auth_check') and empty($this->_authselect)) {
            try {
                $this->_authselect = $dbh->prepare($dbi->getAuthParam('auth_check'));
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
        }
        //NOTE: for auth_crypt_method='crypt' no special auth_user_exists is needed
        if ( !$dbi->getAuthParam('auth_user_exists') 
             and $this->_auth_crypt_method == 'crypt'
             and $this->_authselect)
        {
            try {
                $this->_authselect->bindParam("userid",  $this->_userid, PDO_PARAM_STR, 48);
                $this->_authselect->execute();
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
            if ($this->_authselect->fetchSingle())
                return true;
        }
        else {
            if (! $dbi->getAuthParam('auth_user_exists'))
                trigger_error(fmt("%s is missing", 'DBAUTH_AUTH_USER_EXISTS'),
                              E_USER_WARNING);
            $this->_authcheck = $dbh->prepare($dbi->getAuthParam('auth_check'));
            $this->_authcheck->bindParam("userid", $this->_userid, PDO_PARAM_STR, 48);
            $this->_authcheck->execute();
            if ($this->_authcheck->fetchSingle())
                return true;
        }
        // User does not exist yet.
        // Maybe the user is allowed to create himself. Generally not wanted in 
        // external databases, but maybe wanted for the wiki database, for performance 
        // reasons
        if (empty($this->_authcreate) and $dbi->getAuthParam('auth_create')) {
            try {
                $this->_authcreate = $dbh->prepare($dbi->getAuthParam('auth_create'));
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
        }
        if (!empty($this->_authcreate) and 
            isset($_POST['auth']) and
            isset($_POST['auth']['passwd']))
        {
            $passwd = $_POST['auth']['passwd'];
            try {
                $this->_authcreate->bindParam("userid", $this->_userid, PDO_PARAM_STR, 48);
                $this->_authcreate->bindParam("password", $passwd, PDO_PARAM_STR, 48);
                $rs = $this->_authselect->execute();
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
            if ($rs)
                return true;
        }
        return $this->_tryNextUser();
    }
 
    function checkPass($submitted_password) {
        //global $DBAuthParams;
        $this->getAuthDbh();
        if (!$this->_auth_dbi) {  // needed?
            return $this->_tryNextPass($submitted_password);
        }
        if (!$this->isValidName()) {
            return $this->_tryNextPass($submitted_password);
        }
        if (!$this->_checkPassLength($submitted_password)) {
            return WIKIAUTH_FORBIDDEN;
        }
        if (!isset($this->_authselect))
            $this->userExists();
        if (!isset($this->_authselect))
            trigger_error(fmt("Either %s is missing or DATABASE_TYPE != '%s'",
                              'DBAUTH_AUTH_CHECK', 'SQL'),
                          E_USER_WARNING);

        //NOTE: for auth_crypt_method='crypt'  defined('ENCRYPTED_PASSWD',true) must be set
        $dbh = &$this->_auth_dbi;
        if ($this->_auth_crypt_method == 'crypt') {
            try {
                $this->_authselect->bindParam("userid", $this->_userid, PDO_PARAM_STR, 48);
                $this->_authselect->execute();
                $rs = $this->_authselect->fetch(PDO_FETCH_BOTH);
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
            $stored_password = @$rs[0];
            $result = $this->_checkPass($submitted_password, $stored_password);
        } else {
            try {
                $this->_authselect->bindParam("password", $submitted_password, PDO_PARAM_STR, 48);
                $this->_authselect->bindParam("userid", $this->_userid, PDO_PARAM_STR, 48);
                $this->_authselect->execute();
                $rs = $this->_authselect->fetch(PDO_FETCH_BOTH);
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
            $okay = @$rs[0];
            $result = !empty($okay);
        }

        if ($result) {
            $this->_level = WIKIAUTH_USER;
            return $this->_level;
        } elseif (USER_AUTH_POLICY === 'strict') {
            $this->_level = WIKIAUTH_FORBIDDEN;
            return $this->_level;
        } else {
            return $this->_tryNextPass($submitted_password);
        }
    }

    function mayChangePass() {
        return $GLOBALS['request']->_dbi->getAuthParam('auth_update');
    }

    function storePass($submitted_password) {
        if (!$this->isValidName()) {
            return false;
        }
        $this->getAuthDbh();
        $dbh = &$this->_auth_dbi;
        $dbi =& $GLOBALS['request']->_dbi;
        if ($dbi->getAuthParam('auth_update') and empty($this->_authupdate)) {
            try {
                $this->_authupdate = $dbh->prepare($dbi->getAuthParam('auth_update'));
            }
            catch (PDOException $e) {
                trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
                return false;
            }
        }
        if (empty($this->_authupdate)) {
            trigger_error(fmt("Either %s is missing or DATABASE_TYPE != '%s'",
                              'DBAUTH_AUTH_UPDATE','SQL'),
                          E_USER_WARNING);
            return false;
        }

        if ($this->_auth_crypt_method == 'crypt') {
            if (function_exists('crypt'))
                $submitted_password = crypt($submitted_password);
        }
        try {
            $this->_authupdate->bindParam("password", $submitted_password, PDO_PARAM_STR, 48);
            $this->_authupdate->bindParam("userid", $this->_userid, PDO_PARAM_STR, 48);
            $this->_authupdate->execute();
        }
        catch (PDOException $e) {
            trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
            return false;
        }
        return true;
    }
}

// $Log: PdoDb.php,v $
// Revision 1.2  2005/06/10 06:12:36  rurban
// finish missing db calls
//
// Revision 1.1  2005/05/06 16:56:48  rurban
// add PdoDbPassUser
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
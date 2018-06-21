<?php //-*-php-*-
rcs_id('$Id: Db.php,v 1.3 2005/06/10 06:11:56 rurban Exp $');
/* Copyright (C) 2004 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

/**
 * Baseclass for PearDB and ADODB PassUser's
 * Authenticate against a database, to be able to use shared users.
 *   internal: no different $DbAuthParams['dsn'] defined, or
 *   external: different $DbAuthParams['dsn']
 * The magic is done in the symbolic SQL statements in config/config.ini, similar to
 * libnss-mysql.
 *
 * We support only the SQL and ADODB backends.
 * The other WikiDB backends (flat, cvs, dba, ...) should be used for pages, 
 * not for auth stuff. If one would like to use e.g. dba for auth, he should 
 * use PearDB (SQL) with the right $DBAuthParam['auth_dsn']. 
 * (Not supported yet, since we require SQL. SQLite would make since when 
 * it will come to PHP)
 *
 * @tables: user, pref
 *
 * Preferences are handled in the parent class _PassUser, because the 
 * previous classes may also use DB pref_select and pref_update.
 *
 * Flat files auth is handled by the auth method "File".
 */
class _DbPassUser
extends _PassUser
{
    var $_authselect, $_authupdate, $_authcreate;

    // This can only be called from _PassUser, because the parent class 
    // sets the auth_dbi and pref methods, before this class is initialized.
    function __construct($UserName='',$prefs=false) {
        if (!$this->_prefs) {
            if ($prefs) $this->_prefs = $prefs;
        }
        if (!isset($this->_prefs->_method))
           parent::__construct($UserName);
        elseif (!$this->isValidName($UserName)) {
            trigger_error(_("Invalid username."),E_USER_WARNING);
            return false;
        }
        $this->_authmethod = 'Db';
        //$this->getAuthDbh();
        //$this->_auth_crypt_method = @$GLOBALS['DBAuthParams']['auth_crypt_method'];
        $dbi =& $GLOBALS['request']->_dbi;
        $dbtype = $dbi->getParam('dbtype');
        if ($dbtype == 'ADODB') {
            include_once("lib/WikiUser/AdoDb.php");
            if (check_php_version(5))
                return new _AdoDbPassUser($UserName,$this->_prefs);
            else {
                $user = new _AdoDbPassUser($UserName,$this->_prefs);
                eval("\$this = \$user;");
                return $user;
            }
        }
        elseif ($dbtype == 'SQL') {
            include_once("lib/WikiUser/PearDb.php");
            if (check_php_version(5))
                return new _PearDbPassUser($UserName,$this->_prefs);
            else {
                $user = new _PearDbPassUser($UserName,$this->_prefs);
                eval("\$this = \$user;");
                return $user;
            }
        }
        elseif ($dbtype == 'PDO') {
            include_once("lib/WikiUser/PdoDb.php");
            if (check_php_version(5))
                return new _PdoDbPassUser($UserName,$this->_prefs);
            else {
                $user = new _PdoDbPassUser($UserName,$this->_prefs);
                eval("\$this = \$user;");
                return $user;
            }
        }
        return false;
    }

    /* Since we properly quote the username, we allow most chars here. 
       Just " ; and ' is forbidden, max length: 48 as defined in the schema.
    */
    function isValidName ($userid = false) {
        if (!$userid) $userid = $this->_userid;
        if (strcspn($userid, ";'\"") != strlen($userid)) return false;
        if (strlen($userid) > 48) return false;
        return true;
    }

    function mayChangePass() {
        return !isset($this->_authupdate);
    }

}

// $Log: Db.php,v $
// Revision 1.3  2005/06/10 06:11:56  rurban
// special validname method
//
// Revision 1.2  2004/12/26 17:11:15  rurban
// just copyright
//
// Revision 1.1  2004/11/01 10:43:58  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
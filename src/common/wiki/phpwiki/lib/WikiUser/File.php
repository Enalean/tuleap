<?php //-*-php-*-
rcs_id('$Id: File.php,v 1.8 2006/03/07 18:03:51 rurban Exp $');
/* Copyright (C) 2004 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

class _FilePassUser
extends _PassUser
/**
 * Check users defined in a .htaccess style file
 * username:crypt\n...
 *
 * Preferences are handled in _PassUser
 */
{
    var $_file, $_may_change;

    // This can only be called from _PassUser, because the parent class 
    // sets the pref methods, before this class is initialized.
    function __construct($UserName='', $prefs=false, $file='') {
        if (!$this->_prefs and isa($this, "_FilePassUser")) {
            if ($prefs) $this->_prefs = $prefs;
            if (!isset($this->_prefs->_method))
              parent::__construct($UserName);
        }
        $this->_userid = $UserName;
        // read the .htaccess style file. We use our own copy of the standard pear class.
        $this->_may_change = defined('AUTH_USER_FILE_STORABLE') && AUTH_USER_FILE_STORABLE;
        if (empty($file) and defined('AUTH_USER_FILE'))
            $file = AUTH_USER_FILE;
        // same style as in main.php
        include_once(dirname(__FILE__)."/../pear/File_Passwd.php"); 
        // "__PHP_Incomplete_Class"
        if (!empty($file) or empty($this->_file) or !isa($this->_file, "File_Passwd"))
            $this->_file = new File_Passwd($file, false, $file.'.lock');
        else
            return false;
        return $this;
    }
 
    function mayChangePass() {
        return $this->_may_change;
    }

    function userExists() {
        if (!$this->isValidName()) {
            return $this->_tryNextUser();
        }
        $this->_authmethod = 'File';
        if (isset($this->_file->users[$this->_userid]))
            return true;
            
        return $this->_tryNextUser();
    }

    function checkPass($submitted_password) {
        if (!$this->isValidName()) {
            trigger_error(_("Invalid username."),E_USER_WARNING);
            return $this->_tryNextPass($submitted_password);
        }
        if (!$this->_checkPassLength($submitted_password)) {
            return WIKIAUTH_FORBIDDEN;
        }
        //include_once 'lib/pear/File_Passwd.php';
        if ($this->_file->verifyPassword($this->_userid, $submitted_password)) {
            $this->_authmethod = 'File';
            $this->_level = WIKIAUTH_USER;
            if ($this->isAdmin()) // member of the Administrators group
                $this->_level = WIKIAUTH_ADMIN;
            return $this->_level;
        }
        
        return $this->_tryNextPass($submitted_password);
    }

    function storePass($submitted_password) {
        if (!$this->isValidName()) {
            return false;
        }
        if ($this->_may_change) {
            $this->_file = new File_Passwd($this->_file->filename, true, 
                                           $this->_file->filename.'.lock');
            $result = $this->_file->modUser($this->_userid, $submitted_password);
            $this->_file->close();
            $this->_file = new File_Passwd($this->_file->filename, false);
            return $result;
        }
        return false;
    }

}

// $Log: File.php,v $
// Revision 1.8  2006/03/07 18:03:51  rurban
// fix storePass
//
// Revision 1.7  2005/08/06 13:21:37  rurban
// only cosmetics
//
// Revision 1.6  2005/05/04 05:37:34  rurban
// fix #1191096 by Karel
//
// Revision 1.5  2005/02/14 12:28:27  rurban
// fix policy strict. Thanks to Mikhail Vladimirov
//
// Revision 1.4  2004/12/26 17:11:16  rurban
// just copyright
//
// Revision 1.3  2004/12/20 16:05:01  rurban
// gettext msg unification
//
// Revision 1.2  2004/12/19 00:58:02  rurban
// Enforce PASSWORD_LENGTH_MINIMUM in almost all PassUser checks,
// Provide an errormessage if so. Just PersonalPage and BogoLogin not.
// Simplify httpauth logout handling and set sessions for all methods.
// fix main.php unknown index "x" getLevelDescription() warning.
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
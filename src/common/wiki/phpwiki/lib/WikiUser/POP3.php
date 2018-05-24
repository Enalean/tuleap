<?php //-*-php-*-
rcs_id('$Id: POP3.php,v 1.6 2005/04/23 11:17:41 rurban Exp $');
/* Copyright (C) 2004 $ThePhpWikiProgrammingTeam
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

require_once("lib/WikiUser/IMAP.php");

class _POP3PassUser
extends _IMAPPassUser {
/**
 * Define the var POP3_AUTH_HOST in config/config.ini
 * Preferences are handled in _PassUser
 */
    function checkPass($submitted_password) {
        if (!$this->isValidName()) {
            trigger_error(_("Invalid username."), E_USER_WARNING);
            return $this->_tryNextPass($submitted_password);
        }
        if (!$this->_checkPassLength($submitted_password)) {
            return WIKIAUTH_FORBIDDEN;
        }
        $userid = $this->_userid;
        $pass = $submitted_password;
        $host = defined('POP3_AUTH_HOST') ? POP3_AUTH_HOST : 'localhost:110';
        if (defined('POP3_AUTH_PORT'))
            $port = POP3_AUTH_PORT;
        elseif (strstr($host,':')) {
            list(,$port) = preg_split('/:/D',$host);
        } else {
            $port = 110;
        }
        $retval = false;
        $fp = fsockopen($host, $port, $errno, $errstr, 10);
        if ($fp) {
            // Get welcome string
            $line = fgets($fp, 1024);
            if (! strncmp("+OK", $line, 3)) {
                // Send user name
                fputs($fp, "user $userid\n");
                // Get response
                $line = fgets($fp, 1024);
                if (! strncmp("+OK", $line, 3)) {
                    // Send password
                    fputs($fp, "pass $pass\n");
                    // Get response
                    $line = fgets($fp, 1024);
                    if (! strncmp("+OK", $line, 3)) {
                        $retval = true;
                    }
                }
            }
            // quit the connection
            fputs($fp, "quit\n");
            // Get the sayonara message
            $line = fgets($fp, 1024);
            fclose($fp);
        } else {
            trigger_error(_("Couldn't connect to %s","POP3_AUTH_HOST ".$host.':'.$port),
                          E_USER_WARNING);
        }
        $this->_authmethod = 'POP3';
        if ($retval) {
            $this->_level = WIKIAUTH_USER;
        } else {
            $this->_level = WIKIAUTH_ANON;
        }
        return $this->_level;
    }
}

// $Log: POP3.php,v $
// Revision 1.6  2005/04/23 11:17:41  rurban
// bug #1186291
//
// Revision 1.5  2005/03/19 07:30:52  rurban
// fixed missing IMAP dependency. Thanks to sun-man
//
// Revision 1.4  2004/12/26 17:11:17  rurban
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
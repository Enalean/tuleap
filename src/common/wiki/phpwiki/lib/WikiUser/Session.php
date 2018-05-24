<?php //-*-php-*-
rcs_id('$Id: Session.php,v 1.3 2004/12/26 17:11:17 rurban Exp $');
/* Copyright (C) 2004 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

/** 
 * Support reuse of existing user session from another application.
 * You have to define which session variable holds the userid, and 
 * at what level is that user then. 1: BogoUser, 2: PassUser
 *   define('AUTH_SESS_USER','userid');
 *   define('AUTH_SESS_LEVEL',2);
 */
class _SessionPassUser
extends _PassUser
{
    function _SessionPassUser($UserName='',$prefs=false) {
        if ($prefs) $this->_prefs = $prefs;
        if (!defined("AUTH_SESS_USER") or !defined("AUTH_SESS_LEVEL")) {
            trigger_error(
                "AUTH_SESS_USER or AUTH_SESS_LEVEL is not defined for the SessionPassUser method",
                E_USER_ERROR);
            exit;
        }
        $sess =& $_SESSION;
        // user hash: "[user][userid]" or object "user->id"
        if (strstr(AUTH_SESS_USER,"][")) {
            $sess = $_SESSION;
            // recurse into hashes: "[user][userid]", sess = sess[user] => sess = sess[userid]
            foreach (split("][",AUTH_SESS_USER) as $v) {
                $v = str_replace(array("[","]"),'',$v);
                $sess = $sess[$v];
            }
            $this->_userid = $sess;
        } elseif (strstr(AUTH_SESS_USER,"->")) {
            // object "user->id" (no objects inside hashes supported!)
            list($obj,$key) = preg_split("/->/D",AUTH_SESS_USER);
            $this->_userid = $sess[$obj]->$key;
        } else {
            $this->_userid = $sess[AUTH_SESS_USER];
        }
        if (!isset($this->_prefs->_method))
           _PassUser::_PassUser($this->_userid);
        $this->_level = AUTH_SESS_LEVEL;
        $this->_authmethod = 'Session';
    }
    function userExists() {
        return !empty($this->_userid);
    }
    function checkPass($submitted_password) {
        return $this->userExists() and $this->_level > -1;
    }
    function mayChangePass() {
        return false;
    }
}

// $Log: Session.php,v $
// Revision 1.3  2004/12/26 17:11:17  rurban
// just copyright
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
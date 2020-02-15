<?php
//-*-php-*-
rcs_id('$Id: BogoLogin.php,v 1.6 2005/08/06 13:21:37 rurban Exp $');
/* Copyright (C) 2004 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

/** Without stored password. A _BogoLoginPassUser with password
 *  is automatically upgraded to a PersonalPagePassUser.
 */
class _BogoLoginPassUser extends _PassUser
{

    public $_authmethod = 'BogoLogin';

    public function userExists()
    {
        if (isWikiWord($this->_userid)) {
            $this->_level = WIKIAUTH_BOGO;
            return true;
        } else {
            $this->_level = WIKIAUTH_ANON;
            return false;
        }
    }

    /** A BogoLoginUser requires no password at all
     *  But if there's one stored, we override it with the PersonalPagePassUser instead
     */
    public function checkPass($submitted_password)
    {
        if ($this->_prefs->get('passwd')) {
            if (isset($this->_prefs->_method) and $this->_prefs->_method == 'HomePage') {
                $user = new _PersonalPagePassUser($this->_userid, $this->_prefs);
                if ($user->checkPass($submitted_password)) {
                    $user = UpgradeUser($this, $user);
                    $this->_level = WIKIAUTH_USER;
                    return $this->_level;
                } else {
                    $this->_level = WIKIAUTH_ANON;
                    return $this->_level;
                }
            } else {
                $stored_password = $this->_prefs->get('passwd');
                if ($this->_checkPass($submitted_password, $stored_password)) {
                    $this->_level = WIKIAUTH_USER;
                    return $this->_level;
                } elseif (USER_AUTH_POLICY === 'strict') {
                    $this->_level = WIKIAUTH_FORBIDDEN;
                    return $this->_level;
                } else {
                    return $this->_tryNextPass($submitted_password);
                }
            }
        }
        if (isWikiWord($this->_userid)) {
            $this->_level = WIKIAUTH_BOGO;
        } else {
            $this->_level = WIKIAUTH_ANON;
        }
        return $this->_level;
    }
}

// $Log: BogoLogin.php,v $
// Revision 1.6  2005/08/06 13:21:37  rurban
// only cosmetics
//
// Revision 1.5  2005/02/14 12:28:27  rurban
// fix policy strict. Thanks to Mikhail Vladimirov
//
// Revision 1.4  2004/12/26 17:11:15  rurban
// just copyright
//
// Revision 1.3  2004/11/06 03:07:03  rurban
// make use of dumped static config state in config/config.php (if writable)
//
// Revision 1.2  2004/11/05 20:53:36  rurban
// login cleanup: better debug msg on failing login,
// checked password less immediate login (bogo or anon),
// checked olduser pref session error,
// better PersonalPage without password warning on minimal password length=0
//   (which is default now)
//
// Revision 1.1  2004/11/01 10:43:58  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

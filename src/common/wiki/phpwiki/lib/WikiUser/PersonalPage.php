<?php //-*-php-*-
rcs_id('$Id: PersonalPage.php,v 1.7 2005/07/21 19:02:16 rurban Exp $');
/* Copyright (C) 2004 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

/**
 * This class is only to simplify the auth method dispatcher.
 * It inherits almost all all methods from _PassUser.
 */
class _PersonalPagePassUser
extends _PassUser
{
    var $_authmethod = 'PersonalPage';

    /* Very loose checking, since we properly quote the PageName. 
       Just trim spaces, ... See lib/stdlib.php
    */
    function isValidName ($userid = false) {
        if (!$userid) $userid = $this->_userid;
        $WikiPageName = new WikiPageName($userid);
        return $WikiPageName->isValid() and ($userid === $WikiPageName->name);
    }

    function userExists() {
        return $this->_HomePagehandle and $this->_HomePagehandle->exists();
    }

    /** A PersonalPagePassUser requires PASSWORD_LENGTH_MINIMUM.
     *  BUT if the user already has a homepage with an empty password 
     *  stored, allow login but warn him to change it.
     */
    function checkPass($submitted_password) {
        if ($this->userExists()) {
            $stored_password = $this->_prefs->get('passwd');
            if (empty($stored_password)) {
            	if (PASSWORD_LENGTH_MINIMUM > 0) {
                  trigger_error(sprintf(
                    _("PersonalPage login method:")."\n".
                    _("You stored an empty password in your '%s' page.")."\n".
                    _("Your access permissions are only for a BogoUser.")."\n".
                    _("Please set a password in UserPreferences."),
                                        $this->_userid), E_USER_WARNING);
                  $this->_level = WIKIAUTH_BOGO;
            	} else {
            	  if (!empty($submitted_password))
                    trigger_error(sprintf(
                      _("PersonalPage login method:")."\n".
                      _("You stored an empty password in your '%s' page.")."\n".
                      _("Given password ignored.")."\n".
                      _("Please set a password in UserPreferences."),
                                        $this->_userid), E_USER_WARNING);
                  $this->_level = WIKIAUTH_USER;
            	}
                return $this->_level;
            }
            if ($this->_checkPass($submitted_password, $stored_password))
                return ($this->_level = WIKIAUTH_USER);
            return _PassUser::checkPass($submitted_password);
        } else {
            return WIKIAUTH_ANON;
        }
    }
}

// $Log: PersonalPage.php,v $
// Revision 1.7  2005/07/21 19:02:16  rurban
// fix typo
//
// Revision 1.6  2005/06/22 05:36:52  rurban
// looser isValidName method
//
// Revision 1.5  2005/02/14 12:28:27  rurban
// fix policy strict. Thanks to Mikhail Vladimirov
//
// Revision 1.4  2004/12/26 17:11:17  rurban
// just copyright
//
// Revision 1.3  2004/11/05 22:09:39  rurban
// empty passwd PersonalPage case
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
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
<?php

require_once('common/user/User.class.php');
require_once('common/user/UserManager.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 * UserHelper
 */
class UserHelper {
    
    var $_username_display;
    
    /**
     * Constructor
     *
     */
    function UserHelper() {
        $this->_username_display = $this->_getCurrentUserUsernameDisplayPreference();
    }
    
    function _getCurrentUserUsernameDisplayPreference() {
        return user_get_preference("username_display");
    }
    function _getUserManager() {
        return UserManager::instance();
    }
    /**
     * getDisplayName
     * 
     * Get user name from Codex login, according to the user prefs: Codex login or Real name
     * 
     * Username display preference:
     *  1: user_name (realname)
     *  2: user_name
     *  3: realname
     *  4: realname (user_name)
     *
     * @param  user_name  string
     * @param  realname  string
     */
    function getDisplayName($user_name, $realname) {
        $name = '';
        switch($this->_username_display) {
        case 1:
            $name = "$user_name ($realname)";
            break;
        case 2:
            $name = $user_name;
            break;
        case 3:
            $name = $realname;
            break;
        default:
            $name = "$realname ($user_name)";
            break;
        }
        return $name;
    }
    
    /**
     * getDisplayNameFromUser
     * 
     * Get user name from Codex login, according to the user prefs: Codex login or Real name
     *
     * @param User the user to display
     * @see getDisplayName
     */
    function getDisplayNameFromUser(&$user) {
        if ($user->isNone()) {
            return $user->getUserName();
        }
        return $this->getDisplayName($user->getUserName(), $user->getRealName());
    }
    
    /**
     * getDisplayNameFromUserId
     * 
     * Get user name from Codex login, according to the user prefs: Codex login or Real name
     *
     * @param int the user_id of the user to display
     * @see getDisplayName
     */
    function getDisplayNameFromUserId($user_id) {
        $um =& $this->_getUserManager();
        $user =& $um->getUserById($user_id);
        return $this->getDisplayNameFromUser($user);
    }
    
    /**
     * getDisplayNameFromUserName
     * 
     * Get user name from Codex login, according to the user prefs: Codex login or Real name
     *
     * @param string the user_name of the user to display
     * @see getDisplayName
     */
    function getDisplayNameFromUserName($user_name) {
        if ($this->_isUserNameNone($user_name)) {
            return $user_name;
        } else {
            $um =& $this->_getUserManager();
            $user =& $um->getUserByUserName($user_name);
            return $this->getDisplayNameFromUser($user);
        }
    }
    
    /**
     * _isUserNameNone
     *
     * @param  user_name  
     */
    function _isUserNameNone($user_name) {
        return $user_name == $GLOBALS['Language']->getText('global', 'none');
    }
    
}

?>

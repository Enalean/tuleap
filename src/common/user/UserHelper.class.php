<?php
require_once('common/dao/UserDao.class.php');
require_once('common/user/User.class.php');
require_once('common/user/UserManager.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 * UserHelper
 */
class UserHelper {
    
    var $_username_display;
    var $_cache_by_id;
    var $_cache_by_username;
    var $_userdao;
    
    /**
     * Constructor
     *
     */
    function UserHelper() {
        $this->_username_display = $this->_getCurrentUserUsernameDisplayPreference();
        $this->_cache_by_id = array();
        $this->_cache_by_username = array();
        $this->_userdao = $this->_getuserDao();
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
        if ($um->isUserLoadedById($user_id)) {
            $user =& $um->getUserById($user_id);
            $display = $this->getDisplayNameFromUser($user);
        } else {
            if (!isset($this->_cache_by_id[$user_id])) {
                $dar = $this->_userdao->searchByUserId($user_id);
                if ($row = $dar->getRow()) {
                    $this->_cache_by_id[$user_id] = $this->getDisplayName($row['user_name'], $row['realname']);
                    $this->_cache_by_username[$row['user_name']] = $this->_cache_by_id[$user_id];
                }
            }
            $display = $this->_cache_by_id[$user_id];
        }
        return $display;
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
            if ($um->isUserLoadedByUserName($user_name)) {
                $user =& $um->getUserByUserName($user_name);
                $display = $this->getDisplayNameFromUser($user);
            } else {
                if (!isset($this->_cache_by_username[$user_name])) {
                    $dar = $this->_userdao->searchByUserName($user_name);
                    if ($row = $dar->getRow()) {
                        $this->_cache_by_id[$row['user_id']] = $this->getDisplayName($row['user_name'], $row['realname']);
                        $this->_cache_by_username[$row['user_name']] = $this->_cache_by_id[$row['user_id']];
                    }
                }
                $display = $this->_cache_by_username[$user_name];
            }
        }
        return $display;
    }
    
    /**
     * _isUserNameNone
     *
     * @param  user_name  
     */
    function _isUserNameNone($user_name) {
        return $user_name == $GLOBALS['Language']->getText('global', 'none');
    }
    
    /**
     * Returns the user dao
     */
    function _getUserDao() {
        $dao = new UserDao(CodeXDataAccess::instance());
        return $dao;
    }
}

?>

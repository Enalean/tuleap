<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/UserDao.class.php');
require_once('common/user/User.class.php');
require_once('common/user/UserManager.class.php');

/**
 * UserHelper
 */
class UserHelper {

    const PREFERENCES_NAME_AND_LOGIN = 0;
    const PREFERENCES_LOGIN_AND_NAME = 1;
    const PREFERENCES_LOGIN = 2;
    const PREFERENCES_REAL_NAME = 3;

    var $_username_display;
    var $_cache_by_id;
    var $_cache_by_username;
    var $_userdao;
    
    /**
     * Constructor
     * @todo make it protected (singleton powaaa)
     */
    public function __construct() {
        $this->_username_display = $this->_getCurrentUserUsernameDisplayPreference();
        $this->_cache_by_id = array();
        $this->_cache_by_username = array();
        $this->_userdao = $this->_getuserDao();
    }
    
    protected static $_instance;
    /**
     * 
     * @return UserHelper
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    function _getCurrentUserUsernameDisplayPreference() {
        return $this->_getUserManager()->getCurrentUser()->getPreference("username_display");
    }
    function _getUserManager() {
        return UserManager::instance();
    }
    /**
     * getDisplayName
     * 
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
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
        case self::PREFERENCES_LOGIN_AND_NAME:
            $name = "$user_name ($realname)";
            break;
        case self::PREFERENCES_LOGIN:
            $name = $user_name;
            break;
        case self::PREFERENCES_REAL_NAME:
            $name = $realname;
            break;
        default:
            $name = "$realname ($user_name)";
            break;
        }
        return $name;
    }
    
    /**
     * getDisplayNameSQLQuery
     * 
     * Get SQL statement for extracting display name from the "user" table, according to the user prefs
     * 
     * Username display preference: see getDisplayName()
     *
     */
    function getDisplayNameSQLQuery() {
        $name = '';
        switch($this->_username_display) {
        case self::PREFERENCES_LOGIN_AND_NAME:
            $name = "CONCAT(user.user_name,' (',user.realname,')') AS full_name";
            break;
        case self::PREFERENCES_LOGIN:
            $name = 'user.user_name AS full_name';
            break;
        case self::PREFERENCES_REAL_NAME:
            $name = 'user.realname AS full_name';
            break;
        default:
            $name = "CONCAT(user.realname,' (',user.user_name,')') AS full_name";
            break;
        }
        return $name;
    }

    /**
     * Get SQL statement for filtering according to users' names
     *
     * @param string $by a string containing comma-separated users' names or a pattern of user name.
     *
     * @return string
     */
    function getUserFilter($by) {
        $filter = '';
        $um = $this->_getUserManager();
        $usersIds = $um->getUserIdsList($by);
        if (count($usersIds) > 0) {
            $filter .= ' AND user.user_id IN ('.implode (',', $usersIds).')';
        } else {
            $filter .= ' AND user.user_name LIKE "%'.db_es($by).'%"';
        }
        return $filter;
    }

    /**
     * getDisplayNameSQLOrder
     * 
     * Get SQL statement for sorting display name from the "user" table, according to the user prefs
     * 
     * Username display preference: see getDisplayName()
     *
     */
    function getDisplayNameSQLOrder() {
        $order = '';
        switch($this->_username_display) {
        case self::PREFERENCES_LOGIN_AND_NAME:
            $order = "user.user_name";
            break;
        case self::PREFERENCES_LOGIN:
            $order = 'user.user_name';
            break;
        case self::PREFERENCES_REAL_NAME:
            $order = 'user.realname';
            break;
        default:
            $order = "user.realname";
            break;
        }
        return $order;
    }
    
    /**
     * getDisplayNameFromUser
     * 
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * @param PFUser the user to display
     *
     * @return the display name of the user $user or null if $user is null
     *
     * @see getDisplayName
     */
    function getDisplayNameFromUser($user) {
        if ($user == null) {
            return null;
        } elseif ($user->isNone()) {
            return $user->getUserName();
        } else {
            return $this->getDisplayName($user->getUserName(), $user->getRealName());
        }
    }
    
    /**
     * getDisplayNameFromUserId
     * 
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * @param int the user_id of the user to display
     * @see getDisplayName
     */
    function getDisplayNameFromUserId($user_id) {
        $um = $this->_getUserManager();
        if ($um->isUserLoadedById($user_id)) {
            $user = $um->getUserById($user_id);
            $display = $this->getDisplayNameFromUser($user);
        } else {
            if (!isset($this->_cache_by_id[$user_id])) {
                $this->_cache_by_id[$user_id] = $GLOBALS['Language']->getText('global', 'none');
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
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * @param string the user_name of the user to display
     * @see getDisplayName
     */
    function getDisplayNameFromUserName($user_name) {
        if ($this->_isUserNameNone($user_name)) {
            return $user_name;
        } else {
            $um = $this->_getUserManager();
            if ($um->isUserLoadedByUserName($user_name)) {
                $user = $um->getUserByUserName($user_name);
                $display = $this->getDisplayNameFromUser($user);
            } else {
                if (!isset($this->_cache_by_username[$user_name])) {
                    $dar = $this->_userdao->searchByUserName($user_name);
                    if ($row = $dar->getRow()) {
                        $this->_cache_by_id[$row['user_id']] = $this->getDisplayName($row['user_name'], $row['realname']);
                        $this->_cache_by_username[$row['user_name']] = $this->_cache_by_id[$row['user_id']];
                    } else {
                        $this->_cache_by_username[$user_name] = $user_name;
                    }
                }
                $display = $this->_cache_by_username[$user_name];
            }
        }
        return $display;
    }

    /**
     * Get a link on user profile with name according to user prefs.
     * 
     * @param Integer $user_id User id
     * 
     * @return String
     */
    public function getLinkOnUserFromUserId($user_id) {
        return $this->getLinkOnUser($this->_getUserManager()->getUserById($user_id));
    }
    
    /**
     * Get a link on user profile with name according to user prefs.
     *
     * @param PFUser $user User object
     *
     * @return String
     */
    public function getLinkOnUser(PFUser $user) {
        $hp = Codendi_HTMLPurifier::instance();
        if($user && !$user->isNone()) {
            return '<a href="/users/'.urlencode($user->getName()).'">'.$hp->purify($this->getDisplayNameFromUser($user), CODENDI_PURIFIER_CONVERT_HTML).'</a>';
        } else {
            $username = $user ? $user->getName() : '';
            return  $hp->purify($username, CODENDI_PURIFIER_CONVERT_HTML) ;
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
    
    /**
     * Returns the user dao
     */
    function _getUserDao() {
        $dao = new UserDao(CodendiDataAccess::instance());
        return $dao;
    }
}

?>
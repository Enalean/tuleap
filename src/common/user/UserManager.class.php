<?php

require_once('common/user/User.class.php');
require_once('common/dao/UserDao.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PermissionsManager
 */
class UserManager {
 
    var $_users;
    var $_userid_bynames;
    var $_userdao;
    var $_currentuser_id;
    
    function UserManager(&$userdao) {
        $this->_users = array();
        $this->_userid_bynames = array();
        $this->_userdao =& $userdao;
        $this->_currentuser_id = 0;
    }
    
    function &instance() {
        static $_usermanager_instance;
        if (!$_usermanager_instance) {
            $userdao = new UserDao(CodeXDataAccess::instance());
            $_usermanager_instance = new UserManager($userdao);
        }
        return $_usermanager_instance;
    }
    
    /**
     * @param int the user_id of the user to find
     * @return User or null if the user is not found
     */
    function &getUserById($user_id) {
        if (!isset($this->_users[$user_id])) {
            if ($user_id == 0) {
                $this->_users[$user_id] =& new User(0);
            } else {
                $dar =& $this->_userdao->searchByUserId($user_id);
                if ($row = $dar->getRow()) {
                    $u =& $this->_getUserInstanceFromRow($row);
                    $this->_users[$u->getId()] =& $u;
                    $this->_userid_bynames[$u->getUserName()] = $user_id;
                } else {
                    $this->_users[$user_id] = null;
                }
            }
        }
        return $this->_users[$user_id];
    }
    
    /**
     * @param string the user_name of the user to find
     * @return User or null if the user is not found
     */
    function &getUserByUserName($user_name) {
        if (!isset($this->_userid_bynames[$user_name])) {
            $dar =& $this->_userdao->searchByUserName($user_name);
            if ($row = $dar->getRow()) {
                $u =& $this->_getUserInstanceFromRow($row);
                $this->_users[$u->getId()] =& $u;
                $this->_userid_bynames[$user_name] = $u->getId();
            } else {
                $this->_userid_bynames[$user_name] = null;
            }
        }
        $user = null;
        if ($this->_userid_bynames[$user_name] !== null) {
            $user =& $this->_users[$this->_userid_bynames[$user_name]];
        }
        return $user;
    }
    
    function &_getUserInstanceFromRow($row) {
        $u =& new User($row['user_id']/*, $row */);
        return $u;
    }
    
    /**
     * @return User the user currently logged in (who made the request)
     */
    function &getCurrentUser() {
        return $this->getUserById(user_getid());
    }
    
    /**
     * Set the id of the user logged in
     */
    function setCurrentUserId($user_id) {
        $this->currentuser_id = $user_id;
    }
}

?>

<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PermissionsManager
 */
class UserManager {
 
    var $_users;
    
    function UserManager() {
        $this->_users = array();
    }
    
    function & instance() {
        static $_usermanager_instance;
        if (!$_usermanager_instance) {
            $_usermanager_instance = new UserManager();
        }
        return $_usermanager_instance;
    }
    
    function & getUserById($user_id) {
        if ($user_id == 0 && user_getid()) {
            $user_id = user_getid();
        }
        if (!isset($this->_users[$user_id])) {
            $this->_users[$user_id] =& new User($user_id);
        }
        return $this->_users[$user_id];
    }
    function & getCurrentUser() {
        return $this->getUserById(user_getid());
    }
}

?>

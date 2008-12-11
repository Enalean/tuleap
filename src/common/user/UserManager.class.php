<?php

require_once('common/user/User.class.php');
require_once('common/dao/UserDao.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * UserManager
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
    
    /**
     * Returns the user that have the given email address.
     * Returns null if no account is found.
     * Throws an exception if several accounts share the same email address.
     */
    public function getUserByEmail($email) {
        $user_result = $this->_userdao->searchByEmail($email);

        if ($user_result->rowCount() == 1) {
            return $this->_getUserInstanceFromRow($user_result->getRow());
        } else {
            if ($user_result->rowCount() > 1) {
                throw new Exception("Several accounts share the same email address '$email'");
            } else {
                return null; // No account found
            }
        }
    }
    
    /**
     * Returns a user that correspond to an identifier
     * The identifier can be prepended with a type.
     * Ex:
     *     ldapId:ed1234
     *     email:manu@st.com
     *     id:1234
     *     manu (no type specified means that the identifier is a username)
     */
    public function getUserByIdentifier($identifier) {
        $user = null;
        
        // Guess identifier type
        $separatorPosition = strpos($identifier, ':');
        if ($separatorPosition === false) {
            // identifier = username
            $user = $this->getUserByUserName($identifier);
        } else {
            // identifier = type:value
            $identifierType = substr($identifier, 0, $separatorPosition);
            $identifierValue = substr($identifier, $separatorPosition + 1);
            
            //TODO refactor using some LDAP plugin hook
            
            switch ($identifierType) {
                case 'id':
                    $user = $this->getUserById($identifierValue);
                    break;
                case 'email': // Use with caution, a same email can be shared between several accounts
                    $user = $this->getUserByEmail($identifierValue);
                    break;
                case 'ldapId':
                case 'ldapDn':
                case 'ldapRdn':
                case 'ldapUid':
                case 'ldapCn':    
                    break;
            }
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
     * isUserLoadedById
     *
     * @param int $user_id
     * @return boolean true if the user is already loaded
     */
    function isUserLoadedById($user_id) {
        return isset($this->_users[$user_id]);
    }
    
    /**
     * isUserLoadedByUserName
     *
     * @param string $user_name
     * @return boolean true if the user is already loaded
     */
    function isUserLoadedByUserName($user_name) {
        return isset($this->_userid_bynames[$user_name]);
    }
    
    
    /**
     * Set the id of the user logged in
     */
    function setCurrentUserId($user_id) {
        $this->currentuser_id = $user_id;
    }
}

?>

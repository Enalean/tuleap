<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/user/User.class.php');
require_once('common/dao/UserDao.class.php');

class UserManager {
 
    var $_users          = array();
    var $_userid_bynames = array();
    var $_userdao        = null;
    var $_currentuser    = null;
    
    protected function __construct() {
        $this->_users;
        $this->_userid_bynames;
        $this->_currentuser;
    }
    
    protected static $_instance;
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            $userdao = 
            self::$_instance = new $c($userdao);
        }
        return self::$_instance;
    }
    
    /**
     * @return UserDao
     */
    protected function getDao() {
        if (!$this->_userdao) {
          $this->_userdao = new UserDao(CodeXDataAccess::instance());
        }
        return $this->_userdao;
    }
    
    /**
     * @param int the user_id of the user to find
     * @return User or null if the user is not found
     */
    function getUserById($user_id) {
        if (!isset($this->_users[$user_id])) {
            if ($user_id == 0) {
                $this->_users[$user_id] = $this->_getUserInstanceFromRow(array('user_id' => 0));
            } else {
                $dar = $this->getDao()->searchByUserId($user_id);
                if ($row = $dar->getRow()) {
                    $u = $this->_getUserInstanceFromRow($row);
                    $this->_users[$u->getId()] = $u;
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
    function getUserByUserName($user_name) {
        if (!isset($this->_userid_bynames[$user_name])) {
            $dar = $this->getDao()->searchByUserName($user_name);
            if ($row = $dar->getRow()) {
                $u = $this->_getUserInstanceFromRow($row);
                $this->_users[$u->getId()] = $u;
                $this->_userid_bynames[$user_name] = $u->getId();
            } else {
                $this->_userid_bynames[$user_name] = null;
            }
        }
        $user = null;
        if ($this->_userid_bynames[$user_name] !== null) {
            $user = $this->_users[$this->_userid_bynames[$user_name]];
        }
        return $user;
    }
    
    function _getUserInstanceFromRow($row) {
        $u = new User($row);
        return $u;
    }
    
    /**
     * @param $session_hash string Optional parameter. If given, this will force 
     *                             the load of the user with the given session_hash. 
     *                             else it will check from the user cookies & ip
     * @return User the user currently logged in (who made the request)
     */
    function getCurrentUser($session_hash = false) {
        if (!isset($this->_currentuser) || $session_hash !== false) {
            $dar = null;
            if ($session_hash === false) {
                $session_hash = $this->_getCookieManager()->getCookie('session_hash');
            }
            if ($dar = $this->getDao()->searchBySessionHashAndIp($session_hash, $this->_getServerIp())) {
                if ($row = $dar->getRow()) {
                    $this->_currentuser = $this->_getUserInstanceFromRow($row);
                    $this->_currentuser->setSessionHash($session_hash);
                    $this->getDao()->storeLastAccessDate($this->_currentuser->getId(), time());
                }
            }
            if (!isset($this->_currentuser)) {
                //No valid session_hash/ip found. User is anonymous
                $this->_currentuser = $this->_getUserInstanceFromRow(array('user_id' => 0));
                $this->_currentuser->setSessionHash(false);
            }
            //cache the user
            $this->_users[$this->_currentuser->getId()] = $this->_currentuser;
            $this->_userid_bynames[$this->_currentuser->getUserName()] = $this->_currentuser->getId();
        }
        return $this->_currentuser;
    }
    
    /**
     * Logout the current user
     * - remove the cookie
     * - clear the session hash
     */
    function logout() {
        $user = $this->getCurrentUser();
        if ($user->getSessionHash()) {
            $this->getDao()->deleteSession($user->getSessionHash());
            $user->setSessionHash(false);
            $this->_getCookieManager()->removeCookie('session_hash');
        }
    }
    
    /**
     * Login the user
     * @param $name string The login name submitted by the user
     * @param $pwd string The password submitted by the user
     * @param $allowpending boolean True if pending users are allowed (for verify.php). Default is false
     * @return User Registered user or anonymous if the authentication failed
     */
    function login($name, $pwd, $allowpending = false) {
        $logged_in = false;
        $now = time();
        
        $auth_success     = false;
        $auth_user_id     = null;
        $auth_user_status = null;
        
        $params = array();
        $params['loginname']        = $name;
        $params['passwd']           = $pwd;
        $params['auth_success']     = $auth_success;
        $params['auth_user_id']     = $auth_user_id;
        $params['auth_user_status'] = $auth_user_status;
        $em = EventManager::instance();
        $em->processEvent('session_before_login', $params);
        
        //If nobody answer success, look for the user into the db
        if ($auth_success || ($dar = $this->getDao()->searchByUserName($name))) {
            if ($auth_success || ($row = $dar->getRow())) {
                if ($auth_success) {
                    $this->_currentuser = $this->getUserById($auth_user_id);
                } else {
                    $this->_currentuser = $this->_getUserInstanceFromRow($row);
                    if ($this->_currentuser->getUserPw() == md5($pwd)) {
                        //We have the good user, but check that he is allowed to connect
                        $auth_success = true;
                        $params = array('user_id'           => $this->_currentuser->getId(),
                                        'allow_codex_login' => &$auth_success);
                        $em->processEvent('session_after_login', $params);
                    }
                }
                if ($auth_success) {
                    $allowed = false;
                    //Check the status
                    $status  = $this->_currentuser->getStatus();
                    if (($status == 'A') || ($status == 'R') || 
                        ($allowpending && ($status == 'V' || $status == 'W' ||
                            ($GLOBALS['sys_user_approval']==0 && $status == 'P')))) {
                        $allowed =  true;
                    } else {
                        if ($status == 'S') { 
                            //acount suspended
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session','account_suspended'));
                            $allowed =  false;
                        }
                        if (($GLOBALS['sys_user_approval']==0 && ($status == 'P' || $status == 'V' || $status == 'W'))||
                            ($GLOBALS['sys_user_approval']==1 && ($status == 'V' || $status == 'W'))) { 
                            //account pending
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session','account_pending'));
                            $allowed =  false;
                        } 
                        if ($status == 'D') { 
                            //account deleted
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session','account_deleted'));
                            $allowed =  false;
                        }
                        if (($status != 'A')&&($status != 'R')) {
                            //unacceptable account flag
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session','account_not_active'));
                            $allowed =  false;
                        }
                    }
                    if ($allowed) {
                        //Check that password is not expired
                        if ($password_lifetime = $this->_getPasswordLifetime()) {
                            $expired = false;
                            $expiration_date = $now - 3600 * 24 * $password_lifetime;
                            $warning_date = $expiration_date + 3600 * 24 * 10; //Warns 10 days before
                            
                            if ($this->_currentuser->getLastPwdUpdate() < $expiration_date) {
                                $expired = true;
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session', 'expired_password'));
                            } else {
                                //warn the user that its password will expire
                                if ($this->_currentuser->getLastPwdUpdate() < $warning_date) {
                                    $GLOBALS['Response']->addFeedback(
                                        'warning', 
                                        $GLOBALS['Language']->getText(
                                            'include_session', 
                                            'password_will_expire', 
                                            ceil(($this->_currentuser->getLastPwdUpdate() - $expiration_date) / ( 3600 * 24 ))
                                        )
                                    );
                                }
                            }
                            //The password is expired. Redirect the user.
                            if ($expired) {
                                $GLOBALS['Response']->redirect('/account/change_pw.php?user_id='.$this->_currentuser->getId());
                            }
                        }
                        //Create the session
                        if ($session_hash = $this->getDao()->createSession($this->_currentuser->getId(), $now)) {
                            $logged_in = true;
                            $this->_currentuser->setSessionHash($session_hash);
                            
                            // If permanent login configured then cookie expires in one year from now
                            $expire = 0;
                            if ($this->_currentuser->getStickyLogin()) {
                                $expire = $now + $this->_getSessionLifetime();
                            }
                            $this->_getCookieManager()->setCookie('session_hash', $session_hash, $expire);
                            
                            // Populate response with details about login attempts.
                            //
                            // Always display the last succefull log-in. But if there was errors (number of
                            // bad attempts > 0) display the number of bad attempts and the last
                            // error. Moreover, in case of errors, messages are displayed as warning
                            // instead of info.
                            $level = 'info';
                            if($this->_currentuser->getNbAuthFailure() > 0) {
                                $level = 'warning';
                                $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_last_failure').' '.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->_currentuser->getLastAuthFailure()));
                                $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_nb_failure').' '.$this->_currentuser->getNbAuthFailure());
                            }
                            // Display nothing if no previous record.
                            if($this->_currentuser->getPreviousAuthSuccess() > 0) {
                                $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_prev_success').' '.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->_currentuser->getPreviousAuthSuccess()));
                            }
                        }
                    }
                } else {
                    //invalid password or user_name
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session','invalid_pwd'));
                    $this->getDao()->storeLoginFailure($name, $now);
                    //Add a delay when use login fail.
                    //The delay is 2 sec/nb of bad attempt.
                    sleep(2 * $this->_currentuser->getNbAuthFailure());
                }
            }
        }

        if (!$logged_in) {
            $this->_currentuser = $this->_getUserInstanceFromRow(array('user_id' => 0));
        }
        
        //cache the user
        $this->_users[$this->_currentuser->getId()] = $this->_currentuser;
        $this->_userid_bynames[$this->_currentuser->getUserName()] = $this->_currentuser->getId();
        return $this->_currentuser;
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
     * @return CookieManager
     */
    function _getCookieManager() {
        return new CookieManager();
    }
    
    /**
     * @return EventManager
     */
    function _getEventManager() {
        return EventManager::instance();
    }
    
    function _getServerIp() {
        if (isset($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        else return null;
    }
    
    function _getSessionLifetime() {
        return $GLOBALS['sys_session_lifetime'];
    }
    
    function _getPasswordLifetime() {
        return $GLOBALS['sys_password_lifetime'];
    }
}

?>

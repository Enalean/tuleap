<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2009.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'LDAP.class.php';
require_once 'LDAP_UserManager.class.php';

class LDAP_DirectorySynchronization {
    /**
     * @var LDAP
     */
    var $ldap;
    var $ldapTime;

    var $userToUpdate;

    function __construct(LDAP $ldap) {
        $this->ldapTime = 0;

        $this->chunkSize = 200;

        $this->userToUpdate   = array();
        
        $this->ldap = $ldap;
    }

    function iterateOnAllUsers() {
        $sql = 'SELECT u.user_id, user_name, email, ldap_id, status, realname, ldap_uid
        FROM user u
         JOIN plugin_ldap_user ldap_user ON (ldap_user.user_id = u.user_id)
        WHERE status IN ("A", "R")
        AND u.user_id > 101
        AND ldap_id IS NOT NULL
        AND ldap_id <> ""';

        $res = db_query($sql);
        if ($res && !db_error($res)) {
            $totalUsers = db_numrows($res);
            $iUsers=0;
            while($iUsers < $totalUsers) {
                $this->iterateOnChunk($res, $iUsers);
            }
        } else {
            echo "DB error: ".db_error($res).PHP_EOL;
        }
    }

    /**
     * Split user list in chunks
     *  
     * @param $res
     * @param $iUsers
     * @return unknown_type
     */
    function iterateOnChunk(&$res, &$iUsers) {
        $nbUsers=0;

        $ldap_query_param = array();
        $ldap_row_array = array();
        
        while(($nbUsers < $this->chunkSize) && ($row = db_fetch_array($res))) {
            $iUsers++;
            $nbUsers++;

            $ldap_query_param[] = '(st-eduid='.$row['ldap_id'].')';
            $ldap_row_array[$row['ldap_id']] = $row;            
        }
        
        $this->ldapSync($ldap_query_param, $ldap_row_array);
    }

    function ldapSync($ldap_query_param, $ldap_row_array) {
        $ldap_query = '(|'.implode("", $ldap_query_param).')';

        $time_start = microtime(true);
        $lri = $this->ldap->search($this->ldap->getLDAPParam('people_dn'), $ldap_query, LDAP::SCOPE_ONELEVEL, array('cn', 'mail', 'employeetype', 'st-eduid', 'o', 'uid'));
        $time_end   = microtime(true);
        $this->ldapTime += ($time_end-$time_start); 
        if ($this->ldap->getErrno() === LDAP::ERR_SUCCESS && $lri) {
            foreach ($lri as $lr) {
                $steduid = $lr->getEdUid();
                if (isset($ldap_row_array[$steduid])) {
                    $userRef = $ldap_row_array[$steduid];
                    $userId = $userRef['user_id'];

                    $userUpdate = array();
                    
                    // Check if name changed
                    if ($userRef['realname'] != substr($lr->getCommonName(), 0, 32)) {
                        $userUpdate['realname'] = $lr->getCommonName();
                    }
                    
                    if ($userRef['email'] != $lr->getEmail()) {
                        $userUpdate['email'] = $lr->getEmail();
                    }

                    if ($userRef['ldap_uid'] != $lr->getLogin()) {
                        $userUpdate['ldap_uid'] = $lr->getLogin();
                    }

                    // Allows site defined user update
                    include($GLOBALS['Language']->getContent('synchronize_user', 'en_US', 'ldap', '.php'));
                    
                    if (count($userUpdate) > 0) {
                        $this->userToUpdate[$userId] = $userUpdate;
                    }
                    
                    // Do not desactivate this user
                    unset($ldap_row_array[$steduid]);
                }
            }

            // Suspend users not found in LDAP
            foreach($ldap_row_array as $row) {
                $this->userToUpdate[$row['user_id']]['status'] = 'S';
                $this->userToUpdate[$row['user_id']]['unix_status'] = 'D'; 
            }
        }
    }

    function displayUpdateUser($id, $u) {
        $o = "Update user ($id):";
        foreach($u as $k => $v) {
            $o .= " $k => $v,";
        }
        $o = substr($o, 0, strlen($o) - 1);
        echo $o.PHP_EOL;
    }
    
    function syncUsers() {
        if(count($this->userToUpdate) > 0) {
            $um = $this->getUserManager();
            $lum = $this->getLdapUserManager();
            
            foreach($this->userToUpdate as $id => $u) {
                $userUpdated = false;
                
                // Update user attributes
                $updateUser = false;
                $user = $um->getUserById($id);
                if (isset($u['realname'])) {
                    $user->setRealName($u['realname']);
                    $updateUser = true;
                }
                if (isset($u['email'])) {
                    $user->setEmail($u['email']);
                    $updateUser = true;
                }
                if (isset($u['status'])) {
                    $user->setStatus($u['status']);
                    $updateUser = true;
                }
                if (isset($u['unix_status'])) {
                    $user->setUnixStatus($u['unix_status']);
                    $updateUser = true;
                }
                if ($updateUser) {
                    $userUpdated = $um->updateDb($user);
                }
                
                // Update user login
                if (isset($u['ldap_uid'])) {
                    $userUpdated = $lum->updateLdapUid($id, $u['ldap_uid']);
                }
                
                if ($userUpdated) {
                    $this->displayUpdateUser($id, $u);
                }
            }
        }
    }
            
    function updateUsers() {
        echo "=== User to update: (".count($this->userToUpdate).")\n";
        $this->syncUsers();
    }

    function getElapsedLdapTime() {
        return $this->ldapTime;
    }

    function getUserManager() {
        return UserManager::instance();
    }
    
    function getLdapUserManager() {
        return new LDAP_UserManager($this->ldap);
    }
}
?>
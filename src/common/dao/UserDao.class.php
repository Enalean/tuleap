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

require_once('include/DataAccessObject.class.php');


/**
 *  Data Access Object for User 
 */
class UserDao extends DataAccessObject {
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM user";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches User by Status (either one value or array)
    * @return DataAccessResult
    */
    function searchByStatus($status) {
        if (is_array($status)) {
            $where_status=$this->da->quoteSmartImplode(" OR status = ",$status);
        } else { $where_status = $this->da->quoteSmart($status); }
        $sql = "SELECT * FROM user WHERE status = $where_status";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches User by UserId 
    * @return DataAccessResult
    */
    function searchByUserId($userId) {
        $sql = sprintf("SELECT * FROM user WHERE user_id = %s",
            $this->da->quoteSmart($userId));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserName 
    * @return DataAccessResult
    */
    function searchByUserName($userName) {
        $sql = sprintf("SELECT * FROM user WHERE user_name = %s",
            $this->da->quoteSmart($userName));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Email 
    * @return DataAccessResult
    */
    function searchByEmail($email) {
        $sql = sprintf("SELECT * FROM user WHERE email = %s",
            $this->da->quoteSmart($email));
        return $this->retrieve($sql);
    }

    /**
     * Searches User by ldapid
     * @return DataAccessResult
     */
    function searchByLdapId($ldap_id) {
        $sql = sprintf("SELECT * FROM user WHERE ldap_id = %s",
            $this->da->quoteSmart($ldap_id));
        return $this->retrieve($sql);
    }
    
    public function searchSSHKeys() {
        $sql = "SELECT user_name, authorized_keys 
                FROM user 
                WHERE unix_status = 'A' 
                  AND (status= 'A' OR status='R') 
                  AND authorized_keys != '' 
                  AND authorized_keys IS NOT NULL";
        return $this->retrieve($sql);
    }

    /**
     * Search user by confirm hash
     * 
     * @param String $hash
     * 
     * @return DataAccessResult
     */
    public function searchByConfirmHash($hash) {
        $sql = 'SELECT * FROM user WHERE confirm_hash='.$this->da->quoteSmart($hash);
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table user 
    * @return true or id(auto_increment) if there is no error
    */
    function create($user_name, $email, $user_pw, $realname, $register_purpose, $status, $shell, $unix_pw, $unix_status, $unix_uid, $unix_box, $ldap_id, $add_date, $confirm_hash, $mail_siteupdates, $mail_va, $sticky_login, $authorized_keys, $email_new, $people_view_skills, $people_resume, $timezone, $fontsize, $theme, $language_id ,$expiry_date,$last_pwd_update) {
        $sql =' INSERT INTO user (user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, fontsize, theme, language_id ,expiry_date,last_pwd_update) VALUES ('
            .$this->da->quoteSmart($user_name).' , '.$this->da->quoteSmart($email).' , '.$this->da->quoteSmart(md5($user_pw)).' , '.$this->da->quoteSmart($realname).' , '.$this->da->quoteSmart($register_purpose).' , '
            .$this->da->quoteSmart($status).' , '.$this->da->quoteSmart($shell).' , '.$this->da->quoteSmart($this->_generateUnixPwd ($unix_pw)).' , '.$this->da->quoteSmart($unix_status).' , '
            .$this->da->quoteSmart($unix_uid).' , '.$this->da->quoteSmart($unix_box).' , '.$this->da->quoteSmart($ldap_id).' , '.$this->da->quoteSmart($add_date).' , '
            .$this->da->quoteSmart($confirm_hash).' , '.$this->da->quoteSmart($mail_siteupdates).' , '.$this->da->quoteSmart($mail_va).' , '.$this->da->quoteSmart($sticky_login).' , '
            .$this->da->quoteSmart($authorized_keys).' , '.$this->da->quoteSmart($email_new).' , '.$this->da->quoteSmart($people_view_skills).' , '
            .$this->da->quoteSmart($people_resume).' , '.$this->da->quoteSmart($timezone).' , '.$this->da->quoteSmart($fontsize).' , '
            .$this->da->quoteSmart($theme).' , '.$this->da->quoteSmart($language_id).' , '.$this->da->quoteSmart($expiry_date).' , '.$this->da->quoteSmart($last_pwd_update).')';
        
            $inserted = $this->update($sql);
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        } 
        return $inserted;
    }

    function updateByRow(array $user) {
        $stmt = array();
        if (isset($user['password'])) {
            $stmt[] = 'user_pw='.$this->da->quoteSmart(md5($user['password']));
            $stmt[] = 'unix_pw='.$this->da->quoteSmart($this->_generateUnixPwd($user['password']));
            //$stmt[] = 'windows_pw='.$this->da->quoteSmart(account_genwinpw($user['password']));
            $stmt[] = 'last_pwd_update='.$_SERVER['REQUEST_TIME'];
            unset($user['password']);
        }
        $dar = $this->searchByUserId($user['user_id']);
        if($dar && !$dar->isError()) {
            $current = $dar->current();
            foreach ($user as $field => $value) {
                if ($field != 'user_id' && $value != $current[$field]) {
                    $stmt[] = $field.' = '.$this->da->quoteSmart($value);
                }
            }
            if (count($stmt) > 0) {
                $sql = 'UPDATE user SET '.implode(', ', $stmt).' WHERE user_id = '.db_ei($user['user_id']);
                return $this->update($sql);
            }
        }
        return false;
    }

    /**
     * Generate a random number between 46 and 122
     * 
     * @return Integer
     */
    protected function _ranNum(){
        mt_srand((double)microtime()*1000000);
        $num = mt_rand(46,122);
        return $num;
    }

    /**
     * Generate a random alphanum character
     * 
     * @return String
     */
    protected function _genChr(){
        do {
            $num = $this->_ranNum();
        } while ( ( $num > 57 && $num < 65 ) || ( $num > 90 && $num < 97 ) );
        $char = chr($num);
        return $char;
    }

    /**
     * Random salt generator
     * 
     * @return String
     */ 
    protected function _genSalt(){
        $a = $this->_genChr();
        $b = $this->_genChr();
        // (LJ) Adding $1$ at the beginning of the salt
        // forces the MD5 encryption so the system has to
        // have MD5 pam module installed for Unix passwd file.
        $salt = "$1$" . "$a$b";
        return $salt;
    }

    /**
     * Generate Unix shadow password
     *
     * @param String $plainpw Clear password
     * 
     * @return String
     */
    protected function _generateUnixPwd($plainpw) {
        return crypt($plainpw, $this->_genSalt());
    }

    /**
     * Assign to given user the next available unix_uid
     * 
     * @param Integer $userId User ID
     * 
     * @return Boolean
     */
    function assignNextUnixUid($userId) {
        $sql = 'UPDATE user, (SELECT MAX(unix_uid)+1 AS max_uid FROM user) AS R'.
               ' SET unix_uid = max_uid'.
               ' WHERE user_id = '.$this->da->quoteSmart($userId);
        if ($this->update($sql)) {
            $sql = 'SELECT unix_uid FROM user WHERE user_id = '.$this->da->quoteSmart($userId);
            $dar = $this->retrieve($sql);
            if ($dar && !$dar->isError()) {
                $row = $dar->current();
                return $row['unix_uid'];
            }
        }
        return false;
    }
    
    /**
    * Searches User status by Email
    * @return DataAccessResult
    */
    function searchStatusByEmail($email) {
        //ST: with LDAP user_name can be an email
        $sql = sprintf("SELECT realname, email, status FROM user WHERE (user_name=%s OR email = %s)",
                $this->da->quoteSmart($email),
                $this->da->quoteSmart($email));
        return $this->retrieve($sql);
    }
    
    function searchBySessionHashAndIp($session_hash, $ip) {
        $sql = "SELECT user.*, session_hash, session.ip_addr AS session_ip_addr, session.time AS session_time
                FROM user INNER JOIN session USING (user_id)
                WHERE session_hash = ". $this->da->quoteSmart($session_hash) . " AND 
                      session.ip_addr LIKE ". $this->da->quoteSmart($ip);
        return $this->retrieve($sql);
    }

    /**
     * @return string the new session_hash
     */
    function createSession($user_id, $time) {
        
        // concatinate current time, and random seed for MD5 hash
        // continue until unique hash is generated (SHOULD only be once)
        do {
            $hash = md5( $time . rand() . $_SERVER['REMOTE_ADDR'] . microtime() );
            $sql = "SELECT 1
                    FROM session
                    WHERE session_hash = ". $this->da->quoteSmart($hash);
            $dar = $this->retrieve($sql);
        } while ($dar && $dar->rowCount() == 1);
        $sql = sprintf("INSERT INTO session (session_hash, ip_addr, time,user_id) VALUES (%s, %s, %d, %d)",
            $this->da->quoteSmart($hash),
            $this->da->quoteSmart($_SERVER['REMOTE_ADDR']),
            $time,
            $user_id
        );
        if ($this->update($sql)) {
            $this->storeLoginSuccess($user_id, $time);
        } else {
            $hash = false;
        }
        return $hash;
    }
    
    /** 
     * Store login success.
     * 
     * Store last log-on success timestamp in 'last_auth_success' field and backup
     * the previous value in 'prev_auth_success'. In order to keep the failure
     * counter coherent, if the 'last_auth_success' is newer than the
     * 'last_auth_failure' it means that there was no bad attempts since the last
     * log-on and 'nb_auth_failure' can be reset to zero.
     * 
     * @todo: define a global time object that would give the same time to all
     * actions on an execution.
     */
    function storeLoginSuccess($user_id, $time) {
        $sql = "UPDATE user 
                SET nb_auth_failure = 0,
                    prev_auth_success = last_auth_success,
                    last_auth_success = ". $this->da->escapeInt($time) .",
                    ". $this->_getLastAccessUpdate($time) ."
                WHERE user_id = ". $this->da->escapeInt($user_id);
        $this->update($sql);
    }
    
    /**
     * Don't log access if already accessed in the past 6 hours (scalability+privacy)
     */
    function _getLastAccessUpdate($time) {
        return "last_access_date  = IF(". $this->da->escapeInt($time) ." - last_access_date > 21600, ". $this->da->escapeInt($time) .", last_access_date)";
    }
    
    function storeLastAccessDate($user_id, $time) {
        $sql = "UPDATE user 
                SET ". $this->_getLastAccessUpdate($time) ."
                WHERE user_id = ". $this->da->escapeInt($user_id);
        $this->update($sql);
    }
    
    /**
     * Store login failure.
     *
     * Store last log-on failure and increment the number of failure. If the there
     * was no bad attemps since the last successful login (ie. 'last_auth_success'
     * newer than 'last_auth_failure') the counter is reset to 1.
     */
    function storeLoginFailure($login, $time) {
        $sql = "UPDATE user 
                SET nb_auth_failure = IF(last_auth_success >= last_auth_failure, 1, nb_auth_failure + 1), 
                last_auth_failure = ". $this->da->escapeInt($time) ."
                WHERE user_name = ". $this->da->quoteSmart($login);
        $this->update($sql);
    }
    
    function deleteSession($session_hash) {
        $sql = "DELETE FROM session
                WHERE session_hash = ". $this->da->quoteSmart($session_hash);
        return $this->update($sql);
    }

    /**
     * Search active users with realname or user_name like the variable.
     *
     * You can limit the number of results.
     * This is used by "search users as you type"
     */
    function & searchUserNameLike($name, $limit=0) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS realname, user_name".
            " FROM user".
            " WHERE (realname LIKE '%".db_es($name)."%'".
            " OR user_name LIKE '%".db_es($name)."%')".
            " AND status IN ('A', 'R')";
        $sql .= "ORDER BY realname";
        if($limit > 0) {
            $sql .= " LIMIT ".db_ei($limit);
        }

        return $this->retrieve($sql);
    }

    /**
     * Suspend user account according to a condition
     * 
     * @param String $condition SQL condition
     * 
     * @return Boolean
     */
    function suspendAccount($condition) {
        $sql = 'UPDATE user SET status = "S", unix_status = "S"'.
               ' WHERE '.$condition;
        return $this->update($sql);
    }

    /**
     * Suspend user account according to given date
     *
     * @param Integer $time
     *
     * @return Boolean
     */
    function suspendExpiredAccounts($time) {
        $condition = 'expiry_date != 0'.
                     ' AND expiry_date < '.$this->da->escapeInt($time);
        return $this->suspendAccount($condition);
    }

    /**
     * Suspend account of users who didn't access the platform after given date
     * 
     * @param Integer $time Unix timestamp of a date
     * 
     * @return Boolean
     */
    function suspendInactiveAccounts($time) {
        $condition = 'last_access_date != 0'.
                     ' AND last_access_date < '.$time;
        return $this->suspendAccount($condition);
    }

    /**
     * Return list of user_id that are not member of any project
     * 
     */
    function returnNotProjectMembers(){
        $sql = 'SELECT user_id FROM user LEFT JOIN user_group USING(user_id) WHERE group_id IS NULL and status in ("A","R")';
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() > 0) {
            // user who is no more member of any project.
            return $dar;
        } else {
            return false;
        }
    }

     /**
     * Return the last date of being removed from the last project
     * 
     */
    function delayForBeingNotProjectMembers($user_id){
        $req = 'SELECT date from group_history where field_name = "removed_user" and old_value REGEXP "[(]'.$this->da->escapeInt($user_id).'[)]$" order by date desc LIMIT 1';
        return $this->retrieve($req);
    }

    /**
     * Return 1 row if delay allowed to  be subscribed without belonging to any project has expired 
     * else 0 row
     */
    function delayForBeingSubscribed($user_id, $time){
        //Return delay for being subscribed and not being added to any project
        $select = 'SELECT NULL from user where user_id = '.$this->da->escapeInt($user_id).' and add_date < '.$this->da->escapeInt($time);
        return $this->retrieve($select);
    }

     /**
     * Suspend account of user who is no more member of any project
     * 
     */
    function suspendUserNotProjectMembers($time){
        $dar = $this->returnNotProjectMembers();
        if ($dar){
            //we should verify the delay for it user has been no more belonging to any project
            foreach ($dar as $row){
                //we split the treatment in two methods to distinguish between 0 row returned  
                //by the fact that there is no "removed user" entry for this user_id and the case  
                //where it is the result of comparing the date 
                $res = $this->delayForBeingNotProjectMembers($row['user_id']);
                if($res && !$res->isError()){
                    //user is not member of any project yet
                    if ($res->rowCount() == 0) {
                        //Verify add_date
                        $resultat = $this->delayForBeingSubscribed($row['user_id'],$time);
                        if ($resultat && !$resultat->isError() && $resultat->rowCount() == 1){
                            $condition = 'user.user_id = '.$this->da->escapeInt($row['user_id']);
                            return $this->suspendAccount($condition);
                        }else{
                            return;
                        }
                    } else {
                        //verify if delay has not expired yet
                        $rowLastRemove = $res->current();
                        if ($rowLastRemove['date'] > $time ){
                            return;
                        } else {
                            $condition = 'user.user_id = '.$this->da->escapeInt($row['user_id']);
                            return $this->suspendAccount($condition);	
                        }
                    }
                    
                }
            }
        }
        return;
    }

    /**
     * Return the result of  'FOUND_ROWS()' SQL method for the last query.
     */
    function foundRows() {
        $sql = "SELECT FOUND_ROWS() as nb;";
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
    }
     
}
?>

<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for User 
 */
class UserDao extends DataAccessObject {
    /**
    * Constructs the UserDao
    * @param $da instance of the DataAccess class
    */
    function UserDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM user";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches User by UserId 
    * @return DataAccessResult
    */
    function & searchByUserId($userId) {
        $sql = sprintf("SELECT * FROM user WHERE user_id = %s",
            $this->da->quoteSmart($userId));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserName 
    * @return DataAccessResult
    */
    function & searchByUserName($userName) {
        $sql = sprintf("SELECT * FROM user WHERE user_name = %s",
            $this->da->quoteSmart($userName));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Email 
    * @return DataAccessResult
    */
    function & searchByEmail($email) {
        $sql = sprintf("SELECT * FROM user WHERE email = %s",
            $this->da->quoteSmart($email));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserPw 
    * @return DataAccessResult
    */
    function & searchByUserPw($userPw) {
        $sql = sprintf("SELECT * FROM user WHERE user_pw = %s",
            $this->da->quoteSmart($userPw));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Realname 
    * @return DataAccessResult
    */
    function & searchByRealname($realname) {
        $sql = sprintf("SELECT * FROM user WHERE realname = %s",
            $this->da->quoteSmart($realname));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by RegisterPurpose 
    * @return DataAccessResult
    */
    function & searchByRegisterPurpose($registerPurpose) {
        $sql = sprintf("SELECT * FROM user WHERE register_purpose = %s",
            $this->da->quoteSmart($registerPurpose));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Status 
    * @return DataAccessResult
    */
    function & searchByStatus($status) {
        $sql = sprintf("SELECT * FROM user WHERE status = %s",
            $this->da->quoteSmart($status));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Shell 
    * @return DataAccessResult
    */
    function & searchByShell($shell) {
        $sql = sprintf("SELECT * FROM user WHERE shell = %s",
            $this->da->quoteSmart($shell));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixPw 
    * @return DataAccessResult
    */
    function & searchByUnixPw($unixPw) {
        $sql = sprintf("SELECT * FROM user WHERE unix_pw = %s",
            $this->da->quoteSmart($unixPw));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixStatus 
    * @return DataAccessResult
    */
    function & searchByUnixStatus($unixStatus) {
        $sql = sprintf("SELECT * FROM user WHERE unix_status = %s",
            $this->da->quoteSmart($unixStatus));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixUid 
    * @return DataAccessResult
    */
    function & searchByUnixUid($unixUid) {
        $sql = sprintf("SELECT * FROM user WHERE unix_uid = %s",
            $this->da->quoteSmart($unixUid));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixBox 
    * @return DataAccessResult
    */
    function & searchByUnixBox($unixBox) {
        $sql = sprintf("SELECT * FROM user WHERE unix_box = %s",
            $this->da->quoteSmart($unixBox));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by LdapName 
    * @return DataAccessResult
    */
    function & searchByLdapId($ldapName) {
        $sql = sprintf("SELECT * FROM user WHERE ldap_id = %s",
            $this->da->quoteSmart($ldapName));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by AddDate 
    * @return DataAccessResult
    */
    function & searchByAddDate($addDate) {
        $sql = sprintf("SELECT * FROM user WHERE add_date = %s",
            $this->da->quoteSmart($addDate));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by ConfirmHash 
    * @return DataAccessResult
    */
    function & searchByConfirmHash($confirmHash) {
        $sql = sprintf("SELECT * FROM user WHERE confirm_hash = %s",
            $this->da->quoteSmart($confirmHash));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by MailSiteupdates 
    * @return DataAccessResult
    */
    function & searchByMailSiteupdates($mailSiteupdates) {
        $sql = sprintf("SELECT * FROM user WHERE mail_siteupdates = %s",
            $this->da->quoteSmart($mailSiteupdates));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by MailVa 
    * @return DataAccessResult
    */
    function & searchByMailVa($mailVa) {
        $sql = sprintf("SELECT * FROM user WHERE mail_va = %s",
            $this->da->quoteSmart($mailVa));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by StickyLogin 
    * @return DataAccessResult
    */
    function & searchByStickyLogin($stickyLogin) {
        $sql = sprintf("SELECT * FROM user WHERE sticky_login = %s",
            $this->da->quoteSmart($stickyLogin));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by AuthorizedKeys 
    * @return DataAccessResult
    */
    function & searchByAuthorizedKeys($authorizedKeys) {
        $sql = sprintf("SELECT * FROM user WHERE authorized_keys = %s",
            $this->da->quoteSmart($authorizedKeys));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by EmailNew 
    * @return DataAccessResult
    */
    function & searchByEmailNew($emailNew) {
        $sql = sprintf("SELECT * FROM user WHERE email_new = %s",
            $this->da->quoteSmart($emailNew));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by PeopleViewSkills 
    * @return DataAccessResult
    */
    function & searchByPeopleViewSkills($peopleViewSkills) {
        $sql = sprintf("SELECT * FROM user WHERE people_view_skills = %s",
            $this->da->quoteSmart($peopleViewSkills));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by PeopleResume 
    * @return DataAccessResult
    */
    function & searchByPeopleResume($peopleResume) {
        $sql = sprintf("SELECT * FROM user WHERE people_resume = %s",
            $this->da->quoteSmart($peopleResume));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Timezone 
    * @return DataAccessResult
    */
    function & searchByTimezone($timezone) {
        $sql = sprintf("SELECT * FROM user WHERE timezone = %s",
            $this->da->quoteSmart($timezone));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Fontsize 
    * @return DataAccessResult
    */
    function & searchByFontsize($fontsize) {
        $sql = sprintf("SELECT * FROM user WHERE fontsize = %s",
            $this->da->quoteSmart($fontsize));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Theme 
    * @return DataAccessResult
    */
    function & searchByTheme($theme) {
        $sql = sprintf("SELECT * FROM user WHERE theme = %s",
            $this->da->quoteSmart($theme));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by LanguageId 
    * @return DataAccessResult
    */
    function & searchByLanguageId($languageId) {
        $sql = sprintf("SELECT * = %s",
            $this->da->quoteSmart($languageId));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table user 
    * @return true or id(auto_increment) if there is no error
    */
    function create($user_name, $email, $user_pw, $realname, $register_purpose, $status, $shell, $unix_pw, $unix_status, $unix_uid, $unix_box, $ldap_id, $add_date, $confirm_hash, $mail_siteupdates, $mail_va, $sticky_login, $authorized_keys, $email_new, $people_view_skills, $people_resume, $timezone, $fontsize, $theme, $language_id) {
		$sql = sprintf("INSERT INTO user (user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, fontsize, theme, language_id) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            $this->da->quoteSmart($user_name),
            $this->da->quoteSmart($email),
            $this->da->quoteSmart($user_pw),
            $this->da->quoteSmart($realname),
            $this->da->quoteSmart($register_purpose),
            $this->da->quoteSmart($status),
            $this->da->quoteSmart($shell),
            $this->da->quoteSmart($unix_pw),
            $this->da->quoteSmart($unix_status),
            $this->da->quoteSmart($unix_uid),
            $this->da->quoteSmart($unix_box),
            $this->da->quoteSmart($ldap_id),
            $this->da->quoteSmart($add_date),
            $this->da->quoteSmart($confirm_hash),
            $this->da->quoteSmart($mail_siteupdates),
            $this->da->quoteSmart($mail_va),
            $this->da->quoteSmart($sticky_login),
            $this->da->quoteSmart($authorized_keys),
            $this->da->quoteSmart($email_new),
            $this->da->quoteSmart($people_view_skills),
            $this->da->quoteSmart($people_resume),
            $this->da->quoteSmart($timezone),
            $this->da->quoteSmart($fontsize),
            $this->da->quoteSmart($theme),
            $this->da->quoteSmart($language_id));
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

    
    /**
    * Searches User status by Email
    * @return DataAccessResult
    */
    function & searchStatusByEmail($email) {
        //ST: with LDAP user_name can be an email
        $sql = sprintf("SELECT realname, email, status FROM user WHERE (user_name=%s OR email = %s)",
                $this->da->quoteSmart($email),
                $this->da->quoteSmart($email));
        return $this->retrieve($sql);
    }
    
    function searchBySessionHashAndIp($session_hash, $ip) {
        $sql = "SELECT user.*, session_hash, session.ip_addr AS session_ip_addr, session.time AS session_time
                FROM user INNER JOIN session USING (user_id)
                WHERE session_hash = ". $this->da->quoteSmart($session_hash);
        $first_part_of_ip = implode('.', array_slice(explode('.', $ip), 0, 2));
        $sql .= "
              AND session.ip_addr LIKE ". $this->da->quoteSmart($first_part_of_ip .'.%');
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
}


?>
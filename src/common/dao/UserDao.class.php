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
    function & searchAll($offset=null, $limit=null) {
        $sql = "SELECT * FROM user";

        if($offset !== null && $limit !== null) {
            $sql .= ' LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }
        
        return $this->retrieve($sql);
    }
    
    /**
    * Searches User by UserId 
    * @return DataAccessResult
    */
    function & searchByUserId($userId) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE user_id = %s",
            $this->da->quoteSmart($userId));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserName 
    * @return DataAccessResult
    */
    function & searchByUserName($userName) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE user_name = %s",
            $this->da->quoteSmart($userName));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Email 
    * @return DataAccessResult
    */
    function & searchByEmail($email) {
        $sql = sprintf("SELECT user_id, user_name, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE email = %s",
            $this->da->quoteSmart($email));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserPw 
    * @return DataAccessResult
    */
    function & searchByUserPw($userPw) {
        $sql = sprintf("SELECT user_id, user_name, email, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE user_pw = %s",
            $this->da->quoteSmart($userPw));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Realname 
    * @return DataAccessResult
    */
    function & searchByRealname($realname) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE realname = %s",
            $this->da->quoteSmart($realname));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by RegisterPurpose 
    * @return DataAccessResult
    */
    function & searchByRegisterPurpose($registerPurpose) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE register_purpose = %s",
            $this->da->quoteSmart($registerPurpose));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Status 
    * @return DataAccessResult
    */
    function & searchByStatus($status, $offset=null, $limit=null) {
        $sql = sprintf("SELECT * FROM user WHERE status = %s ORDER BY user_name",
            $this->da->quoteSmart($status));
        if($offset !== null && $limit !== null) {
            $sql .= ' LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Shell 
    * @return DataAccessResult
    */
    function & searchByShell($shell) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE shell = %s",
            $this->da->quoteSmart($shell));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixPw 
    * @return DataAccessResult
    */
    function & searchByUnixPw($unixPw) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE unix_pw = %s",
            $this->da->quoteSmart($unixPw));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixStatus 
    * @return DataAccessResult
    */
    function & searchByUnixStatus($unixStatus) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE unix_status = %s",
            $this->da->quoteSmart($unixStatus));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixUid 
    * @return DataAccessResult
    */
    function & searchByUnixUid($unixUid) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE unix_uid = %s",
            $this->da->quoteSmart($unixUid));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UnixBox 
    * @return DataAccessResult
    */
    function & searchByUnixBox($unixBox) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE unix_box = %s",
            $this->da->quoteSmart($unixBox));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by LdapName 
    * @return DataAccessResult
    */
    function & searchByLdapId($ldapName) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE ldap_id = %s",
            $this->da->quoteSmart($ldapName));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by AddDate 
    * @return DataAccessResult
    */
    function & searchByAddDate($addDate) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE add_date = %s",
            $this->da->quoteSmart($addDate));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by ConfirmHash 
    * @return DataAccessResult
    */
    function & searchByConfirmHash($confirmHash) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE confirm_hash = %s",
            $this->da->quoteSmart($confirmHash));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by MailSiteupdates 
    * @return DataAccessResult
    */
    function & searchByMailSiteupdates($mailSiteupdates) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE mail_siteupdates = %s",
            $this->da->quoteSmart($mailSiteupdates));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by MailVa 
    * @return DataAccessResult
    */
    function & searchByMailVa($mailVa) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE mail_va = %s",
            $this->da->quoteSmart($mailVa));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by StickyLogin 
    * @return DataAccessResult
    */
    function & searchByStickyLogin($stickyLogin) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE sticky_login = %s",
            $this->da->quoteSmart($stickyLogin));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by AuthorizedKeys 
    * @return DataAccessResult
    */
    function & searchByAuthorizedKeys($authorizedKeys) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE authorized_keys = %s",
            $this->da->quoteSmart($authorizedKeys));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by EmailNew 
    * @return DataAccessResult
    */
    function & searchByEmailNew($emailNew) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE email_new = %s",
            $this->da->quoteSmart($emailNew));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by PeopleViewSkills 
    * @return DataAccessResult
    */
    function & searchByPeopleViewSkills($peopleViewSkills) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_resume, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE people_view_skills = %s",
            $this->da->quoteSmart($peopleViewSkills));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by PeopleResume 
    * @return DataAccessResult
    */
    function & searchByPeopleResume($peopleResume) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, timezone, windows_pw, fontsize, theme, language_id FROM user WHERE people_resume = %s",
            $this->da->quoteSmart($peopleResume));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Timezone 
    * @return DataAccessResult
    */
    function & searchByTimezone($timezone) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, windows_pw, fontsize, theme, language_id FROM user WHERE timezone = %s",
            $this->da->quoteSmart($timezone));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by WindowsPw 
    * @return DataAccessResult
    */
    function & searchByWindowsPw($windowsPw) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, fontsize, theme, language_id FROM user WHERE windows_pw = %s",
            $this->da->quoteSmart($windowsPw));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Fontsize 
    * @return DataAccessResult
    */
    function & searchByFontsize($fontsize) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, theme, language_id FROM user WHERE fontsize = %s",
            $this->da->quoteSmart($fontsize));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Theme 
    * @return DataAccessResult
    */
    function & searchByTheme($theme) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, language_id FROM user WHERE theme = %s",
            $this->da->quoteSmart($theme));
        return $this->retrieve($sql);
    }

    /**
    * Searches User by LanguageId 
    * @return DataAccessResult
    */
    function & searchByLanguageId($languageId) {
        $sql = sprintf("SELECT user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme FROM user WHERE language_id = %s",
                       $this->da->quoteSmart($languageId));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table user 
    * @return true or id(auto_increment) if there is no error
    */
    function create($user_name, $email, $user_pw, $realname, $register_purpose, $status, $shell, $unix_pw, $unix_status, $unix_uid, $unix_box, $ldap_id, $add_date, $confirm_hash, $mail_siteupdates, $mail_va, $sticky_login, $authorized_keys, $email_new, $people_view_skills, $people_resume, $timezone, $windows_pw, $fontsize, $theme, $language_id) {
		$sql = sprintf("INSERT INTO user (user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
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
            $this->da->quoteSmart($windows_pw),
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

    /**
     * Searches User by the begining of their name
     * @return DataAccessResult
     */
    function & searchByNameFirstLetter($usersearch,$offset=null, $limit=null) {
        $cleanUserSearch = db_escape_string($usersearch);
        $sql = 'SELECT * '.
               'FROM user '.
               'WHERE user_name '.
               'LIKE \''.$cleanUserSearch.'%\' '.
               'ORDER BY user_name ';
        if($offset !== null && $limit !== null) {
            $sql .= 'LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }
        
        return $this->retrieve($sql);
    }

    /**
     * Search User by their login name and real name
     * @return DataAccessResult
     */
    function & searchByAllNames($usersearch,$offset=null, $limit=null) {
        $cleanUserSearch = db_escape_string($usersearch);
        $sql = 'SELECT * '.
               'FROM user '.
               'WHERE (user_name '.
               'LIKE \'%'.$cleanUserSearch.'%\' '.
               'OR '.
               'realname '.
               'LIKE \'%'.$cleanUserSearch.'%\') '.
               'ORDER BY user_name,realname ';
        if($offset !== null && $limit !== null) {
            $sql .= 'LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }
        
        return $this->retrieve($sql);
    }

    /**
     * Search user by their group name
     * @return DataAccessResult
     */
    function & searchByGroupName($usersearch,$offset=null, $limit=null) {
        $cleanUserSearch = db_escape_string($usersearch);
        $sql = 'SELECT DISTINCT user_name,user.user_id, email, user_pw, realname, user.register_purpose, user.status, shell, unix_pw, unix_status, unix_uid, user.unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme '.
               'FROM user, user_group, groups '.
               'WHERE user.user_id = user_group.user_id '.
               'AND user_group.group_id = groups.group_id '.
               'AND groups.group_name LIKE \'%'.$cleanUserSearch.'%\' '.
               'ORDER BY user_name ';
        if($offset !== null && $limit !== null) {
            $sql .= 'LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }

        return $this->retrieve($sql);
    }

    function & searchByUserAndGroupName($username, $groupname, $offset=null, $limit=null) {
        $cleanusername = db_escape_string($username);
        $cleangroupname = db_escape_string($groupname);

        $sql = 'SELECT DISTINCT user_name,user.user_id, email, user_pw, realname, user.register_purpose, user.status, shell, unix_pw, unix_status, unix_uid, user.unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme '.
               'FROM user, user_group, groups '.
               'WHERE user.user_id = user_group.user_id '.
               'AND user_group.group_id = groups.group_id '.
               'AND user.user__name LIKE \'%'.$cleanusername.'%\' '.
               'OR realname '.
               'LIKE \'%'.$cleanusername.'%\' '.
               'AND groups.group_name LIKE \'%'.$cleangroupname.'%\' '.
               'ORDER BY user.user_name, user.real_name, groups.group_name ';

        if($offset !== null && $limit !== null) {
            $sql .= 'LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }

        return $this->retrieve($sql);
    }

    function & searchByUserNameAndStatus($username, $status, $offset=null, $limit=null) {
        $cleanusername = db_escape_string($username);
        $cleanstatus = $this->da->quoteSmart($status);

        $sql = 'SELECT * '.
               'FROM user '.
               'WHERE user_name '.
               'LIKE \''.$cleanusername.'%\' '.
               'OR realname '.
               'LIKE \'%'.$cleanusername.'%\' '.
               'AND status = '.$cleanstatus.
               'ORDER BY user_name, realname, status ';
        
        if($offset !== null && $limit !== null) {
            $sql .= 'LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }
        
        return $this->retrieve($sql);
    }

    /**
     * search user by criteria
     *
     */
    function & searchUserByCriteria($ca, $offset, $limit) {

        $sql = 'SELECT * ';

        foreach($ca as $c) {

            $from = $c->getFrom();
            $where = $c->getWhere();
            
            if ($c->getGroupBy() !== null) {
                $groupby = $c->getGroupBy();
            }
        }

        $sql .= 'FROM '.$from;
        $sql .= ' WHERE '.$where;
        
        if ($c->getGroupBy() !== null) {
            $sql .= ' GROUP BY '.$groupby;
        }
   
        $sql .= ' LIMIT '.$offset.', '.$limit;
        return $this->retrieve($sql);
    }
    
    /**
     * count the number of row of a resource
     * @return int
     */
    function & count($function) {   
        $count = db_num_rows($this->function);
        return $count;
    }
}

interface Statement {

    public function getFrom();

    public function getWhere();

    public function getGroupBy();
    
    public function getOrderBy();
}

class UserNameStatusCriteria implements Statement {

    private $name;

    private $status;

    function __construct($name, $status) {
        $this->name = $name;
        $this->status = $status;
    }

    function getFrom() {
        return 'user';
    }

    function getWhere() {
        return '(user_name LIKE \'%'.$this->name.'%\' OR realname LIKE \'%'.$this->name.'%\') AND status = \''.$this->status.'\'';
    }

    function getGroupBy() {}

    function getOrderBy() {
        return 'user_name, realname, status';
    }
}


class UserNameCriteria implements Statement {

    private $name;

    function __construct($name) {
        $this->name = $name;
    }
    
    function getFrom() {
        return 'user';
    }

    function getWhere() {
        return 'user_name LIKE \'%'.$this->name.'%\' OR realname LIKE \'%'.$this->name.'%\'';
    }

    function getGroupBy() {}

    function getOrderBy() {
        return 'user_name, realname, status';
    }
}


class UserGroupCriteria implements Statement {

    private $group;

    function __construct($group) {
        $this->group = $group;
    }

    function getFrom() {
        return 'user JOIN user_group ON (user.user_id = user_group.user_id) JOIN groups ON (user_group.group_id = groups.group_id)';
    }

    function getWhere() {
        return '(groups.group_name LIKE \'%'.$this->group.'%\' OR groups.unix_group_name LIKE \'%'.$this->group.'%\')';
    }

    function getGroupBy() {
        return 'user.user_id';
}

    function getOrderBy() {
        return 'user_name, realname, status';
    }
}

class UserStatusCriteria implements Statement {

    private $status;

    function __construct($status) {
        $this->status = $status;
    }

    function getFrom() {
        return 'user';
    }

    function getWhere() {
        return 'user.status = \''.$this->status.'\'';
    }

    function getGroupBy() {}

    function getOrderBy() {
        return 'user_name, realname, status';
    }
}

?>

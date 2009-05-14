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

require_once('common/dao/UserPreferencesDao.class.php');
require_once('common/dao/UserGroupDao.class.php');

/**
 *
 * User object
 * 
 * Sets up database results and preferences for a user and abstracts this info
 */
class User {
    
    /**
     * The user is active
     */
    const STATUS_ACTIVE     = 'A';
    
    /**
     * The user is restricted
     */
    const STATUS_RESTRICTED = 'R';
    
    /**
     * The user is pending
     */
    const STATUS_PENDING    = 'P';
    
    /**
     * The user is suspended
     */
    const STATUS_SUSPENDED  = 'S';
    
    /**
     * The user is deleted
     */
    const STATUS_DELETED    = 'D';
    
    
    
    /**
     * the id of the user
     * = 0 if anonymous
     */
    protected $id;

    protected $user_id;
    protected $user_name;
    protected $email;
    protected $user_pw;
    protected $realname;
    protected $register_purpose;
    protected $status;
    protected $shell;
    protected $unix_pw;
    protected $unix_status;
    protected $unix_uid;
    protected $unix_box;
    protected $ldap_id;
    protected $add_date;
    protected $confirm_hash;
    protected $mail_siteupdates;
    protected $mail_va;
    protected $sticky_login;
    protected $authorized_keys;
    protected $email_new;
    protected $people_view_skills;
    protected $people_resume;
    protected $timezone;
    protected $fontsize;
    protected $theme;
    protected $language_id;
    protected $last_pwd_update;
    protected $last_access_date;
    protected $expiry_date;
    protected $prev_auth_success;
    protected $last_auth_success;
    protected $last_auth_failure;
    protected $nb_auth_failure;

    /**
     * Keep super user info
     */
    protected $is_super_user;

    /**
     * The locale of the user
     */
    protected $locale;
    
    /**
     * The preferences
     */
    var $_preferences;
    
    /**
     * The dao used to retrieve preferences
     */
    var $_preferencesdao;
    
    /**
     * The dao used to retrieve user-group info
     */
    var $_usergroupdao;
    
    /**
     * session hash
     * By default it is false. Use explicitly setSessionHash()
     * @see setSessionHash
     */
    protected $session_hash;
    
    /**
     * Special property, out of $data_array to store CLEAR password
     * should be used only for update/creation purpose.
     */
    private $password;
    
    /**
     * Constructor
     * 
     * You should not create new User directly. 
     * Please use the UserManager instead to retrieve users.
     * 
     * @param array the row corresponding to the user. default is null (anonymous)
     */
    public function __construct($row = null) {
        
        $this->is_super_user = null;
        $this->locale        = '';
        $this->_preferences  = array();
        
        $this->user_id            = isset($row['user_id'])            ? $row['user_id']            : 0;
        $this->user_name          = isset($row['user_name'])          ? $row['user_name']          : null;
        $this->email              = isset($row['email'])              ? $row['email']              : null;
        $this->user_pw            = isset($row['user_pw'])            ? $row['user_pw']            : null;
        $this->realname           = isset($row['realname'])           ? $row['realname']           : null;
        $this->register_purpose   = isset($row['register_purpose'])   ? $row['register_purpose']   : null;
        $this->status             = isset($row['status'])             ? $row['status']             : null;
        $this->shell              = isset($row['shell'])              ? $row['shell']              : null;
        $this->unix_pw            = isset($row['unix_pw'])            ? $row['unix_pw']            : null;
        $this->unix_status        = isset($row['unix_status'])        ? $row['unix_status']        : null;
        $this->unix_uid           = isset($row['unix_uid'])           ? $row['unix_uid']           : null;
        $this->unix_box           = isset($row['unix_box'])           ? $row['unix_box']           : null;
        $this->ldap_id            = isset($row['ldap_id'])            ? $row['ldap_id']            : null;
        $this->add_date           = isset($row['add_date'])           ? $row['add_date']           : null;
        $this->confirm_hash       = isset($row['confirm_hash'])       ? $row['confirm_hash']       : null;
        $this->mail_siteupdates   = isset($row['mail_siteupdates'])   ? $row['mail_siteupdates']   : null;
        $this->mail_va            = isset($row['mail_va'])            ? $row['mail_va']            : null;
        $this->sticky_login       = isset($row['sticky_login'])       ? $row['sticky_login']       : null;
        $this->authorized_keys    = isset($row['authorized_keys'])    ? $row['authorized_keys']    : null;
        $this->email_new          = isset($row['email_new'])          ? $row['email_new']          : null;
        $this->people_view_skills = isset($row['people_view_skills']) ? $row['people_view_skills'] : null;
        $this->people_resume      = isset($row['people_resume'])      ? $row['people_resume']      : null;
        $this->timezone           = isset($row['timezone'])           ? $row['timezone']           : null;
        $this->fontsize           = isset($row['fontsize'])           ? $row['fontsize']           : null;
        $this->theme              = isset($row['theme'])              ? $row['theme']              : null;
        $this->language_id        = isset($row['language_id'])        ? $row['language_id']        : null;
        $this->last_pwd_update    = isset($row['last_pwd_update'])    ? $row['last_pwd_update']    : null;
        $this->last_access_date   = isset($row['last_access_date'])   ? $row['last_access_date']   : null;
        $this->expiry_date        = isset($row['expiry_date'])        ? $row['expiry_date']        : null;
        $this->prev_auth_success  = isset($row['prev_auth_success'])  ? $row['prev_auth_success']  : null;
        $this->last_auth_success  = isset($row['last_auth_success'])  ? $row['last_auth_success']  : null;
        $this->last_auth_failure  = isset($row['last_auth_failure'])  ? $row['last_auth_failure']  : null;
        $this->nb_auth_failure    = isset($row['nb_auth_failure'])    ? $row['nb_auth_failure']    : null;
        
        $this->id = $this->user_id;
        
        //set the locale
        if (!$this->language_id) {
            //Detect browser settings
            $accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            $this->locale = $GLOBALS['Language']->getLanguageFromAcceptLanguage($accept_language);
        } else {
            $this->locale = $this->language_id;
        }
        
        $this->session_hash = false;
    }
    
    /**
     * clear: clear the cached group data
     */
    function clearGroupData() {
        unset($this->_group_data);
        $this->_group_data = null;
    }
    /**
     * clear: clear the cached tracker data
     */
    function clearTrackerData() {
       unset($this->_tracker_data);
       $this->_tracker_data = null;
    }
     
    /**
     * group data row from db. 
     * For each group_id (the user is part of) one array from the user_group table
     */
    protected $_group_data;
    protected function getUserGroupData() {
        if (!is_array($this->_group_data)) {
            $this->_group_data = array();
            if ($this->user_id) {
                foreach($this->getUserGroupDao()->searchByUserId($this->user_id) as $row) {
                    $this->_group_data[$row['group_id']] = $row;
                }
            }
        }
        return $this->_group_data;
    }
    
    /**
     * is this user member of group $group_id ??
     */
    public function isMember($group_id,$type = 0) {
        $group_data = $this->getUserGroupData();
        
        $is_member = false;
        
        if (isset($group_data[1]['admin_flags']) && $group_data[1]['admin_flags'] == 'A') {
            //Codendi admins always return true
            $is_member = true;
        } else if (isset($group_data[$group_id])) {
            if ($type === 0) {
                //We just want to know if the user is member of the group regardless the role
                $is_member = true;
            } else {
                //Lookup for the role defined by $type
                $group_perm = $group_data[$group_id];
                $type       = strtoupper($type);

                switch ($type) {
                    case 'A' : //admin for this group
                        $is_member = ($group_perm['admin_flags'] && $group_perm['admin_flags'] === 'A');
                        break;
                    case 'B1': //bug tech
                        $is_member = ($group_perm['bug_flags'] == 1 || $group_perm['bug_flags'] == 2);
                        break;
                    case 'B2' : //bug admin
                        $is_member = ($group_perm['bug_flags'] == 2 || $group_perm['bug_flags'] == 3);
                        break;
                    case 'P1' : //pm tech
                        $is_member = ($group_perm['project_flags'] == 1 || $group_perm['project_flags'] == 2);
                        break;
                    case 'P2' : //pm admin
                        $is_member = ($group_perm['project_flags'] == 2 || $group_perm['project_flags'] == 3);
                        break;
                    case 'C1' : //patch tech
                        $is_member = ($group_perm['patch_flags'] == 1 || $group_perm['patch_flags'] == 2);
                        break;
                    case 'C2' : //patch admin
                        $is_member = ($group_perm['patch_flags'] == 2 || $group_perm['patch_flags'] == 3);
                        break;
                    case 'F2' : //forum admin
                        $is_member = ($group_perm['forum_flags'] == 2);
                        break;
                    case 'S1' : //support tech
                        $is_member = ($group_perm['support_flags'] == 1 || $group_perm['support_flags'] == 2);
                        break;
                    case 'S2' : //support admin
                        $is_member = ($group_perm['support_flags'] == 2 || $group_perm['support_flags'] == 3);
                        break;
                    case 'D1' : //document tech
                        $is_member = ($group_perm['doc_flags'] == 1 || $group_perm['doc_flags'] == 2);
                        break;
                    case 'D2' : //document admin
                        $is_member = ($group_perm['doc_flags'] == 2 || $group_perm['doc_flags'] == 3);
                        break;
                    case 'R2' : //file release admin
                        $is_member = ($group_perm['file_flags'] == 2);
                        break;
                    case 'W2': //wiki release admin
                        $is_member = ($group_perm['wiki_flags'] == 2);
                        break;
                    case 'SVN_ADMIN': //svn admin
                        $is_member = ($group_perm['svn_flags'] == 2);
                        break;
                    case 'N1': //news write
                        $is_member = ($group_perm['news_flags'] == 1);
                        break;
                    case 'N2': //news admin
                        $is_member = ($group_perm['news_flags'] == 2);
                        break;
                    default : //fubar request
                        $is_member = false;
                }
            }
        }
        return $is_member;
    }

    public function isNone() {
        return $this->getId() == 100;
    }
    
    public function isAnonymous() {
        return $this->getId() == 0;
    }
    
    public function isLoggedIn() {
        return $this->getSessionHash() !== false;
    }
    
    /** 
     * is this user admin of the tracker group_artifact_id
     * @return boolean
     */
    public function isTrackerAdmin($group_id,$group_artifact_id) {
      return ($this->getTrackerPerm($group_artifact_id) >= 2 || $this->isMember($group_id,'A'));
    }
    
    /**
     * tracker permission data
     * for each group_artifact_id (the user is part of) one array from the artifact-perm table
     */
    protected $_tracker_data;
    protected function getTrackerData() {
        if (!$this->_tracker_data) {
            $this->_tracker_data = array();
            $id = (int)$this->user_id;
            //TODO: use a DAO (waiting for the next tracker api)
            $sql = "SELECT group_artifact_id, perm_level 
                    FROM artifact_perm WHERE user_id = $id";
            $db_res = db_query($sql);
            if (db_numrows($db_res) > 0) {
                while ($row = db_fetch_array($db_res)) {
                    $this->_tracker_data[$row['group_artifact_id']] = $row;
                }
            }
        }
        return $this->_tracker_data;
    }

    function getTrackerPerm($group_artifact_id) {
        $tracker_data = $this->getTrackerData();
        return isset($tracker_data[$group_artifact_id]) ? $tracker_data[$group_artifact_id]['perm_level'] : 0;
    }


    function isSuperUser() {
        return $this->isMember(1, 'A');
    }
    
    var $_ugroups;
    function getUgroups($group_id, $instances) {
        $hash = md5(serialize($instances));
        if (!isset($this->_ugroups)) {
            $this->_ugroups = array();
        }
        if (!isset($this->_ugroups[$hash])) {
            $this->_ugroups[$hash] = array_merge($this->getDynamicUgroups($group_id, $instances), $this->getStaticUgroups($group_id));
        }
        return $this->_ugroups[$hash];
    }
    
    var $_static_ugroups;
    function getStaticUgroups($group_id) {
        if (!isset($this->_static_ugroups)) {
            $this->_static_ugroups = array();
            if (!$this->isSuperUser()) {
                $res = ugroup_db_list_all_ugroups_for_user($group_id, $this->id);
                while ($row = db_fetch_array($res)) {
                    $this->_static_ugroups[] = $row['ugroup_id'];
                }
            }
        }
        return $this->_static_ugroups;
    }
    
    var $_dynamics_ugroups;
    function getDynamicUgroups($group_id, $instances) {
        $hash = md5(serialize($instances));
        if (!isset($this->_dynamics_ugroups)) {
            $this->_dynamics_ugroups = array();
        }
        if (!isset($this->_dynamics_ugroups[$hash])) {
            $this->_dynamics_ugroups[$hash] = ugroup_db_list_dynamic_ugroups_for_user($group_id, $instances, $this->id);
        }
        return $this->_dynamics_ugroups[$hash];
    }
    
    //
    // Getter
    //

    /**
     * Return associative array of data from db
     * 
     * @return array
     */
    function toRow() {
    	$row = array();
    	foreach ($this->data_array as $k => $v) {
    		if (!is_numeric($k)) {
    			$row[$k] = $v;
    		}
    	}
        return $row;
    }

    /**
     * @return int the ID of the user
     */
    function getId() {
        return $this->id;
    }
    /**
     * alias of getUserName()
     * @return string the name of the user (aka login)
     */
    function getName() {
        return $this->getUserName();
    }
    /**
     * @return string the name of the user (aka login)
     */
    function getUserName() {
        return $this->user_name;
    }
    /**
     * @return string the real name of the user
     */
    function getRealName() {
        return $this->realname;
    }
    /**
     * @return string the email adress of the user
     */
    function getEmail() {
        return $this->email;
    }
    /**
     * @return string the Status of the user
     * 'A' = Active
     * 'R' = Restricted
     * 'D' = Deleted
     * 'S' = Suspended
     */
    function getStatus() {
        return $this->status;
    }
    /**
     * @return string ldap identifier of the user
     */
    function getLdapId() {
        return $this->data_array['ldap_id'];
    }    
    /**
     * @return string the registration date of the user (timestamp format)
     */
    function getAddDate() {
        return $this->add_date;
    }
    /**
     * @return string the last time the user has changed her password
     */
    function getLastPwdUpdate() {
        return $this->last_pwd_update;
    }
    /**
     * @return string the last access date of the user (timestamp format)
     */
    function getLastAccessDate() {
        return $this->last_access_date;
    }
    /**
     * @return string the timezone of the user (GMT, Europe/Paris, etc ...)
     */
    function getTimezone() {
        return $this->timezone;
    }
    /**
     * @return int 1 if the user accept to receive site mail updates, 0 if he does'nt
     */
    function getMailSiteUpdates() {
        return $this->mail_siteupdates;
    }
    /**
     * @return int 1 if the user accept to receive additional mails from the community, 0 if he does'nt
     */
    function getMailVA() {
        return $this->mail_va;
    }
    /**
     * @return int 0 or 1
     */
    function getStickyLogin() {
        return $this->sticky_login;
    }
    /**
     * @return int font size preference of the user
     */
    function getFontSize() {
        return $this->fontsize;
    }
    /**
     * @return string theme set in user's preferences
     */
    function getTheme() {
        return $this->theme;
    }
    /**
     * @return string the Status of the user
     * '0' = (number zero) special value for the site admin
     * 'N' = No Unix Account
     * 'A' = Active
     * 'S' = Suspended
     * 'D' = Deleted
     */
    function getUnixStatus() {
        return $this->unix_status;
    }
    
    function getUnixUid() {
        return $this->data_array['unix_uid'];
    }
    
    /**
     * @return string unix box of the user
     */
    function getUnixBox() {
        return $this->unix_box;
    }
    /**
     * @return real unix ID of the user (not the one in the DB!)
     */
    function getRealUnixUID() {
        $unix_id = $this->unix_uid + $GLOBALS['unix_uid_add'];
        return $unix_id;
    }
    /**
     * @return string authorized keys of the user
     */
    function getAuthorizedKeys() {
        return $this->authorized_keys;
    }
    /**
     * @return string resume of the user
     */
    function getPeopleResume() {
        return $this->people_resume;
    }
    /**
     * @return int 1 if the user skills are public, 0 otherwise
     */
    function getPeopleViewSkills() {
        return $this->people_view_skills;
    }
    /**
     * @return int ID of the language of the user
     */
    function getLanguageID() {
        return $this->language_id;
    }
    /**
     * @return string md5 of user pwd
     */
    function getUserPw() {
        return $this->user_pw;
    }
    
    /**
     * @return String User shell
     */
    function getShell() {
        return $this->data_array['shell'];
    }
    
    function getLocale() {
        return $this->locale;
    }

    /**
     * @return int Timestamp of the last authentication success.
     */
    function getLastAuthSuccess() {
        return $this->last_auth_success;
    }
    
    /**
     * Return the previous authentication success (the one before last auth
     * success).
     * @return int Timestamp of the previous authentication success.
     */
    function getPreviousAuthSuccess() {
        return $this->prev_auth_success;
    }
    
    /**
     * @return int Timestamp of the last unsuccessful authencation attempt
     */
    function getLastAuthFailure() {
        return $this->last_auth_failure;
    }
    
    /**
     * @return int Number of authentication failure since the last success.
     */
    function getNbAuthFailure() {
        return $this->nb_auth_failure;
    }

    /** 
     * @return String Clear user password
     */
    function getPassword() {
        return $this->password; 
    }
    /**
     * isActive - test if the user is active or not
     * 
     * @return boolean true if the user is active, false otherwise
     */
    function isActive() {
        return ($this->getStatus() == 'A');
    }

    /**
     * isRestricted - test if the user is restricted or not
     * 
     * @return boolean true if the user is restricted, false otherwise
     */
    function isRestricted() {
        return (!$this->isAnonymous() && $this->getStatus() == 'R');
    }

    /**
     * isDeleted - test if the user is deleted or not
     * 
     * @return boolean true if the user is deleted, false otherwise
     */
    function isDeleted() {
        return ($this->getStatus() == 'D');
    }

    /**
     * isSuspended - test if the user is suspended or not
     * 
     * @return boolean true if the user is suspended, false otherwise
     */
    function isSuspended() {
        return ($this->getStatus() == 'S');
    }

    /**
     * hasActiveUnixAccount - test if the unix account of the user is active or not
     * 
     * @return boolean true if the unix account of the user is active, false otherwise
     */
    function hasActiveUnixAccount() {
        return ($this->getUnixStatus() == 'A');
    }

    /**
     * hasSuspendedUnixAccount - test if the unix account of the user is suspended or not
     * 
     * @return boolean true if the unix account of the user is suspended, false otherwise
     */
    function hasSuspendedUnixAccount() {
        return ($this->getUnixStatus() == 'S');
    }

    /**
     * hasDeletedUnixAccount - test if the unix account of the user is deleted or not
     * 
     * @return boolean true if the unix account of the user is deleted, false otherwise
     */
    function hasDeletedUnixAccount() {
        return ($this->getUnixStatus() == 'D');
    }

    /**
     * hasNoUnixAccount - test if the user doesn't have a unix account
     * 
     * @return boolean true if the user doesn't have a unix account, false otherwise
     */
    function hasNoUnixAccount() {
        return ($this->getUnixStatus() == 'N');
    }
    
    /**
     * @return array groups id the user is member of
     */
    function getProjects() {
        $projects = array();
        foreach($this->getUserGroupDao()->searchActiveGroupsByUserId($this->user_id) as $data) {
            $projects[] = $data['group_id'];
        }
        return $projects;
    }

    //
    // Setters
    //

    /**
     * @param int the ID of the user
     */
    function setId($id) {
        $this->id = $id;
        $this->data_array['user_id'] = $id;
    }

    /**
     * @param string the name of the user (aka login)
     */
    function setUserName($name) {
        $this->data_array['user_name'] = $name;
    }
    /**
     * @param string the real name of the user
     */
    function setRealName($name) {
        $this->data_array['realname'] = $name;
    }
    /**
     * @param string the email adress of the user
     */
    function setEmail($email) {
        $this->data_array['email'] = $email;
    }
    /**
     * @param string the Status of the user
     * 'A' = Active
     * 'R' = Restricted
     * 'D' = Deleted
     * 'S' = Suspended
     */
    function setStatus($status) {
    	$allowedStatus = array('A' => true,
    	                       'R' => true,
    	                       'D' => true,
    	                       'S' => true);
    	if (isset($allowedStatus[$status])) {
            $this->data_array['status'] = $status;
    	}
    }
    /**
     * @param string ldap identifier of the user
     */
    function setLdapId($ldapId) {
        $this->data_array['ldap_id'] = $ldapId;
    }    
    /**
     * @param string the registration date of the user (timestamp format)
     */
    function setAddDate($addDate) {
        $this->data_array['add_date'] = $addDate;
    }
    /**
     * @param string the timezone of the user (GMT, Europe/Paris, etc ...)
     */
    function setTimezone($timezone) {
        $this->data_array['timezone'] = $timezone;
    }
    /**
     * @param int 1 if the user accept to receive site mail updates, 0 if he does'nt
     */
    function setMailSiteUpdates($mailSiteUpdate) {
        $this->data_array['mail_siteupdates'] = $mailSiteUpdate;
    }
    /**
     * @param int 1 if the user accept to receive additional mails from the community, 0 if he does'nt
     */
    function setMailVA($mailVa) {
        $this->data_array['mail_va'] = $mailVa;
    }
    /**
     * @param int 0 or 1
     */
    function setStickyLogin($stickyLogin) {
        $this->data_array['sticky_login'] = $stickyLogin;
    }
    /**
     * @param int font size preference of the user
     */
    function setFontSize($fontSize) {
        $this->data_array['fontsize'] = $fontSize;
    }
    /**
     * @param string theme set in user's preferences
     */
    function setTheme($theme) {
        $this->data_array['theme'] = $theme;
    }
    /**
     * @param string the Status of the user
     * '0' = (number zero) special value for the site admin
     * 'N' = No Unix Account
     * 'A' = Active
     * 'S' = Suspended
     * 'D' = Deleted
     */
    function setUnixStatus($unixStatus) {
    	$allowedStatus = array(0 => true,
    	                       '0' => true,
    	                       'N' => true,
    	                       'A' => true,
    	                       'S' => true,
    	                       'D' => true);
        if (isset($allowedStatus[$unixStatus])) {
            $this->data_array['unix_status'] = $unixStatus;
        }
    }
    
    /**
     * @param Integer $unixUid Unix uid
     */
    function setUnixUid($unixUid) {
        $this->data_array['unix_uid'] = $unixUid;
    }
    
    /**
     * @param string unix box of the user
     */
    function setUnixBox($unixBox) {
        $this->data_array['unix_box'] = $unixBox;
    }
    /**
     * @param string authorized keys of the user
     */
    function setAuthorizedKeys($authorizedKeys) {
        $this->data_array['authorized_keys'] = $authorizedKeys;
    }
    /**
     * @param string resume of the user
     */
    function setPeopleResume($peopleResume) {
        $this->data_array['people_resume'] = $peopleResume;
    }
    /**
     * @param int 1 if the user skills are public, 0 otherwise
     */
    function setPeopleViewSkills($peopleViewSkills) {
        $this->data_array['people_view_skills'] = $peopleViewSkills;
    }
    /**
     * @param int ID of the language of the user
     */
    function setLanguageID($languageID) {
        $this->data_array['language_id'] = $languageID;
    }
    /**
     * @param string md5 of user pwd
     */
    function setUserPw($userPw) {
        $this->data_array['user_pw'] = $userPw;
    }
    
    /**
     * @param String User shell
     */
    function setShell($shell) {
        $this->data_array['shell'] = $shell;
    }
    
    function setLocale($locale) {
        $this->locale = $locale;
    }
    
    /**
     * @param int Timestamp of the last authentication success.
     */
    function setLastAuthSuccess($lastAuthSuccess) {
        $this->data_array['last_auth_success'] = $lastAuthSuccess;
    }
    /**
     * the previous authentication success (the one before last auth
     * success).
     * @param int Timestamp of the previous authentication success.
     */
    function setPreviousAuthSuccess($previousAuthSuccess) {
        $this->data_array['prev_auth_success'] = $previousAuthSuccess;
    }
    /**
     * @param int Timestamp of the last unsuccessful authencation attempt
     */
    function setLastAuthFailure($lastAuthFailure) {
        $this->data_array['last_auth_failure'] = $lastAuthFailure;
    }
    /**
     * @param int Number of authentication failure since the last success.
     */
    function setNbAuthFailure($nbAuthFailure) {
        $this->data_array['nb_auth_failure'] = $nbAuthFailure;
    }
    
    /**
     * Set clear password
     * 
     * @param  String $password
     */
    function setPassword($password) {
        $this->password = $password;
    }

    //
    // Preferences
    //
    
    protected function getPreferencesDao() {
        if (!$this->_preferencesdao) {
            $this->_preferencesdao = new UserPreferencesDao(CodendiDataAccess::instance());
        }
        return $this->_preferencesdao;
    }
    
    protected function getUserGroupDao() {
        if (!$this->_usergroupdao) {
            $this->_usergroupdao = new UserGroupDao(CodendiDataAccess::instance());
        }
        return $this->_usergroupdao;
    }
    
    /**
     * getPreference
     *
     * @param string $preference_name
     * @return string preference value or false if not set
     */
    function getPreference($preference_name) {
        if (!isset($this->_preferences[$preference_name])) {
            $this->_preferences[$preference_name] = false;
            if ($this->isLoggedIn()) {
                $dao =& $this->getPreferencesDao();
                $dar =& $dao->search($this->getId(), $preference_name);
                if ($row = $dar->getRow()) {
                    $this->_preferences[$preference_name] = $row['preference_value'];
                }
            }
        }
        return $this->_preferences[$preference_name];
    }
    
    /**
     * setPreference
     *
     * @param  string $preference_name
     * @param  string $preference_value
     * @return boolean
     */
    function setPreference($preference_name, $preference_value) {
        $this->_preferences[$preference_name] = false;
        if ($this->isLoggedIn()) {
            $dao =& $this->getPreferencesDao();
            if ($dao->set($this->getId(), $preference_name, $preference_value)) {
                $this->_preferences[$preference_name] = $preference_value;
                return true;
            }
        }
        return false;
    }
    
    /**
     * delPreference
     *
     * @param  string $preference_name  
     * @return boolean
     */
    function delPreference($preference_name) {
        $this->_preferences[$preference_name] = false;
        if ($this->isLoggedIn()) {
            $dao =& $this->getPreferencesDao();
            if ( ! $dao->delete($this->getId(), $preference_name)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * setSessionHash
     * @param $session_hash string
     */
     public function setSessionHash($session_hash) {
         $this->session_hash = $session_hash;
     }
     
     /**
      * getSessionHash
      * @return string
      */
     function getSessionHash() {
         return $this->session_hash;
     }
     
}

?>
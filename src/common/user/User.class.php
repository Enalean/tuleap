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
require_once('common/include/Recent_Element_Interface.class.php');
require_once('common/language/BaseLanguageFactory.class.php');
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
     * Site admin validated the account as active
     */
    const STATUS_VALIDATED = 'V';
    
    /**
     * Site admin validated the account as restricted
     */
    const STATUS_VALIDATED_RESTRICTED = 'W';
    
    /**
     * Name of the preference for lab features
     */
    const PREF_NAME_LAB_FEATURE = 'use_lab_features';
    
    /**
     * Pref for recent elements
     */
    const PREFERENCE_RECENT_ELEMENTS = 'recent_elements';
    
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
    protected $expiry_date;
    protected $has_avatar;

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
     * Special property to store CLEAR password
     * should be used only for update/creation purpose.
     */
    private $clear_password;
    
    /**
     * @var BaseLanguageFactory
     */
    protected $languageFactory;
    
    /**
     * @var BaseLanguage
     */
    protected $language;
    
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
        $this->expiry_date        = isset($row['expiry_date'])        ? $row['expiry_date']        : null;
        $this->has_avatar         = isset($row['has_avatar'])         ? $row['has_avatar']         : null;
        
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
     * Return associative array of data from db
     * 
     * @return array
     */
    function toRow() {
        return array(
            'user_id'            => $this->user_id,
            'user_name'          => $this->user_name,
            'email'              => $this->email,
            'user_pw'            => $this->user_pw,
            'realname'           => $this->realname,
            'register_purpose'   => $this->register_purpose,
            'status'             => $this->status,
            'shell'              => $this->shell,
            'unix_pw'            => $this->unix_pw,
            'unix_status'        => $this->unix_status,
            'unix_uid'           => $this->unix_uid,
            'unix_box'           => $this->unix_box,
            'ldap_id'            => $this->ldap_id,
            'add_date'           => $this->add_date,
            'confirm_hash'       => $this->confirm_hash,
            'mail_siteupdates'   => $this->mail_siteupdates,
            'mail_va'            => $this->mail_va,
            'sticky_login'       => $this->sticky_login,
            'authorized_keys'    => $this->authorized_keys,
            'email_new'          => $this->email_new,
            'people_view_skills' => $this->people_view_skills,
            'people_resume'      => $this->people_resume,
            'timezone'           => $this->timezone,
            'fontsize'           => $this->fontsize,
            'theme'              => $this->theme,
            'language_id'        => $this->language_id,
            'last_pwd_update'    => $this->last_pwd_update,
            'expiry_date'        => $this->expiry_date,
            'has_avatar'         => $this->has_avatar,
        );
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
    public function getUserGroupData() {
        if (!is_array($this->_group_data)) {
            if ($this->user_id) {
                $this->setUserGroupData($this->getUserGroupDao()->searchByUserId($this->user_id));
            }
        }
        return $this->_group_data;
    }

    /**
     * Set in cache the dataset of dynamic user group
     *
     * @param array $data
     */
    public function setUserGroupData($data) {
        $this->_group_data = array();
        foreach ($data as $row) {
            $this->_group_data[$row['group_id']] = $row;
        }
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
            if ($type === 0) { // Note: yes, this is '==='
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
                    case 'F2' : //forum admin
                        $is_member = ($group_perm['forum_flags'] == 2);
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
    
    /**
     * Check membership of the user to a specified ugroup
     * (call to old style ugroup_user_is_member in /src/www/project/admin ; here for unit tests purpose)
     *
     * @param int $ugroup_id  the id of the ugroup
     * @param int $group_id   the id of the project (is necessary for automatic project groups like project member, release admin, etc.)
     * @param int $tracker_id the id of the tracker (is necessary for trackers since the tracker admin role is different for each tracker.)
     *
     * @return boolean true if user is member of the ugroup, false otherwise.
     */
    public function isMemberOfUGroup($ugroup_id, $group_id, $tracker_id = 0) {
        return ugroup_user_is_member($this->getId(), $ugroup_id, $group_id, $tracker_id);
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
    
    public function getAllUgroups() {
        return $this->getUGroupDao()->searchByUserId($this->user_id);
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

    /**
     * Check if user can see the existance of given user.
     *
     * A user can see another one if
     * - the "querying" user is Active
     * - the "querying" user is Restricted AND the user to see is
     *   member of a project the restricted user is member too.
     *
     * @param User $user A user to test
     *
     * @return Boolean
     */
    public function canSee($user) {
        if ($this->isRestricted()) {
            $myGroupData   = $this->getUserGroupData();
            $userGroupData = $user->getUserGroupData();
            $commonGroups  = array_intersect_key($myGroupData, $userGroupData);
            return count($commonGroups) > 0;
        } else {
            return true;
        }
    }

    /**
     * User's language object corresponding to user's locale
     * 
     * @return BaseLanguage
     */
    public function getLanguage() {
        if (!$this->language) {
            $this->language = $this->getLanguageFactory()->getBaseLanguage($this->getLocale());
        }
        return $this->language;
    }
    
    //
    // Getter
    //

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
        return $this->ldap_id;
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
        return $this->unix_uid;
    }
    
    function getUnixHomeDir() {
        return $GLOBALS['homedir_prefix']."/".$this->getUserName();
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

    public function getAuthorizedKeysRaw() {
        return $this->getAuthorizedKeys();
    }

    public function getAuthorizedKeysArray() {
        return $this->getAuthorizedKeys(true);
    }

    /**
     *
     * @deprecated Flag methods are evil
     * @see User::getAuthorizedKeysRaw
     * @see User::getAuthorizedKeysArray
     *
     * @return string authorized keys of the user
     */
    function getAuthorizedKeys($split=false) {
        if ($split) {
            return array_filter(explode('###', $this->authorized_keys));
        } else {
            return $this->authorized_keys;
        }
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
        return $this->shell;
    }
    
    /**
     * Return the local of the user. Ex: en_US, fr_FR
     *
     * @return string
     */
    function getLocale() {
        return $this->locale;
    }

    /** 
     * @return String Clear user password
     */
    function getPassword() {
        return $this->clear_password; 
    }
    
   /** 
     * @return String Register purpose
     */
    function getRegisterPurpose() {
        return $this->register_purpose; 
    }
    
    /** 
     * @return String new email
     */
    function getNewMail() {
         return $this->email_new; 
    }
    
    /** 
     * @return String expiry date
     */
    function getExpiryDate() {
         return $this->expiry_date; 
    }
    
    /** 
     * @return String Confirm Hash
     */
    function getConfirmHash() {
         return $this->confirm_hash; 
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
     *
     * @param bool $return_all_data true if you want all groups data instead of only group_id (the later is the default)
     *
     * @return array groups id the user is member of
     */
    function getProjects($return_all_data = false) {
        $projects = array();
        foreach($this->getUserGroupDao()->searchActiveGroupsByUserId($this->user_id) as $data) {
            if ($return_all_data) {
                $projects[] = $data;
            } else {
                $projects[] = $data['group_id'];
            }
        }
        return $projects;
    }

    /**
     * Return all projects that a given member belongs to 
     * and also the projects that he is a member of its static ugroup
     * 
     * @return Array of Integer
     */
    public function getAllProjects() {
        $projects = array();
        $dar      = $this->getUGroupDao()->searchGroupByUserId($this->user_id);
        foreach ($dar as $row) {
            $projects[] = $row['group_id'];
        }
        $projects = array_unique(array_merge($projects, $this->getProjects()));
        return $projects;
    }

    /**
     * Wrapper for UGroupDao
     * 
     * @return UGroupDao
     */
    protected function getUGroupDao() {
        return new UGroupDao();
    }
    
    //
    // Setters
    //

    /**
     * @param int the ID of the user
     */
    function setId($id) {
        $this->id = $id;
        $this->user_id = $id;
    }

    /**
     * @param string the name of the user (aka login)
     */
    function setUserName($name) {
        $this->user_name = $name;
    }
    /**
     * @param string the real name of the user
     */
    function setRealName($name) {
        $this->realname = $name;
    }
    /**
     * @param string the email adress of the user
     */
    function setEmail($email) {
        $this->email = $email;
    }
    /**
     * @param string the Status of the user
     * 'A' = Active
     * 'R' = Restricted
     * 'D' = Deleted
     * 'S' = Suspended
     * 'P' = Pending
     */
    function setStatus($status) {
    	$allowedStatus = array('A' => true,
    	                       'R' => true,
    	                       'D' => true,
    	                       'S' => true,
    	                       'P' => true);
    	if (isset($allowedStatus[$status])) {
            $this->status = $status;
    	}
    }
    /**
     * @param string ldap identifier of the user
     */
    function setLdapId($ldapId) {
        $this->ldap_id = $ldapId;
    }    
    /**
     * @param string the registration date of the user (timestamp format)
     */
    function setAddDate($addDate) {
        $this->add_date = $addDate;
    }
    /**
     * @param string the timezone of the user (GMT, Europe/Paris, etc ...)
     */
    function setTimezone($timezone) {
        $this->timezone = $timezone;
    }
    /**
     * @param int 1 if the user accept to receive site mail updates, 0 if he does'nt
     */
    function setMailSiteUpdates($mailSiteUpdate) {
        $this->mail_siteupdates = $mailSiteUpdate;
    }
    /**
     * @param int 1 if the user accept to receive additional mails from the community, 0 if he does'nt
     */
    function setMailVA($mailVa) {
        $this->mail_va = $mailVa;
    }
    /**
     * @param int 0 or 1
     */
    function setStickyLogin($stickyLogin) {
        $this->sticky_login = $stickyLogin;
    }
    /**
     * @param int font size preference of the user
     */
    function setFontSize($fontSize) {
        $this->fontsize = $fontSize;
    }
    /**
     * @param string theme set in user's preferences
     */
    function setTheme($theme) {
        $this->theme = $theme;
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
            $this->unix_status = $unixStatus;
        }
    }
    
    /**
     * @param Integer $unixUid Unix uid
     */
    function setUnixUid($unixUid) {
        $this->unix_uid = $unixUid;
    }
    
    /**
     * @param string unix box of the user
     */
    function setUnixBox($unixBox) {
        $this->unix_box = $unixBox;
    }
    /**
     * @param string authorized keys of the user
     */
    function setAuthorizedKeys($authorizedKeys) {
        $this->authorized_keys = $authorizedKeys;
    }
    /**
     * @param string resume of the user
     */
    function setPeopleResume($peopleResume) {
        $this->people_resume = $peopleResume;
    }
    /**
     * @param int 1 if the user skills are public, 0 otherwise
     */
    function setPeopleViewSkills($peopleViewSkills) {
        $this->people_view_skills = $peopleViewSkills;
    }

    /**
     * @param string md5 of user pwd
     */
    function setUserPw($userPw) {
        $this->user_pw = $userPw;
    }
    
    /**
     * @param String User shell
     */
    function setShell($shell) {
        $this->shell = $shell;
    }

    /**
     * @param int ID of the language of the user
     */
    function setLanguageID($languageID) {
        $this->language_id = $languageID;
    }

    function setLocale($locale) {
        $this->locale = $locale;
    }

    function setLanguage(BaseLanguage $language) {
        $this->language = $language;
    }
    
    /**
     * Set clear password
     * 
     * @param  String $password
     */
    function setPassword($password) {
        $this->clear_password = $password;
    }
    
    /**
     * Set new Email
     * 
     * @param  String $new_email
     */
    function setNewMail($newEmail) {
        $this->email_new = $newEmail;
    }
    
    /**
     * Set Register Purpose
     * 
     * @param  String $regiter_purpose
     */
    function setRegisterPurpose($registerPurpose) {
        $this->register_purpose = $registerPurpose;
    }
    
    /**
     * Set Confirm Hash
     * 
     * @param  String $confirm_hash
     */
    function setConfirmHash($confirmHash) {
        $this->confirm_hash = $confirmHash;
    }
    
    /**
     * Set Expiry Date
     * 
     * @param  String $expiry date
     */
    function setExpiryDate($expiryDate) {
        $this->expiry_date = $expiryDate;
    }
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
            if (!$this->isAnonymous()) {
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
        if (!$this->isAnonymous()) {
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

     /**
      * Return all valid status
      *
      * @return Array
      */
     public static function getAllUnixStatus() {
         return array('N', 'A', 'S', 'D');
     }

     /**
      * Return all possible shells
      *
      * @return Array
      */
     public static function getAllUnixShells() {
         return file('/etc/shells', FILE_IGNORE_NEW_LINES);
     }

     /**
      * Return all "working" status (after validation step)
      *
      * @return Array
      */
     public static function getAllWorkingStatus() {
         return array(self::STATUS_ACTIVE, self::STATUS_RESTRICTED, self::STATUS_SUSPENDED, self::STATUS_DELETED);
     }

     /**
      * Say if the user has avatar
      *
      * @return bool
      */
     public function hasAvatar() {
         return $this->has_avatar;
     }

     /**
      * Set if the user has avatar
      *
      * @param bool $has_avatar true if the user has an avatar
      *
      * @return User for chaining methods
      */
     public function setHasAvatar($has_avatar = 1) {
         $this->has_avatar = ($has_avatar ? 1 : 0);
         return $this;
     }
     
     /**
      * Display the html code for this users's avatar
      *
      * @return string html
      */
     public function fetchHtmlAvatar($width = 50) {
         $purifier = Codendi_HTMLPurifier::instance();
         $html = '';
         $html .= '<div class="avatar" title="'. $purifier->purify($this->getRealName()) .'" style="width: '. ($width+2) .'px; height: '. ($width+2) .'px;">';
         if ($this->isAnonymous()) {
             $html .= '<img src="http://www.gravatar.com/avatar/'. md5($this->getEmail()) .'.jpg?s='. $width .'&amp;d=wavatar" />';
         } else {
             if ($this->hasAvatar()) {
                 $html .= '<img src="'. get_server_url() .'/users/'. $purifier->purify($this->getUserName()) .'/avatar.png" width="'. $width .'" />';
             }
         }
         $html .= '</div>';
         return $html;
     }

     /**
      * Lab features mode
      *
      * @return Boolean true if the user want lab features
      */
     public function useLabFeatures() {
         return $this->getPreference(self::PREF_NAME_LAB_FEATURE);
     }

     /**
      * (de)Activate lab features mode
      *
      * @param Boolean $toggle true if user wants to enable lab features
      *
      * @return void
      */
     public function setLabFeatures($toggle) {
         $this->setPreference(self::PREF_NAME_LAB_FEATURE, $toggle ? 1 : 0);
     }
      
     /**
      * Return true if user can do "$permissionType" on "$objectId"
      *
      * Note: this method is not useable in trackerV2 because it doesn't use "instances" parameter of getUgroups.
      *
      * @param String  $permissionType Permission nature
      * @param String  $objectId       Object to test
      * @param Integer $groupId        Project the object belongs to
      *
      * @return Boolean
      */
     public function hasPermission($permissionType, $objectId, $groupId) {
         return permission_is_authorized($permissionType, $objectId, $this->getId(), $groupId);
     }
    
    /**
     * Get the list of recent elements the user browsed
     * 
     * @return Array of Recent_Element_Interface
     */
    public function getRecentElements() {
        if ($recent_elements = $this->getPreference(self::PREFERENCE_RECENT_ELEMENTS)) {
            if ($recent_elements = unserialize($recent_elements)) {
                if (is_array($recent_elements)) {
                    return $recent_elements;
                }
            }
            //somthing wrong happen. Delete the preference
            $this->delPreference(self::PREFERENCE_RECENT_ELEMENTS);
        }
        return array();
    }
     
     /**
      * Add in user preference an element "recently accessed"
      * 
      * @param Recent_Element_Interface $element
      *
      * @return void
      */
     public function addRecentElement(Recent_Element_Interface $element) {
        $history = $this->getRecentElements();
        
        //search if the artifact is already in the history. If so remove it
        $found = $i = 0;
        reset($history);
        while (! $found && (list(, $v) = each($history))) {
            if ($element->getId() == $v['id']) {
                array_splice($history, $i, 1);
                $found = true;
            }
            ++$i;
        }
        if (! $found) {
            //drop the oldest one if >= 5
            while (count($history) >= 7) {
                array_pop($history);
            }
        }
        
        //add the new one
        array_unshift($history, array('id' => $element->getId(), 'link' => $element->fetchXRefLink()));
        
        //store
        $this->setPreference(self::PREFERENCE_RECENT_ELEMENTS, serialize($history));
     }
     
    /**
     * Wrapper for BaseLanguageFactory
     *
     * @return BaseLanguageFactory
     */
    protected function getLanguageFactory() {
        if (!isset($this->languageFactory)) {
            $this->languageFactory = new BaseLanguageFactory();
        }
        return $this->languageFactory;
    }
    
    /**
     * Set LanguageFactory
     * 
     * @param BaseLanguageFactory $languageFactory 
     */
    public function setLanguageFactory(BaseLanguageFactory $languageFactory) {
        $this->languageFactory = $languageFactory;
    }
}

?>

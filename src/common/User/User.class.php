<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermission;

/**
 *
 * User object
 *
 * Sets up database results and preferences for a user and abstracts this info
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PFUser implements PFO_User, IHaveAnSSHKey
{
    public const PREFERENCE_NAME_DISPLAY_USERS = 'username_display';
    /**
     * The user is active
     */
    public const STATUS_ACTIVE = 'A';

    /**
     * The user is restricted
     */
    public const STATUS_RESTRICTED = 'R';

    /**
     * The user is pending
     */
    public const STATUS_PENDING = 'P';

    /**
     * The user is suspended
     */
    public const STATUS_SUSPENDED = 'S';

    /**
     * The user is deleted
     */
    public const STATUS_DELETED = 'D';

    /**
     * Site admin validated the account as active
     */
    public const STATUS_VALIDATED = 'V';

    /**
     * Site admin validated the account as restricted
     */
    public const STATUS_VALIDATED_RESTRICTED = 'W';

    public const UNIX_STATUS_SITEADMIN_SPECIAL = '0';
    public const UNIX_STATUS_NO_UNIX_ACCOUNT   = 'N';
    public const UNIX_STATUS_ACTIVE            = 'A';
    public const UNIX_STATUS_SUSPENDED         = 'S';
    public const UNIX_STATUS_DELETED           = 'D';

    public const PREFERENCE_DISPLAY_DENSITY = 'display_density';
    public const DISPLAY_DENSITY_CONDENSED  = 'condensed';

    /**
     * Seperator for ssh key concatenation
     */
    public const SSH_KEY_SEPARATOR = '###';

    /**
     * Default avatar url
     */
    public const DEFAULT_AVATAR_URL = '/themes/common/images/avatar_default.png';

    public const DEFAULT_CSV_SEPARATOR = 'comma';

    public const DEFAULT_CSV_DATEFORMAT = 'month_day_year';

    public const EDITION_DEFAULT_FORMAT = 'user_edition_default_format';

    public const ACCESSIBILITY_MODE = 'accessibility_mode';

    public const PREFERENCE_NAME_CSV_SEPARATOR          = 'user_csv_separator';
    public const PREFERENCE_NAME_CSV_DATEFORMAT         = 'user_csv_dateformat';
    public const PREFERENCE_NAME_EDITION_DEFAULT_FORMAT = 'user_edition_default_format';

    public const PREFERENCE_CSV_COMMA     = 'comma';
    public const PREFERENCE_CSV_SEMICOLON = 'semicolon';
    public const PREFERENCE_CSV_TAB       = 'tab';

    public const PREFERENCE_CSV_MONTH_DAY_YEAR = 'month_day_year';
    public const PREFERENCE_CSV_DAY_MONTH_YEAR = 'day_month_year';

    public const PREFERENCE_EDITION_TEXT       = 'text';
    public const PREFERENCE_EDITION_HTML       = 'html';
    public const PREFERENCE_EDITION_COMMONMARK = 'commonmark';

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
    protected $timezone;
    protected $language_id;
    protected $last_pwd_update;
    protected $expiry_date;
    /**
     * @var 0|1
     */
    private $has_custom_avatar;

    /**
     * Keep super user info
     */
    private $is_super_user;

    /**
     * The locale of the user
     */
    protected $locale;


    /**
     * The preferences
     */
    private $preferences;

    /**
     * The dao used to retrieve preferences
     */
    public ?\Tuleap\User\StoreUserPreference $preferencesdao = null;

    /**
     * The dao used to retrieve user-group info
     */
    private $usergroupdao;

    /**
     * @var int|false
     */
    private $session_id;

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
     * @var string
     */
    private $avatar_url = '';
    private bool $is_first_timer;

    /**
     * Constructor
     *
     * You should not create new User directly.
     * Please use the UserManager instead to retrieve users.
     *
     * @param array the row corresponding to the user. default is null (anonymous)
     */
    public function __construct($row = null)
    {
        $this->is_super_user = null;
        $this->locale        = BaseLanguage::DEFAULT_LANG;
        $this->preferences   = [];

        $this->user_id           = isset($row['user_id'])            ? $row['user_id']            : 0;
        $this->user_name         = isset($row['user_name'])          ? $row['user_name']          : null;
        $this->email             = isset($row['email'])              ? $row['email']              : null;
        $this->user_pw           = isset($row['password'])           ? $row['password']           : null;
        $this->realname          = isset($row['realname'])           ? $row['realname']           : null;
        $this->register_purpose  = isset($row['register_purpose'])   ? $row['register_purpose']   : null;
        $this->status            = isset($row['status'])             ? $row['status']             : null;
        $this->shell             = isset($row['shell'])              ? $row['shell']              : null;
        $this->unix_pw           = isset($row['unix_pw'])            ? $row['unix_pw']            : null;
        $this->unix_status       = isset($row['unix_status'])        ? $row['unix_status']        : null;
        $this->unix_uid          = isset($row['unix_uid'])           ? $row['unix_uid']           : null;
        $this->unix_box          = isset($row['unix_box'])           ? $row['unix_box']           : null;
        $this->ldap_id           = isset($row['ldap_id'])            ? $row['ldap_id']            : null;
        $this->add_date          = isset($row['add_date'])           ? $row['add_date']           : null;
        $this->confirm_hash      = isset($row['confirm_hash'])       ? $row['confirm_hash']       : null;
        $this->mail_siteupdates  = isset($row['mail_siteupdates'])   ? $row['mail_siteupdates']   : null;
        $this->mail_va           = isset($row['mail_va'])            ? $row['mail_va']            : null;
        $this->sticky_login      = isset($row['sticky_login'])       ? $row['sticky_login']       : null;
        $this->authorized_keys   = isset($row['authorized_keys'])    ? $row['authorized_keys']    : null;
        $this->email_new         = isset($row['email_new'])          ? $row['email_new']          : null;
        $this->timezone          = isset($row['timezone'])           ? $row['timezone']           : null;
        $this->language_id       = isset($row['language_id'])        ? $row['language_id']        : null;
        $this->last_pwd_update   = isset($row['last_pwd_update'])    ? $row['last_pwd_update']    : null;
        $this->expiry_date       = isset($row['expiry_date'])        ? $row['expiry_date']        : null;
        $this->has_custom_avatar = ($row['has_custom_avatar'] ?? 0) ? 1 : 0;
        $this->is_first_timer    = (bool) ($row['is_first_timer'] ?? false);

        $this->id = $this->user_id;

        //set the locale
        if (! $this->language_id) {
            //Detect browser settings
            $accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            if (isset($GLOBALS['Language'])) {
                $this->locale = $GLOBALS['Language']->getLanguageFromAcceptLanguage($accept_language);
            }
        } else {
            $this->locale = $this->language_id;
        }

        $this->session_hash = false;
        $this->session_id   = false;
    }

    /**
     * Return associative array of data from db
     *
     * @return array
     */
    public function toRow()
    {
        return [
            'user_id'            => $this->user_id,
            'user_name'          => $this->user_name,
            'email'              => $this->email,
            'password'            => $this->user_pw,
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
            'timezone'           => $this->timezone,
            'language_id'        => $this->language_id,
            'last_pwd_update'    => $this->last_pwd_update,
            'expiry_date'        => $this->expiry_date,
            'has_custom_avatar'  => $this->has_custom_avatar,
        ];
    }

    /**
     * clear: clear the cached group data
     */
    public function clearGroupData()
    {
        unset($this->group_data);
        $this->group_data = null;
    }

    /**
     * clear: clear the cached tracker data
     */
    public function clearTrackerData()
    {
        unset($this->tracker_data);
        $this->tracker_data = null;
    }

    /**
     * group data row from db.
     * For each group_id (the user is part of) one array from the user_group table
     */
    private $group_data;
    public function getUserGroupData()
    {
        if (! is_array($this->group_data)) {
            if ($this->user_id) {
                $this->setUserGroupData($this->getUserGroupDao()->searchByUserId($this->user_id));
            }
        }
        return $this->group_data;
    }

    /**
     * Set in cache the dataset of dynamic user group
     *
     * @param array $data
     */
    public function setUserGroupData($data)
    {
        $this->group_data = [];
        foreach ($data as $row) {
            $this->group_data[$row['group_id']] = $row;
        }
    }

    /**
     * is this user member of group $group_id ??
     */
    public function isMember($group_id, $type = 0)
    {
        $group_data = $this->getUserGroupData();

        $is_member = false;

        if ($this->isSuperUser()) {
            $is_member = true;
        } elseif (isset($group_data[$group_id])) {
            if ($type === 0) { // Note: yes, this is '==='
                //We just want to know if the user is member of the group regardless the role
                $is_member = true;
            } else {
                //Lookup for the role defined by $type
                $group_perm = $group_data[$group_id];
                $type       = strtoupper($type);

                switch ($type) {
                    case 'A': //admin for this group
                        $is_member = ($group_perm['admin_flags'] && $group_perm['admin_flags'] === 'A');
                        break;
                    case 'F2': //forum admin
                        $is_member = ($group_perm['forum_flags'] == 2);
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
                    default: //fubar request
                        $is_member = false;
                }
            }
        }
        return $is_member;
    }

    /**
     * Check if user is admin of a Project
     * @param int $group_id
     * @return bool
     */
    public function isAdmin($group_id)
    {
        return $this->isMember($group_id, 'A');
    }

    private $cache_ugroup_membership = [];
    /**
     * Check membership of the user to a specified ugroup
     * (call to old style ugroup_user_is_member in /src/www/project/admin ; here for unit tests purpose)
     *
     * @param int $ugroup_id  the id of the ugroup
     * @param int $group_id   the id of the project (is necessary for automatic project groups like project member, release admin, etc.)
     * @param int $tracker_id the id of the tracker (is necessary for trackers since the tracker admin role is different for each tracker.)
     *
     * @return bool true if user is member of the ugroup, false otherwise.
     */
    public function isMemberOfUGroup($ugroup_id, $group_id, $tracker_id = 0)
    {
        if (! isset($this->cache_ugroup_membership[$ugroup_id][$group_id][$tracker_id])) {
            $is_member = ugroup_user_is_member($this->getId(), $ugroup_id, $group_id, $tracker_id);

            $this->cache_ugroup_membership[$ugroup_id][$group_id][$tracker_id] = $is_member;
        }

        return $this->cache_ugroup_membership[$ugroup_id][$group_id][$tracker_id];
    }

    public function setCacheUgroupMembership(int $ugroup_id, int $group_id, bool $is_member): void
    {
        $this->cache_ugroup_membership[$ugroup_id][$group_id][0] = $is_member;
    }

    public function isNone()
    {
        return $this->getId() == 100;
    }

    public function isAnonymous()
    {
        return $this->getId() == 0;
    }

    /**
     * is this user admin of the tracker group_artifact_id
     * @return bool
     */
    public function isTrackerAdmin($group_id, $group_artifact_id)
    {
        return ($this->getTrackerPerm($group_artifact_id) >= 2 || $this->isMember($group_id, 'A'));
    }

    /**
     * tracker permission data
     * for each group_artifact_id (the user is part of) one array from the artifact-perm table
     */
    private $tracker_data;
    protected function getTrackerData()
    {
        if (! $this->tracker_data) {
            $this->tracker_data = [];
            $id                 = (int) $this->user_id;
            //TODO: use a DAO (waiting for the next tracker api)
            $sql    = "SELECT group_artifact_id, perm_level
                    FROM artifact_perm WHERE user_id = $id";
            $db_res = db_query($sql);
            if (db_numrows($db_res) > 0) {
                while ($row = db_fetch_array($db_res)) {
                    $this->tracker_data[$row['group_artifact_id']] = $row;
                }
            }
        }
        return $this->tracker_data;
    }

    public function getTrackerPerm($group_artifact_id)
    {
        $tracker_data = $this->getTrackerData();
        return isset($tracker_data[$group_artifact_id]) ? $tracker_data[$group_artifact_id]['perm_level'] : 0;
    }

    public function isSuperUser(): bool
    {
        if ($this->is_super_user === null) {
            $this->is_super_user = false;
            $group_data          = $this->getUserGroupData();
            if ((isset($group_data[1]['admin_flags']) && $group_data[1]['admin_flags'] == 'A') || $this->doesUserHaveSuperUserPermissionDelegation()) {
                $this->is_super_user = true;
            }
        }
        return $this->is_super_user;
    }

    public function setIsSuperUser(bool $is_super_user): void
    {
        $this->is_super_user = $is_super_user;
    }

    public function getAllUgroups()
    {
        return $this->getUGroupDao()->searchByUserIdTakingAccountUserProjectMembership($this->user_id);
    }

    /**
     * @var array
     */
    private $ugroups;

    /**
     * @return array<int|string>
     * @psalm-return non-empty-array<string|int>
     */
    public function getUgroups($group_id, $instances): array
    {
        $hash = md5(serialize($instances));
        if (! isset($this->ugroups)) {
            $this->ugroups = [];
        }
        if (! isset($this->ugroups[$hash])) {
            $this->ugroups[$hash] = array_merge($this->getDynamicUgroups($group_id, $instances), $this->getStaticUgroups($group_id));
        }
        return $this->ugroups[$hash];
    }

    /**
     * @var array
     */
    private $static_ugroups;

    public function getStaticUgroups($group_id)
    {
        if (! isset($this->static_ugroups)) {
            $this->static_ugroups = [];
        }

        if (! isset($this->static_ugroups[$group_id])) {
            $this->static_ugroups[$group_id] = [];
            if (! $this->isSuperUser()) {
                $res = ugroup_db_list_all_ugroups_for_user($group_id, $this->id);
                while ($row = db_fetch_array($res)) {
                    $this->static_ugroups[$group_id][] = $row['ugroup_id'];
                }
            }
        }

        return $this->static_ugroups[$group_id];
    }

    /**
     * @var array
     */
    private $dynamics_ugroups;

    public function getDynamicUgroups($group_id, $instances)
    {
        $hash = md5(serialize($instances));
        if (! isset($this->dynamics_ugroups)) {
            $this->dynamics_ugroups = [];
        }
        if (! isset($this->dynamics_ugroups[$hash])) {
            $this->dynamics_ugroups[$hash] = ugroup_db_list_dynamic_ugroups_for_user($group_id, $instances, $this->id);
        }
        return $this->dynamics_ugroups[$hash];
    }

    /**
     * User's language object corresponding to user's locale
     *
     * @return BaseLanguage
     */
    public function getLanguage()
    {
        if (! $this->language) {
            $this->language = $this->getLanguageFactory()->getBaseLanguage($this->getLocale());
        }
        return $this->language;
    }

    // Getter
    public function getPublicProfileUrl()
    {
        return '/users/' . urlencode($this->getUserName());
    }

    /**
     * @return int|string the ID of the user
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @psalm-taint-escape file
     */
    public function getUserName(): string
    {
        return $this->user_name ?? '';
    }

    public function getRealName(): string
    {
        return $this->realname ?? 'User #' . $this->getId();
    }

    /**
     * @return string the email adress of the user
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function getEmailNew()
    {
        return $this->email_new;
    }

    /**
     * @return string the Status of the user
     * 'A' = Active
     * 'R' = Restricted
     * 'D' = Deleted
     * 'S' = Suspended
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string ldap identifier of the user
     */
    public function getLdapId()
    {
        return $this->ldap_id;
    }

    /**
     * @return bool
     */
    public function isLDAP()
    {
        return $this->getLdapId() != null;
    }

    /**
     * @return string the registration date of the user (timestamp format)
     */
    public function getAddDate()
    {
        return $this->add_date;
    }

    /**
     * @return string the last time the user has changed her password
     */
    public function getLastPwdUpdate()
    {
        return $this->last_pwd_update;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @return int 1 if the user accept to receive site mail updates, 0 if he does'nt
     */
    public function getMailSiteUpdates()
    {
        return $this->mail_siteupdates;
    }

    /**
     * @return int 1 if the user accept to receive additional mails from the community, 0 if he does'nt
     */
    public function getMailVA()
    {
        return $this->mail_va;
    }

    /**
     * @return int 0 or 1
     */
    public function getStickyLogin()
    {
        return $this->sticky_login;
    }

    /**
     * @return string the Status of the user
     * '0' = (number zero) special value for the site admin
     * 'N' = No Unix Account
     * 'A' = Active
     * 'S' = Suspended
     * 'D' = Deleted
     */
    public function getUnixStatus()
    {
        return $this->unix_status;
    }

    public function hasUnixAccount()
    {
        return $this->getUnixStatus() !== self::UNIX_STATUS_NO_UNIX_ACCOUNT;
    }

    public function getUnixUid()
    {
        return $this->unix_uid;
    }

    /**
     * @psalm-taint-escape shell
     */
    public function getUnixHomeDir(): string
    {
        $username = $this->getUserName();
        if (strpos($username, DIRECTORY_SEPARATOR) !== false) {
            throw new RuntimeException('$username is not expected to contain a directory separator, got ' . $username);
        }
        return ForgeConfig::get('homedir_prefix') . "/" . $username;
    }

    /**
     * Return user unix uid as it is on the unix system (with ID shift)
     * @return int
     */
    public function getSystemUnixUid()
    {
        return $this->getUnixUid() + ForgeConfig::get('unix_uid_add');
    }

    /**
     * Return user unix gid as it is on the unix system (with ID shift)
     * @return int
     */
    public function getSystemUnixGid()
    {
        return $this->getSystemUnixUid();
    }

    /**
     * @return string unix box of the user
     */
    public function getUnixBox()
    {
        return $this->unix_box;
    }

    /**
     * @return real unix ID of the user (not the one in the DB!)
     */
    public function getRealUnixUID()
    {
        $unix_id = $this->unix_uid + ForgeConfig::get('unix_uid_add');
        return $unix_id;
    }

    public function getAuthorizedKeysRaw(): ?string
    {
        return $this->authorized_keys;
    }

    /**
     * @psalm-mutation-free
     */
    public function getAuthorizedKeysArray(): array
    {
        return array_filter(explode(self::SSH_KEY_SEPARATOR, $this->authorized_keys ?? ''));
    }

    /**
     * @return int ID of the language of the user
     */
    public function getLanguageID()
    {
        return $this->language_id;
    }

    /**
     * @return string|null hash of user pwd
     */
    public function getUserPw(): ?string
    {
        return $this->user_pw;
    }

    /**
     * @return String User shell
     */
    public function getShell()
    {
        return $this->shell;
    }

    /**
     * Return the local of the user. Ex: en_US, fr_FR
     */
    public function getLocale(): string
    {
        return (string) $this->locale;
    }

    /**
     * Return an abreviated local of the user. Ex: en, fr
     *
     * @return string
     */
    public function getShortLocale()
    {
        return substr($this->locale, 0, 2);
    }

    public function getPassword(): ?ConcealedString
    {
        return $this->clear_password;
    }

   /**
     * @return String Register purpose
     */
    public function getRegisterPurpose()
    {
        return $this->register_purpose;
    }

    /**
     * @return String new email
     */
    public function getNewMail()
    {
         return $this->email_new;
    }

    /**
     * @return String expiry date
     */
    public function getExpiryDate()
    {
         return $this->expiry_date;
    }

    /**
     * @return String Confirm Hash
     */
    public function getConfirmHash()
    {
         return $this->confirm_hash;
    }

    /**
     * Return true if user is active or restricted.
     *
     * @return bool
     */
    public function isAlive()
    {
        return ! $this->isAnonymous() && ($this->isActive() || $this->isRestricted());
    }

    /**
     * isActive - test if the user is active or not, you'd better have good argument to use this instead of isAlive
     *
     * @see PFUser::isAlive()
     * @return bool true if the user is active, false otherwise
     */
    public function isActive()
    {
        return ($this->getStatus() == 'A');
    }

    /**
     * isRestricted - test if the user is restricted or not
     *
     * @return bool true if the user is restricted, false otherwise
     */
    public function isRestricted()
    {
        return (! $this->isAnonymous() && $this->getStatus() == 'R');
    }

    /**
     * isDeleted - test if the user is deleted or not
     *
     * @return bool true if the user is deleted, false otherwise
     */
    public function isDeleted()
    {
        return ($this->getStatus() == 'D');
    }

    /**
     * isSuspended - test if the user is suspended or not
     *
     * @return bool true if the user is suspended, false otherwise
     */
    public function isSuspended()
    {
        return ($this->getStatus() == 'S');
    }

    /**
     * hasActiveUnixAccount - test if the unix account of the user is active or not
     *
     * @return bool true if the unix account of the user is active, false otherwise
     */
    public function hasActiveUnixAccount()
    {
        return ($this->getUnixStatus() == 'A');
    }

    /**
     * hasSuspendedUnixAccount - test if the unix account of the user is suspended or not
     *
     * @return bool true if the unix account of the user is suspended, false otherwise
     */
    public function hasSuspendedUnixAccount()
    {
        return ($this->getUnixStatus() == 'S');
    }

    /**
     * hasDeletedUnixAccount - test if the unix account of the user is deleted or not
     *
     * @return bool true if the unix account of the user is deleted, false otherwise
     */
    public function hasDeletedUnixAccount()
    {
        return ($this->getUnixStatus() == 'D');
    }

    /**
     * hasNoUnixAccount - test if the user doesn't have a unix account
     *
     * @return bool true if the user doesn't have a unix account, false otherwise
     */
    public function hasNoUnixAccount()
    {
        return ($this->getUnixStatus() == 'N');
    }

    /**
     *
     * @param bool $return_all_data true if you want all groups data instead of only group_id (the later is the default)
     *
     * @return array groups id the user is member of
     */
    public function getProjects($return_all_data = false)
    {
        $projects = [];
        foreach ($this->getUserGroupDao()->searchActiveGroupsByUserId($this->user_id) as $data) {
            if (
                $data['access'] === Project::ACCESS_PRIVATE_WO_RESTRICTED &&
                ForgeConfig::areRestrictedUsersAllowed() &&
                $this->isRestricted()
            ) {
                continue;
            }
            if ($return_all_data) {
                $projects[] = $data;
            } else {
                $projects[] = $data['group_id'];
            }
        }
        return $projects;
    }

    /**
     * Should be an alias of getProjects()
     *
     * However we need real objects. Maybe it would be great to force getProjects to return POPO...
     *
     * @return Project[]
     */
    public function getGroups()
    {
        $projects = [];
        foreach ($this->getProjects() as $group_id) {
            $projects[] = ProjectManager::instance()->getProject($group_id);
        }
        return $projects;
    }

    /**
     * Return all projects that a given member belongs to
     * and also the projects that he is a member of its static ugroup
     *
     * @return Array of Integer
     */
    public function getAllProjects()
    {
        $projects = [];
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
    protected function getUGroupDao()
    {
        return new UGroupDao();
    }

    // Setters
    /**
     * @param int the ID of the user
     */
    public function setId($id)
    {
        $this->id      = $id;
        $this->user_id = $id;
    }

    /**
     * @param string the name of the user (aka login)
     */
    public function setUserName($name)
    {
        $this->user_name = $name;
    }

    /**
     * @param string the real name of the user
     */
    public function setRealName($name)
    {
        $this->realname = $name;
    }

    /**
     * @param string the email adress of the user
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setEmailNew($email)
    {
        $this->email_new = $email;
    }

    /**
     * @param string the Status of the user
     * 'A' = Active
     * 'R' = Restricted
     * 'D' = Deleted
     * 'S' = Suspended
     * 'P' = Pending
     */
    public function setStatus($status)
    {
        $allowedStatus = ['A' => true,
            'R' => true,
            'D' => true,
            'S' => true,
            'P' => true,
        ];
        if (isset($allowedStatus[$status])) {
            $this->status = $status;
        }
    }

    /**
     * @param string ldap identifier of the user
     */
    public function setLdapId($ldapId)
    {
        $this->ldap_id = $ldapId;
    }

    /**
     * @param string the registration date of the user (timestamp format)
     */
    public function setAddDate($addDate)
    {
        $this->add_date = $addDate;
    }

    /**
     * @param string the timezone of the user (GMT, Europe/Paris, etc ...)
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @param int 1 if the user accept to receive site mail updates, 0 if he does'nt
     */
    public function setMailSiteUpdates($mailSiteUpdate)
    {
        $this->mail_siteupdates = $mailSiteUpdate;
    }

    /**
     * @param int 1 if the user accept to receive additional mails from the community, 0 if he does'nt
     */
    public function setMailVA($mailVa)
    {
        $this->mail_va = $mailVa;
    }

    /**
     * @param int 0 or 1
     */
    public function setStickyLogin($stickyLogin)
    {
        $this->sticky_login = $stickyLogin;
    }

    /**
     * @param string the Status of the user
     * '0' = (number zero) special value for the site admin
     * 'N' = No Unix Account
     * 'A' = Active
     * 'S' = Suspended
     * 'D' = Deleted
     */
    public function setUnixStatus($unixStatus)
    {
        $allowedStatus = [0 => true,
            '0' => true,
            'N' => true,
            'A' => true,
            'S' => true,
            'D' => true,
        ];
        if (isset($allowedStatus[$unixStatus])) {
            $this->unix_status = $unixStatus;
        }
    }

    /**
     * @param int $unixUid Unix uid
     */
    public function setUnixUid($unixUid)
    {
        $this->unix_uid = $unixUid;
    }

    /**
     * @param string unix box of the user
     */
    public function setUnixBox($unixBox)
    {
        $this->unix_box = $unixBox;
    }

    /**
     * @param string authorized keys of the user
     */
    public function setAuthorizedKeys($authorizedKeys)
    {
        $this->authorized_keys = $authorizedKeys;
    }

    /**
     * @param hash of user pwd
     */
    public function setUserPw($userPw)
    {
        $this->user_pw = $userPw;
    }

    /**
     * @param String User shell
     */
    public function setShell($shell)
    {
        $this->shell = $shell;
    }

    /**
     * @param int ID of the language of the user
     */
    public function setLanguageID($languageID)
    {
        $this->language_id = $languageID;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setLanguage(BaseLanguage $language)
    {
        $this->language = $language;
    }

    /**
     * Set clear password
     */
    public function setPassword(ConcealedString $password): void
    {
        $this->clear_password = $password;
    }

    /**
     * Set new Email
     *
     * @param  string $newEmail
     */
    public function setNewMail($newEmail)
    {
        $this->email_new = $newEmail;
    }

    /**
     * Set Register Purpose
     *
     * @param  string $registerPurpose
     */
    public function setRegisterPurpose($registerPurpose)
    {
        $this->register_purpose = $registerPurpose;
    }

    /**
     * Set Confirm Hash
     *
     * @param  string $confirmHash
     */
    public function setConfirmHash($confirmHash)
    {
        $this->confirm_hash = $confirmHash;
    }

    public function clearConfirmHash()
    {
        $this->confirm_hash = '';
    }

    /**
     * Set Expiry Date
     *
     * @param  string|int $expiryDate
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiry_date = $expiryDate;
    }
    // Preferences
    protected function getPreferencesDao()
    {
        if (! $this->preferencesdao) {
            $this->preferencesdao = new UserPreferencesDao();
        }
        return $this->preferencesdao;
    }

    protected function getUserGroupDao()
    {
        if (! $this->usergroupdao) {
            $this->usergroupdao = new UserGroupDao();
        }
        return $this->usergroupdao;
    }

    /**
     * getPreference
     *
     * @param string $preference_name
     * @return false|string preference value or false if not set
     */
    public function getPreference($preference_name)
    {
        if (! isset($this->preferences[$preference_name])) {
            $this->preferences[$preference_name] = false;
            if (! $this->isAnonymous()) {
                $row = $this->getPreferencesDao()->search(
                    $this->getId(),
                    $preference_name
                );

                if (isset($row['preference_value'])) {
                    $this->preferences[$preference_name] = $row['preference_value'];
                }
            }
        }
        return $this->preferences[$preference_name];
    }

    /**
     * setPreference
     *
     * @param  string $preference_name
     * @param  string $preference_value
     */
    public function setPreference($preference_name, $preference_value): void
    {
        $this->preferences[$preference_name] = false;
        if (! $this->isAnonymous()) {
            $dao = $this->getPreferencesDao();
            $dao->set($this->getId(), $preference_name, $preference_value);
            $this->preferences[$preference_name] = $preference_value;
        }
    }

    /**
     * Toggle the preference
     *
     * If the user has not set the preference yet, then set the default value
     *
     * Example:
     * $user->togglePreference('cardwall', 'display_avatars', 'display_usernames');
     *
     * @param string $pref_name
     * @param mixed  $default_value
     * @param mixed  $alternate_value
     */
    public function togglePreference($pref_name, $default_value, $alternate_value)
    {
        $current_preference = $this->getPreference($pref_name);
        $new_preference     = $default_value;

        if ($this->shouldUseAlternatePreferenceValue($current_preference, $new_preference)) {
            $new_preference = $alternate_value;
        }
        $this->setPreference($pref_name, $new_preference);
    }

    private function shouldUseAlternatePreferenceValue($current_preference, $new_preference)
    {
        return ($this->hasUserSetAPreference($current_preference)
            && $this->arePreferencesTheSame($current_preference, $new_preference));
    }

    private function hasUserSetAPreference($current_preference)
    {
        return $current_preference !== false;
    }

    private function arePreferencesTheSame($current_preference, $new_preference)
    {
        return $new_preference == $current_preference;
    }

    /**
     * delPreference
     *
     * @param  string $preference_name
     */
    public function delPreference($preference_name): void
    {
        $this->preferences[$preference_name] = false;
        if (! $this->isAnonymous()) {
            $dao = $this->getPreferencesDao();
            $dao->delete($this->getId(), $preference_name);
        }
    }

    /**
     * setSessionHash
     * @param $session_hash string
     */
    public function setSessionHash($session_hash)
    {
        $this->session_hash = $session_hash;
    }

     /**
      * getSessionHash
      * @return string
      */
    public function getSessionHash()
    {
        return $this->session_hash;
    }

    public function setSessionId($session_id)
    {
        $this->session_id = $session_id;
    }

    /**
     * @return int|false
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

     /**
      * Return all valid status
      *
      * @return Array
      */
    public static function getAllUnixStatus()
    {
        return ['N', 'A', 'S', 'D'];
    }

     /**
      * Return all possible shells
      *
      * @return Array
      */
    public static function getAllUnixShells()
    {
        return file('/etc/shells', FILE_IGNORE_NEW_LINES);
    }

     /**
      * Return all "working" status (after validation step)
      *
      * @return Array
      */
    public static function getAllWorkingStatus()
    {
        return [self::STATUS_ACTIVE, self::STATUS_RESTRICTED, self::STATUS_SUSPENDED, self::STATUS_DELETED];
    }

     /**
      * Say if the user has avatar
      *
      * @return bool
      */
    public function hasAvatar()
    {
        return ! empty($this->getRealName());
    }

    public function hasCustomAvatar(): bool
    {
        return (bool) $this->has_custom_avatar;
    }

    public function setHasCustomAvatar(bool $has_custom_avatar): void
    {
        $this->has_custom_avatar = ($has_custom_avatar ? 1 : 0);
    }

     /**
      * Display the html code for this users's avatar
      *
      * @return string html
      */
    public function fetchHtmlAvatar()
    {
        $purifier    = Codendi_HTMLPurifier::instance();
        $user_helper = new UserHelper();

        $title   = $purifier->purify($user_helper->getDisplayNameFromUser($this));
        $user_id = $this->getId();

        $html = '<div class="avatar"
                        title="' . $title . '"
                        data-user-id = "' . $user_id . '"
                    >';

        $url = $this->getAvatarUrl();

        if ($url) {
            $alternate_text = $purifier->purify(_('User avatar'));
            $html          .= '<img src="' . $url . '" alt="' . $alternate_text . '" />';
        }

        $html .= '</div>';
        return $html;
    }

     /**
      * Return the user avatar url
      * @return string url
      */
    public function getAvatarUrl()
    {
        if ($this->avatar_url) {
            return $this->avatar_url;
        }

        $avatar_url = self::DEFAULT_AVATAR_URL;

        if (! $this->isAnonymous() && $this->hasAvatar()) {
            $avatar_file_path = $this->getAvatarFilePath();
            if (is_file($avatar_file_path)) {
                $avatar_url = '/users/' . urlencode($this->getUserName()) . '/avatar-' . hash_file('sha256', $avatar_file_path) . '.png';
            } else {
                $avatar_url = '/users/' . urlencode($this->getUserName()) . '/avatar.png';
            }
        }

        $this->avatar_url = \Tuleap\ServerHostname::HTTPSUrl() . $avatar_url;

        return $this->avatar_url;
    }

    public function setAvatarUrl(string $url): void
    {
        $this->avatar_url = $url;
    }

    /**
     * @return string
     */
    public function getAvatarFilePath()
    {
        return ForgeConfig::get('sys_avatar_path') .
            DIRECTORY_SEPARATOR .
            substr($this->user_id, -2, 1) .
            DIRECTORY_SEPARATOR .
            substr($this->user_id, -1, 1) .
            DIRECTORY_SEPARATOR .
            $this->user_id .
            DIRECTORY_SEPARATOR .
            'avatar';
    }

     /**
      * Return true if user can do "$permissionType" on "$objectId"
      *
      * Note: this method is not useable in trackerV2 because it doesn't use "instances" parameter of getUgroups.
      *
      * @param String  $permissionType Permission nature
      * @param String  $objectId       Object to test
      * @param int $groupId Project the object belongs to
      *
      * @return bool
      */
    public function hasPermission($permissionType, $objectId, $groupId)
    {
        return permission_is_authorized($permissionType, $objectId, $this->getId(), $groupId);
    }

    /**
     * Wrapper for BaseLanguageFactory
     *
     * @return BaseLanguageFactory
     */
    protected function getLanguageFactory()
    {
        if (! isset($this->languageFactory)) {
            $this->languageFactory = new BaseLanguageFactory();
        }
        return $this->languageFactory;
    }

    /**
     * Set LanguageFactory
     *
     */
    public function setLanguageFactory(BaseLanguageFactory $languageFactory)
    {
        $this->languageFactory = $languageFactory;
    }

    public function __toString(): string
    {
        return "User #" . $this->getId();
    }

    /**
     * protected for testing purpose
     */
    protected function getPermissionManager()
    {
        return new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao());
    }

    /**
     * protected for testing purpose
     */
    protected function doesUserHaveSuperUserPermissionDelegation()
    {
        return $this->getPermissionManager()->doesUserHavePermission($this, new SiteAdministratorPermission());
    }

    public function isFirstTimer(): bool
    {
        return $this->is_first_timer;
    }

    public function setIsFirstTimer(bool $value): void
    {
        $this->is_first_timer = $value;
    }
}

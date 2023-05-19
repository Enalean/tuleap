<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\User\BuildDisplayName;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserHelper implements BuildDisplayName
{
    public const PREFERENCES_NAME_AND_LOGIN = 0;
    public const PREFERENCES_LOGIN_AND_NAME = 1;
    public const PREFERENCES_LOGIN          = 2;
    public const PREFERENCES_REAL_NAME      = 3;

    public $_username_display; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public $_cache_by_id; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public $_cache_by_username; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public $_userdao; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * Constructor
     * @todo make it protected (singleton powaaa)
     */
    public function __construct()
    {
        $this->_username_display  = $this->_getCurrentUserUsernameDisplayPreference();
        $this->_cache_by_id       = [];
        $this->_cache_by_username = [];
        $this->_userdao           = $this->_getuserDao();
    }

    protected static $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    /**
     *
     * @return UserHelper
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $c               = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    public function _getCurrentUserUsernameDisplayPreference() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getUserManager()->getCurrentUser()->getPreference("username_display");
    }

    public function _getUserManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return UserManager::instance();
    }

    /**
     * getDisplayName
     *
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * Username display preference:
     *  1: user_name (realname)
     *  2: user_name
     *  3: realname
     *  4: realname (user_name)
     *
     */
    public function getDisplayName(string $user_name, string $realname): string
    {
        $name = '';
        switch ($this->_username_display) {
            case self::PREFERENCES_LOGIN_AND_NAME:
                $name = "$user_name ($realname)";
                break;
            case self::PREFERENCES_LOGIN:
                $name = $user_name;
                break;
            case self::PREFERENCES_REAL_NAME:
                $name = $realname;
                break;
            default:
                $name = "$realname ($user_name)";
                break;
        }
        return $name;
    }

    /**
     * getDisplayNameSQLQuery
     *
     * Get SQL statement for extracting display name from the "user" table, according to the user prefs
     *
     * Username display preference: see getDisplayName()
     *
     */
    public function getDisplayNameSQLQuery()
    {
        $name = '';
        switch ($this->_username_display) {
            case self::PREFERENCES_LOGIN_AND_NAME:
                $name = "CONCAT(user.user_name,' (',user.realname,')') AS full_name";
                break;
            case self::PREFERENCES_LOGIN:
                $name = 'user.user_name AS full_name';
                break;
            case self::PREFERENCES_REAL_NAME:
                $name = 'user.realname AS full_name';
                break;
            default:
                $name = "CONCAT(user.realname,' (',user.user_name,')') AS full_name";
                break;
        }
        return $name;
    }

    /**
     * Get SQL statement for filtering according to users' names
     *
     * @param string $by a string containing comma-separated users' names or a pattern of user name.
     *
     * @return string
     */
    public function getUserFilter($by)
    {
        $filter       = '';
        $user_manager = $this->_getUserManager();
        $usersIds     = $user_manager->getUserIdsList($by);
        if (count($usersIds) > 0) {
            $user_ids_escaped = $this->_getUserDao()->getDa()->escapeIntImplode($usersIds);
            $filter          .= ' AND user.user_id IN (' . $user_ids_escaped . ')';
        } else {
            $by      = $this->_getUserDao()->getDa()->quoteLikeValueSurround($by);
            $filter .= ' AND user.user_name LIKE ' . $by;
        }
        return $filter;
    }

    /**
     * getDisplayNameSQLOrder
     *
     * Get SQL statement for sorting display name from the "user" table, according to the user prefs
     *
     * Username display preference: see getDisplayName()
     *
     */
    public function getDisplayNameSQLOrder()
    {
        $order = '';
        switch ($this->_username_display) {
            case self::PREFERENCES_LOGIN_AND_NAME:
                $order = "user.user_name";
                break;
            case self::PREFERENCES_LOGIN:
                $order = 'user.user_name';
                break;
            case self::PREFERENCES_REAL_NAME:
                $order = 'user.realname';
                break;
            default:
                $order = "user.realname";
                break;
        }
        return $order;
    }

    /**
     * getDisplayNameFromUser
     *
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * @param PFUser the user to display
     *
     * @return ?string the display name of the user $user or null if $user is null
     *
     * @see getDisplayName
     *
     * @template T of PFUser|null
     * @psalm-param T $user
     * @psalm-return (T is PFUser ? string : null)
     */
    public function getDisplayNameFromUser($user)
    {
        if ($user === null) {
            return null;
        }
        if ($user->isNone()) {
            return $user->getUserName();
        }
        return $this->getDisplayName($user->getUserName(), $user->getRealName());
    }

    /**
     * getDisplayNameFromUserId
     *
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * @param int the user_id of the user to display
     * @see getDisplayName
     * @return string|null
     */
    public function getDisplayNameFromUserId($user_id)
    {
        $um = $this->_getUserManager();
        if ($um->isUserLoadedById($user_id)) {
            $user    = $um->getUserById($user_id);
            $display = $this->getDisplayNameFromUser($user);
        } else {
            if (! isset($this->_cache_by_id[$user_id])) {
                $this->_cache_by_id[$user_id] = $GLOBALS['Language']->getText('global', 'none');
                $row                          = $this->_userdao->searchByUserId($user_id);
                if ($row !== null) {
                    $this->_cache_by_id[$user_id]                = $this->getDisplayName($row['user_name'], $row['realname']);
                    $this->_cache_by_username[$row['user_name']] = $this->_cache_by_id[$user_id];
                }
            }
            $display = $this->_cache_by_id[$user_id];
        }
        return $display;
    }

    /**
     * getDisplayNameFromUserName
     *
     * Get user name from Codendi login, according to the user prefs: Codendi login or Real name
     *
     * @param string the user_name of the user to display
     * @see getDisplayName
     */
    public function getDisplayNameFromUserName($user_name)
    {
        if ($this->_isUserNameNone($user_name)) {
            return $user_name;
        } else {
            $um = $this->_getUserManager();
            if ($um->isUserLoadedByUserName($user_name)) {
                $user    = $um->getUserByUserName($user_name);
                $display = $this->getDisplayNameFromUser($user);
            } else {
                if (! isset($this->_cache_by_username[$user_name])) {
                    $row = $this->_userdao->searchByUserName($user_name);
                    if ($row !== null) {
                        $this->_cache_by_id[$row['user_id']]         = $this->getDisplayName($row['user_name'], $row['realname']);
                        $this->_cache_by_username[$row['user_name']] = $this->_cache_by_id[$row['user_id']];
                    } else {
                        $this->_cache_by_username[$user_name] = $user_name;
                    }
                }
                $display = $this->_cache_by_username[$user_name];
            }
        }
        return $display;
    }

    /**
     * Get a link on user profile with name according to user prefs.
     *
     * @param int $user_id User id
     *
     * @return string
     */
    public function getLinkOnUserFromUserId($user_id)
    {
        return $this->getLinkOnUser($this->_getUserManager()->getUserById($user_id));
    }

    /**
     * Get a link on user profile with name according to user prefs.
     *
     * @param PFUser $user User object
     *
     * @return String
     */
    public function getLinkOnUser(PFUser $user)
    {
        $hp = Codendi_HTMLPurifier::instance();
        if ($user && ! $user->isNone()) {
            return '<a href="' . $this->getUserUrl($user) . '">' . $hp->purify($this->getDisplayNameFromUser($user), CODENDI_PURIFIER_CONVERT_HTML) . '</a>';
        } else {
            $username = $user ? $user->getUserName() : '';
            return $hp->purify($username, CODENDI_PURIFIER_CONVERT_HTML);
        }
    }

    public function getUserUrl(PFUser $user)
    {
        return "/users/" . urlencode($user->getUserName());
    }

    public function getAbsoluteUserURL(PFUser $user): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . $this->getUserUrl($user);
    }

    /**
     * _isUserNameNone
     *
     * @param string  $user_name
     */
    public function _isUserNameNone($user_name) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $user_name == $GLOBALS['Language']->getText('global', 'none');
    }

    /**
     * Returns the user dao
     */
    public function _getUserDao() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return new UserDao();
    }
}

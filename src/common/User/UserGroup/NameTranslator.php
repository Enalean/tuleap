<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\User\UserGroup;

use ForgeConfig;

class NameTranslator
{
    public const  string NOBODY                     = 'ugroup_nobody_name_key';
    public const  string ANON                       = 'ugroup_anonymous_users_name_key';
    public const  string AUTHENTICATED              = 'ugroup_authenticated_users_name_key';
    public const  string PROJECT_ADMINS             = 'ugroup_project_admins_name_key';
    public const  string REGISTERED                 = 'ugroup_registered_users_name_key';
    public const  string PROJECT_MEMBERS            = 'ugroup_project_members_name_key';
    private const string WIKI_ADMINS                = 'ugroup_wiki_admin_name_key';
    private const string FORUM_ADMINS               = 'ugroup_forum_admin_name_key';
    private const string FILE_ADMINS                = 'ugroup_file_manager_admin_name_key';
    private const string TV3_TRACKER_ADMINS         = 'ugroup_tracker_admins_name_key';
    public const  string CONFIG_REGISTERED_LABEL    = 'ugroup_registered_label';
    public const  string CONFIG_AUTHENTICATED_LABEL = 'ugroup_authenticated_label';

    public static $names = [
        self::NOBODY,
        self::ANON,
        self::AUTHENTICATED,
        self::REGISTERED,
        self::PROJECT_MEMBERS,
        self::PROJECT_ADMINS,
    ];

    /**
     * @param string $name
     */
    public static function getUserGroupDisplayName($name): string
    {
        switch ($name) {
            case self::NOBODY:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_nobody');
            case self::ANON:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_anonymous_users');
            case self::AUTHENTICATED:
                $label = ForgeConfig::get(self::CONFIG_AUTHENTICATED_LABEL);
                if (! $label) {
                    $label = $GLOBALS['Language']->getText('project_ugroup', 'ugroup_authenticated_users');
                }

                return $label;
            case self::REGISTERED:
                $label = ForgeConfig::get(self::CONFIG_REGISTERED_LABEL);
                if (! $label) {
                    $label = _('Registered users');
                }

                return $label;
            case self::PROJECT_MEMBERS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members');
            case self::PROJECT_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins');
            case self::WIKI_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_wiki_admins');
            case self::FORUM_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_forum_admins');
            default:
                return self::getUserGroupDisplayKey((string) $name);
        }
    }

    public static function getUserGroupDisplayKey(string $name): string
    {
        switch ($name) {
            case self::ANON:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_anonymous_users_name_key');
            case self::REGISTERED:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_registered_users_name_key');
            case self::PROJECT_MEMBERS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members_name_key');
            case self::PROJECT_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins_name_key');
            case self::AUTHENTICATED:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_authenticated_users_name_key');
            case self::FILE_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_file_manager_admin_name_key');
            case self::WIKI_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_wiki_admin_name_key');
            case self::TV3_TRACKER_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_tracker_admins_name_key');
            case self::FORUM_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_forum_admin_name_key');
            case self::NOBODY:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_nobody_name_key');
            default:
                return $name;
        }
    }
}

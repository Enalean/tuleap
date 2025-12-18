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

class DescriptionTranslator
{
    private const  string NOBODY          = 'ugroup_nobody_desc_key';
    private const  string ANON            = 'ugroup_anonymous_users_desc_key';
    private const  string AUTHENTICATED   = 'ugroup_authenticated_users_desc_key';
    private const  string PROJECT_ADMINS  = 'ugroup_project_admins_desc_key';
    private const  string REGISTERED      = 'ugroup_registered_users_desc_key';
    private const  string PROJECT_MEMBERS = 'ugroup_project_members_desc_key';
    private const  string WIKI_ADMINS     = 'ugroup_wiki_admin_desc_key';
    private const  string FILE_ADMINS     = 'ugroup_file_manager_admin_desc_key';

    public static function getUserGroupDisplayDescription(string $desc): string
    {
        switch ($desc) {
            case self::ANON:
                return $GLOBALS['Language']->getOverridableText('project_ugroup', 'ugroup_anonymous_users_desc_key');
            case self::REGISTERED:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_registered_users_desc_key');
            case self::PROJECT_MEMBERS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members_desc_key');
            case self::PROJECT_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins_desc_key');
            case self::AUTHENTICATED:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_authenticated_users_desc_key');
            case self::FILE_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_file_manager_admin_desc_key');
            case self::WIKI_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_wiki_admin_desc_key');
            case self::NOBODY:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_nobody_desc_key');
            default:
                return $desc;
        }
    }
}

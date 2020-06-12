<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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
    private const  NOBODY             = 'ugroup_nobody_desc_key';
    private const  ANON               = 'ugroup_anonymous_users_desc_key';
    private const  AUTHENTICATED      = 'ugroup_authenticated_users_desc_key';
    private const  PROJECT_ADMINS     = 'ugroup_project_admins_desc_key';
    private const  REGISTERED         = 'ugroup_registered_users_desc_key';
    private const  PROJECT_MEMBERS    = 'ugroup_project_members_desc_key';
    private const  WIKI_ADMINS        = 'ugroup_wiki_admin_desc_key';
    private const  FORUM_ADMINS       = 'ugroup_forum_admin_desc_key';
    private const  NEWS_WRITER        = 'ugroup_news_writer_desc_key';
    private const  NEWS_ADMIN         = 'ugroup_news_admin_desc_key';
    private const  FILE_ADMINS        = 'ugroup_file_manager_admin_desc_key';
    private const  TV3_TRACKER_ADMINS = 'ugroup_tracker_admins_desc_key';

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
            case self::TV3_TRACKER_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_tracker_admins_desc_key');
            case self::FORUM_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_forum_admin_desc_key');
            case self::NEWS_ADMIN:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_news_admin_desc_key');
            case self::NEWS_WRITER:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_news_writer_desc_key');
            case self::NOBODY:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_nobody_desc_key');
            default:
                return $desc;
        }
    }
}

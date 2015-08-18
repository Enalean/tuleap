<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ForgeUGroup implements User_UGroup {

    const NOBODY          = 'ugroup_nobody_name_key';
    const ANON            = 'ugroup_anonymous_users_name_key';
    const AUTHENTICATED   = 'ugroup_authenticated_users_name_key';
    const REGISTERED      = 'ugroup_registered_users_name_key';
    const PROJECT_MEMBERS = 'ugroup_project_members_name_key';
    const PROJECT_ADMINS  = 'ugroup_project_admins_name_key';

    const CONFIG_AUTHENTICATED_LABEL = 'ugroup_authenticated_label';
    const CONFIG_REGISTERED_LABEL    = 'ugroup_registered_label';

    private $id;

    private $name;

    private $description;

    public static $names = array(
        self::NOBODY,
        self::ANON,
        self::AUTHENTICATED,
        self::REGISTERED,
        self::PROJECT_MEMBERS,
        self::PROJECT_ADMINS,
    );

    public function __construct($id, $name, $description) {
        $this->id          = $id;
        $this->name        = self::getUserGroupDisplayName($name);
        $this->description = $description;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    static public function getUserGroupDisplayName($name) {
        switch ($name) {
            case self::NOBODY:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_nobody');
            case self::ANON:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_anonymous_users');
            case self::AUTHENTICATED:
                $label = ForgeConfig::get(self::CONFIG_AUTHENTICATED_LABEL);
                if ($label == false) {
                    $label = $GLOBALS['Language']->getText('project_ugroup', 'ugroup_authenticated_users');
                }
                return $label;
            case self::REGISTERED:
                $label = ForgeConfig::get(self::CONFIG_REGISTERED_LABEL);
                if ($label == false) {
                    $label = $GLOBALS['Language']->getText('project_ugroup', 'ugroup_registered_users');
                }
                return $label;
            case self::PROJECT_MEMBERS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members');
            case self::PROJECT_ADMINS:
                return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins');
            default :
                return util_translate_name_ugroup($name);
        }
    }

}
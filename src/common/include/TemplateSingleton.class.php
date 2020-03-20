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


/**
 *  TemplateSingleton object for Project Templates
 */
class TemplateSingleton
{
  // simply containing the
    public $data_array = array();

    public const PROJECT      = 1;
    public const TEMPLATE     = 2;
    public const TEST_PROJECT = 3;

    public function __construct()
    {
        $this->update();
    }

    public static function instance()
    {
        static $template_instance;
        if (!$template_instance) {
            $template_instance = new TemplateSingleton();
        }
        return $template_instance;
    }

    public function getLabel($proj_type)
    {
        switch ($proj_type) {
            case self::TEST_PROJECT:
                return $GLOBALS['Language']->getText('include_common_template', 'test_project');
            case self::TEMPLATE:
                return $GLOBALS['Language']->getText('include_common_template', 'template');
            case self::PROJECT:
            default:
                return $GLOBALS['Language']->getText('include_common_template', 'project');
        }
    }

    public function update()
    {
        $db_res = db_query("SELECT * FROM group_type");
        $this->data_array = array();
        $rows = db_numrows($db_res);
        for ($i = 0; $i < $rows; $i++) {
            $this->data_array[db_result($db_res, $i, 'type_id')] = db_result($db_res, $i, 'name');
        }
    }

    public function isTemplate($id)
    {
        return ($id == self::TEMPLATE);
    }

    public function isProject($id)
    {
        return ($id == self::PROJECT);
    }

    public function isTestProject($id)
    {
        return ($id == self::TEST_PROJECT);
    }

    public function showTypeBox($name = 'group_type', $checked_val = 'xzxz')
    {
        $localizedTypes = array();
        foreach (array_keys($this->data_array) as $type_id) {
            $localizedTypes[] = $this->getLabel($type_id);
        }
        return html_build_select_box_from_arrays(array_keys($this->data_array), $localizedTypes, $name, $checked_val, false);
    }

    public function getLocalizedTypes()
    {
        $localized = array();
        foreach (array_keys($this->data_array) as $type_id) {
            $localized[$type_id] = $this->getLabel($type_id);
        }

        return $localized;
    }

    public function getTemplates()
    {
        $db_templates = db_query("SELECT group_id,group_name,unix_group_name,short_description,register_time FROM groups WHERE type='2' and status IN ('A','s')");
        return $db_templates;
    }
}

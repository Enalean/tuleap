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
    var $data_array = array();

    public const PROJECT      = 1;
    public const TEMPLATE     = 2;
    public const TEST_PROJECT = 3;

    function __construct()
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

    function getLabel($proj_type)
    {
        return $GLOBALS['Language']->getText('include_common_template', $this->data_array[$proj_type]);
    }

    function update()
    {
        $db_res=db_query("SELECT * FROM group_type");
        $this->data_array=array();
        $rows=db_numrows($db_res);
        for ($i=0; $i<$rows; $i++) {
            $this->data_array[db_result($db_res, $i, 'type_id')] = db_result($db_res, $i, 'name');
        }
    }

    function isTemplate($id)
    {
        return ($id == self::TEMPLATE);
    }

    function isProject($id)
    {
        return ($id == self::PROJECT);
    }

    function isTestProject($id)
    {
        return ($id == self::TEST_PROJECT);
    }

    function showTypeBox($name = 'group_type', $checked_val = 'xzxz')
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

    function getTemplates()
    {
        $db_templates = db_query("SELECT group_id,group_name,unix_group_name,short_description,register_time FROM groups WHERE type='2' and status IN ('A','s')");
        return $db_templates;
    }
}

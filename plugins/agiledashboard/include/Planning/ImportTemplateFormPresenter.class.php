<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

class Planning_ImportTemplateFormPresenter
{

    public const TULEAP_TEMPLATE_URL = __DIR__ . '/../../resources/templates/scrum_dashboard_template.xml';

    public $group_id;


    public function __construct($group_id)
    {
        $this->group_id = $group_id;
    }

    public function adminTitle()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'Admin');
    }

    public function importTemplateHeader()
    {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_template');
    }

    public function btnSubmit()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'btn_import');
    }

    public function importInstructions()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_instructions', array(self::TULEAP_TEMPLATE_URL));
    }

    public function importNotes()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_notes');
    }
}

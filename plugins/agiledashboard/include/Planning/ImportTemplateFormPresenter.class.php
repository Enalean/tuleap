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

    public int $group_id;


    public function __construct(
        private readonly Project $project,
        private readonly \Tuleap\Kanban\SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
        $this->group_id = (int) $this->project->getID();
    }

    public function adminTitle()
    {
        if ($this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($this->project)) {
            return dgettext('tuleap-agiledashboard', 'Backlog administration');
        }

        return dgettext('tuleap-agiledashboard', 'Agile Dashboard Administration');
    }

    public function importTemplateHeader()
    {
        return dgettext('tuleap-agiledashboard', 'Import a configuration from a template file');
    }

    public function btnSubmit()
    {
        return dgettext('tuleap-agiledashboard', 'Import');
    }

    public function importInstructions()
    {
        return $GLOBALS['Language']->getOverridableText('plugin_agiledashboard', 'import_instructions', [self::TULEAP_TEMPLATE_URL]);
    }

    public function importNotes()
    {
        return dgettext('tuleap-agiledashboard', 'Note:<br>Importing a template will create new trackers. The import will not work if any existing tracker match those in the template');
    }
}

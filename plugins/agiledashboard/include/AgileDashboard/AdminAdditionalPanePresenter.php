<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
namespace Tuleap\AgileDashboard;

class AdminAdditionalPanePresenter
{

    public $project_id;
    private $title;
    private $output;
    private $additional_panes;

    public function __construct(
        $project_id,
        $title,
        $output,
        array $additional_panes
    ) {
        $this->project_id       = $project_id;
        $this->title            = $title;
        $this->output           = $output;
        $this->additional_panes = $additional_panes;
    }

    public function pane_title()
    {
        return $title;
    }

    public function config_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'config_title');
    }

    public function kanban_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_label');
    }

    public function scrum_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_label');
    }

    public function additional_content()
    {
        return $this->output;
    }

    public function no_content()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_not_found');
    }

    public function additional_panes()
    {
        return array_values($this->additional_panes);
    }
}

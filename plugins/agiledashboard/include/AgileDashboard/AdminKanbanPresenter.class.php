<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
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

class AdminKanbanPresenter
{

    /** @var int */
    public $group_id;

    /** @var bool */
    public $kanban_activated;

    /** @var string */
    public $kanban_title;

    /** @var bool */
    public $has_kanban;

    /**
     * @var bool
     */
    public $can_burnup_be_configured;

    public function __construct(
        $group_id,
        $kanban_activated,
        $kanban_title,
        $has_kanban,
        bool $can_burnup_be_configured
    ) {
        $this->group_id                 = $group_id;
        $this->kanban_activated         = $kanban_activated;
        $this->kanban_title             = $kanban_title;
        $this->has_kanban               = $has_kanban;
        $this->can_burnup_be_configured = $can_burnup_be_configured;
    }

    public function config_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'config_title');
    }

    public function config_submit_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'config_submit_label');
    }

    public function general_settings_section()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'general_settings_section');
    }

    public function activate_kanban_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'activate_kanban_label');
    }

    public function title_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title');
    }

    public function title_label_help()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title_kanban_help');
    }

    public function kanban_activated_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_activated_label');
    }

    public function kanban_not_activated_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_not_activated_label');
    }

    public function first_kanban_will_be_created()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'first_kanban_will_be_created');
    }

    public function token()
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }
}

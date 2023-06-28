<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

    /** @var bool */
    public $has_kanban;

    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_scrum_accessible;

    public function __construct(
        $group_id,
        $kanban_activated,
        $has_kanban,
        bool $is_scrum_accessible,
    ) {
        $this->group_id            = $group_id;
        $this->kanban_activated    = $kanban_activated;
        $this->has_kanban          = $has_kanban;
        $this->is_scrum_accessible = $is_scrum_accessible;
    }

    public function config_submit_label()
    {
        return dgettext('tuleap-agiledashboard', 'Save');
    }

    public function general_settings_section()
    {
        return dgettext('tuleap-agiledashboard', 'General settings');
    }

    public function activate_kanban_label()
    {
        return dgettext('tuleap-agiledashboard', 'Activate Kanban');
    }

    public function kanban_activated_label()
    {
        return dgettext('tuleap-agiledashboard', 'Kanban is currently active.');
    }

    public function kanban_not_activated_label()
    {
        return dgettext('tuleap-agiledashboard', 'Kanban is not currently active.');
    }

    public function first_kanban_will_be_created()
    {
        return dgettext('tuleap-agiledashboard', 'A first Kanban will be created during the activation. This operation can take a few seconds.');
    }

    public function token()
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }
}

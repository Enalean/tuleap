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

declare(strict_types=1);

namespace Tuleap\Kanban;

use CSRFSynchronizerToken;

class AdminKanbanPresenter
{
    public readonly string $config_submit_label;
    public readonly string $activate_kanban_label;
    public readonly string $general_settings_section;
    public readonly string $kanban_activated_label;
    public readonly string $kanban_not_activated_label;
    public readonly string $first_kanban_will_be_created;

    public function __construct(
        public readonly int $group_id,
        public readonly bool $kanban_activated,
        public readonly bool $has_kanban,
        public readonly bool $is_scrum_accessible,
    ) {
        $this->config_submit_label          = dgettext('tuleap-kanban', 'Save');
        $this->general_settings_section     = dgettext('tuleap-kanban', 'General settings');
        $this->activate_kanban_label        = dgettext('tuleap-kanban', 'Activate Kanban');
        $this->kanban_activated_label       = dgettext('tuleap-kanban', 'Kanban is currently active.');
        $this->kanban_not_activated_label   = dgettext('tuleap-kanban', 'Kanban is not currently active.');
        $this->first_kanban_will_be_created = dgettext('tuleap-kanban', 'A first Kanban will be created during the activation. This operation can take a few seconds.');
    }

    public function token(): string
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }
}

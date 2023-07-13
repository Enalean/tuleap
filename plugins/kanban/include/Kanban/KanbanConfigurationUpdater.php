<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use AgileDashboardConfigurationResponse;
use Codendi_Request;
use Tuleap\Kanban\Legacy\LegacyKanbanActivator;
use Tuleap\Kanban\Legacy\LegacyKanbanDeactivator;
use Tuleap\Kanban\Legacy\LegacyKanbanRetriever;

final class KanbanConfigurationUpdater
{
    private int $project_id;

    public function __construct(
        private readonly Codendi_Request $request,
        private readonly LegacyKanbanRetriever $legacy_kanban_retriever,
        private readonly LegacyKanbanActivator $legacy_kanban_activator,
        private readonly LegacyKanbanDeactivator $legacy_kanban_deactivator,
        private readonly AgileDashboardConfigurationResponse $response,
        private readonly FirstKanbanCreator $first_kanban_creator,
    ) {
        $this->project_id = (int) $this->request->get('group_id');
    }

    public function updateConfiguration(): void
    {
        $kanban_was_activated = $this->legacy_kanban_retriever->isKanbanActivated($this->project_id);
        $kanban_is_activated  = (bool) $this->request->get('activate-kanban');

        if ($kanban_is_activated) {
            if (! $kanban_was_activated) {
                $this->legacy_kanban_activator->activateKanban($this->project_id);
                $this->first_kanban_creator->createFirstKanban($this->request->getCurrentUser());
                $this->response->kanbanActivated();
            }
        } else {
            if ($kanban_was_activated) {
                $this->legacy_kanban_deactivator->deactivateKanban($this->project_id);
            }
        }

        $this->response->kanbanConfigurationUpdated();
    }
}

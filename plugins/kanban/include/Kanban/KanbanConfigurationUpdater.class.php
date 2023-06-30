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

use AgileDashboard_ConfigurationManager;
use AgileDashboardConfigurationResponse;
use Codendi_Request;

final class KanbanConfigurationUpdater
{
    private int $project_id;

    public function __construct(
        private readonly Codendi_Request $request,
        private readonly AgileDashboard_ConfigurationManager $config_manager,
        private readonly AgileDashboardConfigurationResponse $response,
        private readonly FirstKanbanCreator $first_kanban_creator,
    ) {
        $this->project_id = (int) $this->request->get('group_id');
    }

    public function updateConfiguration(): void
    {
        $kanban_is_activated = $this->getActivatedKanban();

        $this->config_manager->updateConfiguration(
            $this->project_id,
            $this->config_manager->scrumIsActivatedForProject($this->request->getProject()),
            $kanban_is_activated,
            $this->config_manager->getScrumTitle($this->project_id),
        );

        if ($kanban_is_activated) {
            $this->first_kanban_creator->createFirstKanban($this->request->getCurrentUser());
        }

        $this->response->kanbanConfigurationUpdated();
    }

    private function getActivatedKanban(): bool
    {
        $kanban_was_activated = $this->config_manager->kanbanIsActivatedForProject($this->project_id);
        $kanban_is_activated  = (bool) $this->request->get('activate-kanban');

        if ($kanban_is_activated && ! $kanban_was_activated) {
            $this->response->kanbanActivated();
        }

        return $kanban_is_activated;
    }
}

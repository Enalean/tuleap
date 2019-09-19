<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboardKanbanConfigurationUpdater
{

    /** @var int */
    private $project_id;

    /** @var Codendi_Request */
    private $request;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var AgileDashboardConfigurationResponse */
    private $response;

    /** @var AgileDashboard_FirstKanbanCreator */
    private $first_kanban_creator;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_ConfigurationManager $config_manager,
        AgileDashboardConfigurationResponse $response,
        AgileDashboard_FirstKanbanCreator $first_kanban_creator
    ) {
        $this->request              = $request;
        $this->project_id           = (int) $this->request->get('group_id');
        $this->config_manager       = $config_manager;
        $this->response             = $response;
        $this->first_kanban_creator = $first_kanban_creator;
    }

    public function updateConfiguration()
    {
        if (! $this->request->exist('kanban-title-admin')) {
            $this->response->missingKanbanTitle();
            return;
        }

        $kanban_is_activated = $this->getActivatedKanban();

        $this->config_manager->updateConfiguration(
            $this->project_id,
            $this->config_manager->scrumIsActivatedForProject($this->project_id),
            $kanban_is_activated,
            $this->config_manager->getScrumTitle($this->project_id),
            $this->getKanbanTitle()
        );

        if ($kanban_is_activated) {
            $this->first_kanban_creator->createFirstKanban($this->request->getCurrentUser());
        }

        $this->response->kanbanConfigurationUpdated();
    }

    private function getActivatedKanban()
    {
        $kanban_was_activated = $this->config_manager->kanbanIsActivatedForProject($this->project_id);
        $kanban_is_activated  = $this->request->get('activate-kanban');

        if ($kanban_is_activated && ! $kanban_was_activated) {
             $this->response->kanbanActivated();
        }

        return $kanban_is_activated;
    }

    private function getKanbanTitle()
    {
        $old_kanban_title = $this->config_manager->getKanbanTitle($this->project_id);
        $kanban_title     = trim($this->request->get('kanban-title-admin'));

        if ($kanban_title !== $old_kanban_title) {
            $this->response->kanbanTitleChanged();
        }

        if ($kanban_title == '') {
            $this->response->emptyKanbanTitle();
            $kanban_title = $old_kanban_title;
        }

        return $kanban_title;
    }
}

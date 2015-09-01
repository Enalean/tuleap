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

class AgileDashboardScrumConfigurationUpdater {

    /** @var int */
    private $project_id;

    /** @var Codendi_Request */
    private $request;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var AgileDashboardConfigurationResponse */
    private $response;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_ConfigurationManager $config_manager,
        AgileDashboardConfigurationResponse $response
    ) {
        $this->request        = $request;
        $this->project_id     = (int) $this->request->get('group_id');
        $this->config_manager = $config_manager;
        $this->response       = $response;
    }

    public function updateConfiguration() {
        if (! $this->request->exist('scrum-title-admin')) {
            $this->response->missingScrumTitle();
            return;
        }

        $this->config_manager->updateConfiguration(
            $this->project_id,
            $this->getActivatedScrum(),
            $this->config_manager->kanbanIsActivatedForProject($this->project_id),
            $this->getScrumTitle(),
            $this->config_manager->getKanbanTitle($this->project_id)
        );

        $this->response->scrumConfigurationUpdated();
    }

    private function getActivatedScrum() {
        $scrum_was_activated = $this->config_manager->scrumIsActivatedForProject($this->project_id);
        $scrum_is_activated  = $this->request->get('activate-scrum');

        if ($scrum_is_activated && ! $scrum_was_activated) {
            $this->response->scrumActivated();
        }

        return $scrum_is_activated;
    }

    private function getScrumTitle() {
        $old_scrum_title = $this->config_manager->getScrumTitle($this->project_id);
        $scrum_title     = trim($this->request->get('scrum-title-admin'));

        if ($scrum_title !== $old_scrum_title) {
            $this->response->scrumTitleChanged();
        }

        if ($scrum_title == '') {
            $this->response->emptyScrumTitle();
            $scrum_title = $old_scrum_title;
        }

        return $scrum_title;
    }
}

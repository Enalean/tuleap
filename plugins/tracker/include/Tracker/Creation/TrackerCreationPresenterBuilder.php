<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Project;
use ProjectManager;
use TrackerDao;

class TrackerCreationPresenterBuilder
{
    /**
     * @var TrackerDao
     */
    private $tracker_dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(ProjectManager $project_manager, TrackerDao $tracker_dao)
    {
        $this->project_manager = $project_manager;
        $this->tracker_dao     = $tracker_dao;
    }

    public function build(\Project $current_project, \CSRFSynchronizerToken $csrf): TrackerCreationPresenter
    {
        $project_templates = [];
        foreach ($this->project_manager->getSiteTemplates() as $project) {
            $tracker_list = $this->tracker_dao->searchByGroupId($project->getID());
            if (! $tracker_list || count($tracker_list) === 0) {
                continue;
            }

            $formatted_tracker = [];
            foreach ($tracker_list as $tracker) {
                $formatted_tracker[] = new TrackerTemplatesRepresentation($tracker['id'], $tracker['name']);
            }

            $project_templates[] = new ProjectTemplatesRepresentation(
                $project,
                $formatted_tracker
            );
        }

        $existing_trackers = $this->getExistingTrackersNamesAndShortnamesInProject($current_project);

        return new TrackerCreationPresenter($project_templates, $existing_trackers, $current_project, $csrf);
    }

    private function getExistingTrackersNamesAndShortnamesInProject(Project $project): array
    {
        $trackers = $this->tracker_dao->searchByGroupId($project->getID());
        $existing_trackers = [
            'names'      => [],
            'shortnames' => []
        ];

        if ($trackers === false) {
            return $existing_trackers;
        }

        foreach ($trackers as $tracker) {
            $existing_trackers['names'][] = strtolower($tracker['name']);
            $existing_trackers['shortnames'][] = strtolower($tracker['item_name']);
        }

        return $existing_trackers;
    }
}

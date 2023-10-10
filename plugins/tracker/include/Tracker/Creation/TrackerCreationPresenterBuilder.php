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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use Project;
use ProjectManager;
use TrackerDao;
use TrackerFactory;
use Tuleap\Tracker\Creation\JiraImporter\JiraRunner;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\TrackerColor;

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

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var DefaultTemplatesCollectionBuilder
     */
    private $default_templates_collection_builder;
    /**
     * @var PendingJiraImportDao
     */
    private $pending_jira_import_dao;
    /**
     * @var JiraRunner
     */
    private $jira_runner;

    public function __construct(
        ProjectManager $project_manager,
        TrackerDao $tracker_dao,
        PendingJiraImportDao $pending_jira_import_dao,
        TrackerFactory $tracker_factory,
        DefaultTemplatesCollectionBuilder $default_templates_collection_builder,
        JiraRunner $jira_runner,
    ) {
        $this->project_manager                      = $project_manager;
        $this->tracker_dao                          = $tracker_dao;
        $this->tracker_factory                      = $tracker_factory;
        $this->default_templates_collection_builder = $default_templates_collection_builder;
        $this->pending_jira_import_dao              = $pending_jira_import_dao;
        $this->jira_runner                          = $jira_runner;
    }

    public function build(\Project $current_project, \CSRFSynchronizerToken $csrf, \PFUser $user): TrackerCreationPresenter
    {
        $project_templates = [];

        $default_templates = $this->default_templates_collection_builder->build()->getSortedDefaultTemplatesRepresentations();
        foreach ($this->project_manager->getSiteTemplates() as $project) {
            $tracker_list = $this->tracker_dao->searchByGroupId($project->getID());
            if (! $tracker_list || count($tracker_list) === 0) {
                continue;
            }

            $formatted_tracker = [];
            foreach ($tracker_list as $tracker) {
                $formatted_tracker[] = new TrackerTemplatesRepresentation($tracker['id'], $tracker['name'], $tracker['description'], $tracker['color']);
            }

            $project_templates[] = new ProjectTemplatesRepresentation($project, $formatted_tracker);
        }

        $existing_trackers            = $this->getExistingTrackersNamesAndShortnamesInProject($current_project);
        $trackers_from_other_projects = $this->getTrackersUserIsAdmin($user);

        $tracker_colors = [
            'colors_names' => TrackerColor::COLOR_NAMES,
            'default_color' => TrackerColor::default()->getName(),
        ];

        $display_jira_importer = $this->jira_runner->canBeProcessedAsynchronously();

        return new TrackerCreationPresenter(
            $default_templates,
            $project_templates,
            $existing_trackers,
            $trackers_from_other_projects,
            $tracker_colors,
            $current_project,
            $csrf,
            $display_jira_importer,
            $this->areThereTV3($current_project)
        );
    }

    private function areThereTV3(Project $project): bool
    {
        if ($project->usesService('tracker')) {
            $tv3_factory = new \ArtifactTypeFactory($project);
            $tv3         = $tv3_factory->getArtifactTypes();

            return ! empty($tv3);
        }

        return false;
    }

    private function getExistingTrackersNamesAndShortnamesInProject(Project $project): array
    {
        $trackers          = $this->tracker_dao->searchByGroupId($project->getID());
        $existing_trackers = [
            'names'      => [],
            'shortnames' => [],
        ];

        if ($trackers === false) {
            return $existing_trackers;
        }

        foreach ($trackers as $tracker) {
            $existing_trackers['names'][]      = strtolower($tracker['name']);
            $existing_trackers['shortnames'][] = strtolower($tracker['item_name']);
        }

        foreach ($this->pending_jira_import_dao->searchByProjectId((int) $project->getID()) as $row) {
            $existing_trackers['names'][]      = strtolower($row['tracker_name']);
            $existing_trackers['shortnames'][] = strtolower($row['tracker_shortname']);
        }

        return $existing_trackers;
    }

    private function getTrackersUserIsAdmin(\PFUser $user): array
    {
        $projects_ids = $user->getProjects();
        $trackers     = [];

        if (count($projects_ids) === 0) {
            return $trackers;
        }

        foreach ($projects_ids as $id) {
            $trackers_user_can_view = $this->tracker_factory->getTrackersByProjectIdUserCanView($id, $user);
            $trackers_base_info     = [];

            foreach ($trackers_user_can_view as $tracker) {
                if (! $tracker->userIsAdmin($user)) {
                    continue;
                }

                $trackers_base_info[] = [
                    'id' => $tracker->getId(),
                    'name' => $tracker->getName(),
                    'description' => $tracker->getDescription(),
                    'tlp_color' => $tracker->getColor()->getName(),
                ];
            }

            if (count($trackers_base_info) === 0) {
                continue;
            }

            $project = $this->project_manager->getProject($id);

            $trackers[] = [
                'id' => $id,
                'name' => $project->getPublicName(),
                'trackers' => $trackers_base_info,
            ];
        }

        return $trackers;
    }
}

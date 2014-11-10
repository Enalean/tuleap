<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class ElasticSearch_SearchResultProjectsFacetCollection {

    const IDENTIFIER            = 'group_id';
    const USER_PROJECTS_IDS_KEY = 'user_projects_ids';

    /**
     * @var array
     */
    private $option_groups = array();

    /**
     * @var array
     */
    private $submitted_facets;

    /**
     * @var array
     */
    private $user_projects_ids;

    /**
     * @var int
     */
    private $count_my_projects = 0;


    public function __construct(array $results, ProjectManager $project_manager, array $submitted_facets, array $user_projects_ids) {
        $this->submitted_facets  = $submitted_facets;
        $this->user_projects_ids = $user_projects_ids;

        $projects = $this->getProjectsValues($results, $project_manager);

        $this->createSpecificProjectsOptionGroup();
        $this->createProjectsOptionGroup($projects);
    }

    private function getProjectsValues(array $results, ProjectManager $project_manager) {
        $projects = array();

        if (isset($results['terms'])) {
            foreach ($results['terms'] as $result) {
                $project = $project_manager->getProject($result['term']);

                if ($project && !$project->isError()) {
                    $checked = isset($this->submitted_facets[self::IDENTIFIER]) && in_array($project->getGroupId(), $this->submitted_facets[self::IDENTIFIER]);
                    $projects[] = new ElasticSearch_SearchResultProjectsFacet($project, $result['count'], $checked);

                    $this->incrementCountMyProjects($project, $result['count']);
                }
            }
        }

        usort($projects, array($this, 'sortProjects'));

        return $projects;
    }

    private function sortProjects($a, $b) {
        return strcasecmp($a->label, $b->label);
    }

    private function incrementCountMyProjects($project, $count) {
        if (in_array($project->getGroupId(), $this->user_projects_ids)) {
            $this->count_my_projects += $count;
        }
    }

    private function createSpecificProjectsOptionGroup() {
        $is_my_projects_checked = isset($this->submitted_facets[self::IDENTIFIER]) && in_array(self::USER_PROJECTS_IDS_KEY, $this->submitted_facets[self::IDENTIFIER]);

        $my_projects = new ElasticSearch_SearchResultMyProjectsFacet(
            $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_my_project_label'),
            $this->count_my_projects,
            $is_my_projects_checked
        );

        $specific_projects_option_group = new ElasticSearch_SearchResultProjectsGroupFacet(
            $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_project_specific_projects_option_group'),
            array($my_projects)
        );

        $this->option_groups[] = $specific_projects_option_group;
    }

    private function createProjectsOptionGroup($projects) {
        $projects_option_group = new ElasticSearch_SearchResultProjectsGroupFacet(
            $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_project_projects_option_group'),
            $projects
        );

        $this->option_groups[] = $projects_option_group;
    }

    public function identifier() {
        return self::IDENTIFIER;
    }

    public function option_groups() {
        return $this->option_groups;
    }

    public function placeholder() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_project_placeholder');
    }
}

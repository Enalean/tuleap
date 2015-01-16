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
     * @var ProjectManager
     */
    private $project_manager;

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

    /**
     * @var array
     */
    private $matching_project_ids = array();


    public function __construct(PFUser $user, array $results, ProjectManager $project_manager, array $submitted_facets, array $user_projects_ids) {
        $this->submitted_facets  = $submitted_facets;
        $this->user_projects_ids = $user_projects_ids;
        $this->project_manager   = $project_manager;

        $matching_projects = $this->getMatchingProjectsValues($results);
        $other_projects    = $this->getOtherProjectsValues($user, $submitted_facets);

        $this->createSpecificProjectsOptionGroup();
        $this->createMatchingProjectsOptionGroup($matching_projects);
        $this->createOtherProjectsOptionGroup($other_projects);
    }

    private function getMatchingProjectsValues(array $results) {
        $projects = array();
        if (isset($results['terms'])) {
            foreach ($results['terms'] as $result) {
                $project = $this->project_manager->getProject($result['term']);

                if ($this->isProjectValid($project)) {
                    $checked = isset($this->submitted_facets[self::IDENTIFIER]) && in_array($project->getGroupId(), $this->submitted_facets[self::IDENTIFIER]);
                    $projects[] = new ElasticSearch_SearchResultProjectsFacet($project, $result['count'], $checked);

                    $this->incrementCountMyProjects($project, $result['count']);
                }
                $this->matching_project_ids[] = $project->getID();
            }
        }

        usort($projects, array($this, 'sortProjects'));

        return $projects;
    }

    private function getOtherProjectsValues(PFUser $user,  $submitted_facets) {
        $other_projects        = array();
        $all_projects_for_user = $this->project_manager->getAllMyAndPublicProjects($user);

        foreach ($all_projects_for_user as $project_id => $project) {
            if ($this->isProjectValid($project) && ! $this->isProjectInSearchResults($project_id)) {
                $selected = isset($submitted_facets['group_id']) && in_array($project_id, $submitted_facets['group_id']);
                $other_projects[] = new ElasticSearch_SearchResultProjectsFacet($project, 0, $selected);
            }
        }

        usort($other_projects, array($this, 'sortProjects'));

        return $other_projects;
    }

    private function isProjectInSearchResults($project_id) {
        return in_array($project_id, $this->matching_project_ids);
    }

    private function isProjectValid($project) {
        return $project && ! $project->isError();
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

    private function createMatchingProjectsOptionGroup($projects) {
        $projects_option_group = new ElasticSearch_SearchResultProjectsGroupFacet(
            $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_project_matching_projects_option_group'),
            $projects
        );

        $this->option_groups[] = $projects_option_group;
    }

    private function createOtherProjectsOptionGroup($projects) {
        $projects_option_group = new ElasticSearch_SearchResultProjectsGroupFacet(
            $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_project_other_projects_option_group'),
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

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

require_once 'common/project/Project.class.php';


/**
 * Base class for building view of type cross tracker search 
 */
abstract class Tracker_CrossSearch_ViewBuilder {

    /**
     * @var Tracker_FormElementFactory
     */
    protected $form_element_factory;
    
    /**
     * @var Tracker_CrossSearch_Search
     */
    private $search;

    /**
     * @var Tracker_CrossSearch_CriteriaBuilder
     */
    private $criteria_builder;
    
    public function __construct(Tracker_FormElementFactory               $form_element_factory,
                                Tracker_CrossSearch_Search               $search,
                                Tracker_CrossSearch_CriteriaBuilder      $criteria_builder) {
        
        $this->form_element_factory   = $form_element_factory;
        $this->search                 = $search;
        $this->criteria_builder       = $criteria_builder;
    }
    
    protected function getHierarchicallySortedArtifacts(PFUser $user, $project, $tracker_ids, $cross_search_query, $excluded_artifact_ids = array()) {
        return $this->search->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query, $excluded_artifact_ids);
    }
    
    /**
     * Call getCriteria on this criteria_builder 
     * 
     * @param PFUser                      $user			 	an user
     * @param Project                   $project			a project
     * @param Tracker_Report            $report				a tracker report
     * @param Tracker_CrossSearch_Query $cross_search_query a cross search query
     * 
     * @return array of Tracker_Report_Criteria
     */
    protected function getCriteria(PFUser $user, Project $project, Tracker_Report $report, Tracker_CrossSearch_Query $cross_search_query) {
        return $this->criteria_builder->getCriteria($user, $project, $report, $cross_search_query);
    }
    
    protected function getReport(PFUser $user, Project $project) {
        $name               = $GLOBALS['Language']->getText('plugin_tracker_homenav', 'search');
        $is_query_displayed = Toggler::shouldBeDisplayed($user, 'tracker_report_query_0', true);
        
        $report_id = $description = $current_renderer_id = $parent_report_id
                   = $user_id = $is_default = $tracker_id = $updated_by = $updated_at
                   = 0;
        
        $report = new Tracker_Report(
            $report_id,
            $name,
            $description,
            $current_renderer_id,
            $parent_report_id,
            $user_id,
            $is_default,
            $tracker_id,
            $is_query_displayed,
            $updated_by,
            $updated_at
        );
        $report->setProjectId($project->getID());
        return $report;
    }
    
}
?>

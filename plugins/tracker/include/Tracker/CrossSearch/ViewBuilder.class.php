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

require_once 'SemanticTitleReportField.class.php';
require_once 'Query.class.php';
require_once 'SemanticStatusReportField.class.php';
require_once 'SemanticValueFactory.class.php';
require_once 'CriteriaBuilder.class.php';
require_once dirname(__FILE__).'/../Planning/SearchContentView.class.php';

class Tracker_CrossSearch_ViewBuilder {
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    
    /**
     * @var Tracker_CrossSearch_Search
     */
    private $search;


    /**
     * @var Tracker_CrossSearch_CriteriaBuilder
     */
    private $criteria_builder;
    
    public function __construct(Tracker_FormElementFactory               $form_element_factory,
                                TrackerFactory                           $tracker_factory,
                                Tracker_CrossSearch_Search               $search,
                                Tracker_CrossSearch_CriteriaBuilder      $criteria_builder) {
        
        $this->form_element_factory   = $form_element_factory;
        $this->tracker_factory        = $tracker_factory;
        $this->search                 = $search;
        $this->criteria_builder       = $criteria_builder;
    }
    
    /**
     * @return Tracker_CrossSearch_SearchView 
     */
    public function buildView(User $user, Project $project, Tracker_CrossSearch_Query $cross_search_query) {
        $report       = $this->getReport();
        $service      = $this->getService($project);
        $criteria     = $this->getCriteria($user, $project, $report, $cross_search_query);
        $trackers     = $this->tracker_factory->getTrackersByGroupIdUserCanView($project->getGroupId(), $user);
        $tracker_ids  = $this->getTrackersIds($trackers);
        $artifacts    = $this->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query);
        
        $content_view = $this->getContentViewInstance('Tracker_CrossSearch_SearchContentView', $report, $criteria, $artifacts);
        
        return new Tracker_CrossSearch_SearchView($project, $service, $criteria, $trackers, $content_view);
    }
    
    public function buildCustomContentView($class_name, User $user, Project $project, Tracker_CrossSearch_Query $cross_search_query, $excluded_artifact_ids, $tracker_ids) {
        $report    = $this->getReport();
        $criteria  = $this->getCriteria($user, $project, $report, $cross_search_query);
        $artifacts = $this->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query, $excluded_artifact_ids);
        
        return $this->getContentViewInstance($class_name, $report, $criteria, $artifacts);
    }
    
    private function getContentViewInstance($class_name, $report, $criteria, $artifacts) {
        return new $class_name($report, $criteria, $artifacts, Tracker_ArtifactFactory::instance(), $this->form_element_factory);
    }
    
    private function getHierarchicallySortedArtifacts( User $user, $project, $tracker_ids, $cross_search_query, $excluded_artifact_ids = array()) {
        return $this->search->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query, $excluded_artifact_ids);
    }
    
    /**
     * Call getCriteria on this criteria_builder 
     * 
     * @param User 						$user			 	an user
     * @param Project 					$project			a project
     * @param Tracker_Report 			$report				a tracker report
     * @param Tracker_CrossSearch_Query $cross_search_query a cross search query
     * 
     * @return array of Tracker_Report_Criteria
     */
    private function getCriteria(User $user, Project $project, Tracker_Report $report, Tracker_CrossSearch_Query $cross_search_query) {
        return $this->criteria_builder->getCriteria($user, $project, $report, $cross_search_query);
    }
    
    
    /**
     * @return Service
     */
    private function getService(Project $project) {
        $service = $project->getService('plugin_tracker');
        
        if ($service) {
            return $service;
        } else {
            $service_label = $GLOBALS['Language']->getText('plugin_tracker', 'title');
            $error_message = $GLOBALS['Language']->getText('project_service', 'service_not_used', array($service_label));
            
            throw new Tracker_CrossSearch_ServiceNotUsedException($error_message);
        }
    }
    
    private function getReport() {
        $name               = $GLOBALS['Language']->getText('plugin_tracker_homenav', 'search');
        $is_query_displayed = true;
        
        $report_id = $description = $current_renderer_id = $parent_report_id
            = $user_id = $is_default = $tracker_id = $updated_by = $updated_at
            = 0;
        
        $report = new Tracker_Report($report_id, 
                                     $name, 
                                     $description, 
                                     $current_renderer_id, 
                                     $parent_report_id, 
                                     $user_id, 
                                     $is_default, 
                                     $tracker_id, 
                                     $is_query_displayed, 
                                     $updated_by, 
                                     $updated_at);
        return $report;
    }
    
    public function getTrackersIds(array $trackers) {
        return array_map(array($this, 'getTrackerId'), $trackers);
    }
    
    public function getTrackerId(Tracker $tracker) {
        return $tracker->getId();
    }
}
?>

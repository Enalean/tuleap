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
class Tracker_CrossSearch_ViewBuilder {
    /**
     * @var Tracker_FormElementFactory
     */
    private $formElementFactory;
    
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(Tracker_FormElementFactory $formElementFactory, TrackerFactory $tracker_factory) {
        $this->formElementFactory = $formElementFactory;
        $this->tracker_factory    = $tracker_factory;
    }
    
    public function buildView(Project $project, array $request_criteria) {
        $service            = $this->getService($project);
        $criteria           = $this->getCriteria($project, $this->getReport(), $request_criteria);
        $trackers           = $this->getTrackers($project, $this->tracker_factory);
        return $this->getView($project, $service, $criteria, $trackers);
   
    }
    public function buildContentView(Project $project, array $request_criteria, Tracker_CrossSearch_Search $search, Tracker_HierarchyFactory $hierarchy_factory, array $excludedTrackerIds = array()) {
        $report             = $this->getReport();
        $criteria           = $this->getCriteria($project, $report, $request_criteria);
        $trackers           = $this->getTrackers($project, $this->tracker_factory);
        $artifacts          = $this->getArtifacts($trackers, $search, $request_criteria, $hierarchy_factory, $excludedTrackerIds);
        return $this->getContentView($report, $criteria, $artifacts);
    }
    
    
    public function getCriteria(Project $project, Tracker_Report $report, array $request_criteria) {
        $fields   = $this->formElementFactory->getProjectSharedFields($project);
        $criteria = array();
        foreach ($fields as $field) {
            $field->setCriteriaValue($this->getSelectedValues($field, $request_criteria));
            
            $id          = null;
            $rank        = 0;
            $is_advanced = true;
            $criteria[]  = new Tracker_Report_Criteria($id, $report, $field, $rank, $is_advanced);
        }
        return $criteria;
    }
    
    protected function getView(Project $project, Service $service, $criteria, $trackers) {
        return new Tracker_CrossSearch_SearchView($project, $service, $criteria, $trackers);
    }
    
    /**
     * @return Service
     */
    private function getService(Project $project) {
        $service = $project->getService('plugin_tracker');
        if ($service) {
            return $service;
        } else {
            $serviceLabel = $GLOBALS['Language']->getText('plugin_tracker', 'title');
            $errorMessage = $GLOBALS['Language']->getText('project_service', 'service_not_used', array($serviceLabel));
            
            throw new Tracker_CrossSearch_ServiceNotUsedException($errorMessage);
        }
    }
    protected function getContentView(Tracker_Report $report, $criteria, $artifacts) {
        $artifact_factory   = Tracker_ArtifactFactory::instance();
        $formElementFactory = Tracker_FormElementFactory::instance();
        $bindFactory        = new Tracker_FormElement_Field_List_BindFactory();
        $shared_factory     = new Tracker_SharedFormElementFactory($this->formElementFactory, $bindFactory);
        return new Tracker_CrossSearch_SearchContentView($report, $criteria, $artifacts, $artifact_factory, $shared_factory);
    }
    
    private function getReport() {
        $name = "Shared field search";
        $is_query_displayed = true;
        $report_id = $description = $current_renderer_id = $parent_report_id = $user_id = $is_default = $tracker_id = $updated_by = $updated_at = 0;
        
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

    private function getArtifacts(array $trackers, $search, $request_criteria, $hierarchy_factory, $excludedTrackerIds = array()) {
        $hierarchy = $this->getTrackersHierarchy($trackers, $hierarchy_factory);
        return $search->getMatchingArtifacts($trackers, $hierarchy, $request_criteria, $excludedTrackerIds);
    }
    
    private function getTrackersHierarchy(array $trackers, $hierarchy_factory) {
        $tracker_ids = array();
        foreach ($trackers as $tracker) {
            $tracker_ids[] = $tracker->getId();
        }
        return $hierarchy_factory->getHierarchy($tracker_ids);
    }
    
    private function getTrackers(Project $project, $tracker_factory) {
        return $tracker_factory->getTrackersByGroupId($project->getGroupId());
    }
    
    
    private function getSelectedValues(Tracker_FormElement_Field $field, $request_criteria) {
        $currentValue = array();
        if (isset($request_criteria[$field->getId()]['values'])) {
            $currentValue = $request_criteria[$field->getId()]['values'];
        }
        return $currentValue;
    }

}

?>

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

require_once 'SearchView.class.php';
require_once 'ServiceNotUsedException.class.php';
require_once 'ProjectNotFoundException.class.php';
require_once 'Search.class.php';
require_once dirname(__FILE__) .'/../../../tracker/include/Tracker/Hierarchy/Dao.class.php';

class AgileDashboard_SearchController {
    /**
     * @var Codendi_Request
     */
    private $request;
    
    /**
     * @var ProjectManager
     */
    private $projectManager;
    
    /**
     * @var BaseLanguage
     */
    private $language;
    
    /**
     * @var Layout
     */
    private $layout;
    
    /**
     * @var Tracker_FormElementFactory
     */
    private $formElementFactory;
    
    private $hierarchy_dao;
    
    /**
     * @var AgileDashboard_Search
     */
    private $search;
    
    public function __construct(Codendi_Request            $request,
                                ProjectManager             $projectManager, 
                                Tracker_FormElementFactory $formElementFactory, 
                                BaseLanguage               $language, 
                                Layout                     $layout,
                                AgileDashboard_Search      $search,
                                Tracker_Hierarchy_Dao      $hierarchy_dao) {
        
        $this->request            = $request;
        $this->projectManager     = $projectManager;
        $this->language           = $language;
        $this->layout             = $layout;
        $this->formElementFactory = $formElementFactory;
        $this->search             = $search;
        $this->hierarchy_dao      = $hierarchy_dao;
    }

    public function search() {
        try {
            $project            = $this->getProject();
            $service            = $this->getService($project);
            $report             = $this->getReport();
            $criteria           = $this->getCriteria($project, $report);
            $trackers           = $this->getTrackers($project);
            $artifacts          = $this->getArtifacts($trackers);
            $trackers_hierarchy = $this->getTrackersHierarchy($trackers);
            
            $view = $this->getView($service, $this->language, $report, $criteria, $artifacts, $trackers, $trackers_hierarchy);
            $view->render();
        }
        catch (AgileDashboard_ProjectNotFoundException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/');
        }
        catch (AgileDashboard_ServiceNotUsedException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/projects/' . $project->getUnixName() . '/');
        }
    }
    
    protected function getTrackersHierarchy($trackers) {
        $tracker_ids = array();
        foreach ($trackers as $tracker) {
            $tracker_ids[] = $tracker->getId();
        }
        $hierarchy_rows = $this->hierarchy_dao->searchTrackerHierarchy($tracker_ids);
        return new Tracker_Hierarchy($hierarchy_rows);
    }
    
    protected function getArtifacts(array $trackers) {
        return $this->search->getMatchingArtifacts($trackers, $this->request->get('criteria'));
    }
    
    public function getTrackers($project) {
        $trackers = array();
        $projectSharedFields = $this->formElementFactory->getAllProjectSharedFields($project);
        foreach ($projectSharedFields as $field) {
            $trackers[$field->getTrackerId()] = $field->getTracker();
        } 
        return $trackers;
    }
    
    private function getTrackerFromField(Tracker_FormElement $field) {
        return $field->getTracker();
    }
    
    protected function getReport() {
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
    
    public function getCriteria(Project $project, Tracker_Report $report) {
        $fields   = $this->formElementFactory->getProjectSharedFields($project);
        $criteria = array();
        foreach ($fields as $field) {
            $field->setCriteriaValue($this->getSelectedValues($field));
            
            $id          = null;
            $rank        = 0;
            $is_advanced = true;
            $criteria[]  = new Tracker_Report_Criteria($id, $report, $field, $rank, $is_advanced);
        }
        return $criteria;
    }
    
    protected function getSelectedValues(Tracker_FormElement_Field $field) {
        $request_criteria = $this->request->get('criteria');
        $currentValue     = $request_criteria[$field->getId()]['values'];
        if (!$currentValue) {
            $currentValue = array();
        }
        return $currentValue;
    }
    
    /**
     * @return Project
     */
    protected function getProject() {
        $projectId = $this->request->get('group_id');
        $project   = $this->projectManager->getProject($projectId);
        if ($project->isError()) {
            $errorMessage = $this->language->getText('project', 'does_not_exist');
            throw new AgileDashboard_ProjectNotFoundException($errorMessage);
        } else {
            return $project;
        }
    }
    
    /**
     * @return Service
     */
    protected function getService(Project $project) {
        $service = $project->getService('plugin_agiledashboard');
        if ($service) {
            return $service;
        } else {
            $serviceLabel = $this->language->getText('plugin_agiledashboard', 'title');
            $errorMessage = $this->language->getText('project_service', 'service_not_used', array($serviceLabel));
            
            throw new AgileDashboard_ServiceNotUsedException($errorMessage);
        }
    }
    
    protected function getView(Service $service, BaseLanguage $language, Tracker_Report $report, $criteria, $artifacts, $trackers, Tracker_Hierarchy $trackers_hierarchy) {
        $artifact_factory   = Tracker_ArtifactFactory::instance();
        $formElementFactory = Tracker_FormElementFactory::instance();
        $bindFactory        = new Tracker_FormElement_Field_List_BindFactory();
        $shared_factory     = new Tracker_SharedFormElementFactory($formElementFactory, $bindFactory);
        return new AgileDashboard_SearchView($service, $language, $report, $criteria, $artifacts, $artifact_factory, $shared_factory, $trackers, $trackers_hierarchy);
    }
}
?>

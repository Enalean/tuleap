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
require_once dirname(__FILE__) .'/../Hierarchy/HierarchyFactory.class.php';
require_once dirname(__FILE__) .'/../HomeNavPresenter.class.php';

class Tracker_CrossSearch_SearchController {
    /**
     * @var Codendi_Request
     */
    private $request;
    
    /**
     * @var ProjectManager
     */
    private $projectManager;
    
    /**
     * @var Layout
     */
    private $layout;
    
    /**
     * @var Tracker_FormElementFactory
     */
    private $formElementFactory;
    
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    
    /**
     * @var Tracker_CrossSearch_Search
     */
    private $search;
    
    public function __construct(Codendi_Request            $request,
                                ProjectManager             $projectManager, 
                                Tracker_FormElementFactory $formElementFactory, 
                                Layout                     $layout,
                                Tracker_CrossSearch_Search $search,
                                Tracker_HierarchyFactory   $hierarchy_factory,
                                TrackerFactory             $tracker_factory) {
        
        $this->request            = $request;
        $this->projectManager     = $projectManager;
        $this->layout             = $layout;
        $this->formElementFactory = $formElementFactory;
        $this->search             = $search;
        $this->hierarchy_factory  = $hierarchy_factory;
        $this->tracker_factory    = $tracker_factory;
        $this->renderer = new MustacheRenderer(dirname(__FILE__).'/../../../templates');
    }

    public function search() {
        try {
            $request_criteria           = $this->request->get('criteria');
            $project_id         = $this->request->get('group_id');
            $project            = $this->getProject($project_id, $this->projectManager);
            
            
            $view = $this->buildView($project
                    , $this->formElementFactory
                    , $request_criteria
                    , $this->tracker_factory);
            
            $view_builder = new SearchViewBuilder();
            $content_view = $view_builder->buildContentView($project
                    , $this->formElementFactory
                    , $request_criteria
                    , $this->tracker_factory
                    , $this->search
                    , $this->hierarchy_factory);
            
            $view->render($content_view);
        }
        catch (Tracker_CrossSearch_ProjectNotFoundException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/');
        }
        catch (Tracker_CrossSearch_ServiceNotUsedException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/projects/' . $project->getUnixName() . '/');
        }
    }
    
    
    private function buildView($project, $formElementFactory, $request_criteria, $tracker_factory) {
        $service            = $this->getService($project);
        $criteria           = $this->getCriteria($project, $this->getReport(), $formElementFactory, $request_criteria);
        $trackers           = $this->getTrackers($project, $tracker_factory);
        return $this->getView($project, $service, $criteria, $trackers);
   
    }
    
    private function getTrackers(Project $project, $tracker_factory) {
        return $tracker_factory->getTrackersByGroupId($project->getGroupId());
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
    
    public function getCriteria(Project $project, Tracker_Report $report, $formElementFactory, $request_criteria) {
        $fields   = $formElementFactory->getProjectSharedFields($project);
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
    
    private function getSelectedValues(Tracker_FormElement_Field $field, $request_criteria) {
        $currentValue     = $request_criteria[$field->getId()]['values'];
        if (!$currentValue) {
            $currentValue = array();
        }
        return $currentValue;
    }
    
    /**
     * @return Project
     */
    private function getProject($projectId, $projectManager) {
        $project   = $projectManager->getProject($projectId);
        if ($project->isError()) {
            $errorMessage = $GLOBALS['Language']->getText('project', 'does_not_exist');
            throw new Tracker_CrossSearch_ProjectNotFoundException($errorMessage);
        } else {
            return $project;
        }
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
    
    protected function getView(Project $project, Service $service, $criteria, $trackers) {
        return new Tracker_CrossSearch_SearchView($project, $service, $criteria, $trackers);
    }
    
    protected function getContentView(Tracker_Report $report, $criteria, $artifacts) {
        $artifact_factory   = Tracker_ArtifactFactory::instance();
        $formElementFactory = Tracker_FormElementFactory::instance();
        $bindFactory        = new Tracker_FormElement_Field_List_BindFactory();
        $shared_factory     = new Tracker_SharedFormElementFactory($formElementFactory, $bindFactory);
        return new Tracker_CrossSearch_SearchContentView($report, $criteria, $artifacts, $artifact_factory, $shared_factory);
    }

}
class SearchViewBuilder {

    function __construct() {
    }
    public function buildContentView($project, $formElementFactory, $request_criteria, $tracker_factory, $search, $hierarchy_factory) {
        $report             = $this->getReport();
        $criteria           = $this->getCriteria($project, $report, $formElementFactory, $request_criteria);
        $trackers           = $this->getTrackers($project, $tracker_factory);
        $artifacts          = $this->getArtifacts($trackers, $search, $criteria, $hierarchy_factory);
        return $this->getContentView($report, $criteria, $artifacts);
    }
    protected function getContentView(Tracker_Report $report, $criteria, $artifacts) {
        $artifact_factory   = Tracker_ArtifactFactory::instance();
        $formElementFactory = Tracker_FormElementFactory::instance();
        $bindFactory        = new Tracker_FormElement_Field_List_BindFactory();
        $shared_factory     = new Tracker_SharedFormElementFactory($formElementFactory, $bindFactory);
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

    public function getCriteria(Project $project, Tracker_Report $report, $formElementFactory, $request_criteria) {
        $fields   = $formElementFactory->getProjectSharedFields($project);
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
    

    private function getArtifacts(array $trackers, $search, $request_criteria, $hierarchy_factory) {
        $hierarchy = $this->getTrackersHierarchy($trackers, $hierarchy_factory);
        return $search->getMatchingArtifacts($trackers, $hierarchy, $request_criteria);
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
        $currentValue     = $request_criteria[$field->getId()]['values'];
        if (!$currentValue) {
            $currentValue = array();
        }
        return $currentValue;
    }

}
?>

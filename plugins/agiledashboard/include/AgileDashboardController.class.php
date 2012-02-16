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

require_once 'AgileDashboardView.class.php';
require_once 'AgileDashboardSearchResultView.class.php';
require_once 'ServiceNotUsedException.class.php';
require_once 'ProjectNotFoundException.class.php';
require_once 'AgileDashboardSearch.class.php';

class AgileDashboardController {
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
    
    /**
     * @var AgileDashboardSearch
     */
    private $search;
    
    public function __construct(Codendi_Request $request,
                                ProjectManager $projectManager, 
                                Tracker_FormElementFactory $formElementFactory, 
                                BaseLanguage $language, 
                                Layout $layout,
                                AgileDashboardSearch $search) {
        $this->request            = $request;
        $this->projectManager     = $projectManager;
        $this->language           = $language;
        $this->layout             = $layout;
        $this->formElementFactory = $formElementFactory;
        $this->search             = $search;
    }
    
    public function index() {
        $projectId = $this->request->get('group_id');
        
        try {
            $project = $this->getProject($projectId);
            $service = $this->getService($project);
            
            $report   = $this->getReport();
            $criteria = $this->getCriteria($project, $report);
            
            $view     = $this->getView($service, $this->language, $report, $criteria);
            $view->render();
        } catch (ProjectNotFoundException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/');
        } catch (ServiceNotUsedException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/projects/' . $project->getUnixName() . '/');
        }
    }
    
    public function search() {
        $criteria = $this->request->get('criteria');
        $matchingIds = $this->search->getMatchingIds($criteria);
        $view = $this->getSearchResultView($matchingIds);
        $view->render();
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
    
    private function getCriteria(Project $project, Tracker_Report $report) {
        $fields = $this->formElementFactory->getProjectSharedFields($project);

        $criteria = array();
        foreach ($fields as $field) {
            $id          = null;
            $rank        = 0;
            $is_advanced = false;
            $criteria[]  = new Tracker_Report_Criteria($id, $report, $field, $rank, $is_advanced);
        }
        return $criteria;
    }
    
    /**
     * @return Project
     */
    protected function getProject($projectId) {
        $project = $this->projectManager->getProject($projectId);
        if ($project->isError()) {
            $errorMessage = $this->language->getText('project', 'does_not_exist');
            throw new ProjectNotFoundException($errorMessage);
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
            
            throw new ServiceNotUsedException($errorMessage);
        }
    }
    
    protected function getView(Service $service, BaseLanguage $language, Tracker_Report $report, $criteria) {
        return new AgileDashboardView($service, $language, $report, $criteria);
    } 
    
    protected function getSearchResultView($matchingIds) {
        return new AgileDashboardSearchResultView($matchingIds);
    }
}
?>

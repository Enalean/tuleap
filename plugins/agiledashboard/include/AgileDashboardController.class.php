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
require_once 'ServiceNotUsedException.class.php';
require_once 'ProjectNotFoundException.class.php';

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
    
    public function __construct(Codendi_Request $request, ProjectManager $projectManager, BaseLanguage $language, Layout $layout) {
        $this->request        = $request;
        $this->projectManager = $projectManager;
        $this->language       = $language;
        $this->layout         = $layout;
    }
    
    public function index() {
        $projectId = $this->request->get('group_id');
        
        try {
            $project = $this->getProject($projectId);
            $service = $this->getService($project);
            $view    = $this->getView($service, $this->language);
            $view->render();
        } catch (ProjectNotFoundException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/');
        } catch (ServiceNotUsedException $e) {
            $this->layout->addFeedback('error', $e->getMessage());
            $this->layout->redirect('/projects/' . $project->getUnixName() . '/');
        }
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
    
    protected function getView(Service $service, BaseLanguage $language) {
        return new AgileDashboardView($service, $language);
    }
}
?>

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
        $project   = $this->projectManager->getProject($projectId);
        $service   = $project->getService('plugin_agiledashboard');
        
        if ($service) {
            $view = $this->getView($service, $this->language);
            $view->render();
        } else {
            $serviceLabel = $this->language->getText('plugin_agiledashboard', 'title');
            $errorMessage = $this->language->getText('project_service', 'service_not_used', array($serviceLabel));
            
            $this->layout->addFeedback('error', $errorMessage);
            $this->layout->redirect('/projects/' . $project->getUnixName() . '/');
        }
    }
    
    protected function getView(Service $service, BaseLanguage $language) {
        return new AgileDashboardView($service, $language);
    }
}
?>

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
require_once 'Criteria.class.php';
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
    
    public function __construct(Codendi_Request                 $request,
                                ProjectManager                  $projectManager, 
                                Layout                          $layout,
                                Tracker_CrossSearch_ViewBuilder $view_builder) {
        
        $this->request            = $request;
        $this->projectManager     = $projectManager;
        $this->layout             = $layout;
        $this->view_builder       = $view_builder;
    }

    public function search() {
        try {
            $request_criteria  = $this->request->get('criteria');
            $semantic_criteria = $this->request->get('semantic_criteria');
            $project_id        = $this->request->get('group_id');
            $project           = $this->getProject($project_id, $this->projectManager);
            
            if (! $request_criteria) {
                $request_criteria = array();
            }
            
            $cross_search_criteria = new Tracker_CrossSearch_Criteria($request_criteria, $semantic_criteria);
            $view                  = $this->view_builder->buildView($project, $cross_search_criteria);
            
            $view->render();
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
    
}
?>

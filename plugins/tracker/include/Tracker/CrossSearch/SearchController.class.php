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
                                TrackerFactory             $tracker_factory,
                                Tracker_CrossSearch_ViewBuilder          $view_builder = null) {
        
        $this->request            = $request;
        $this->projectManager     = $projectManager;
        $this->layout             = $layout;
        $this->formElementFactory = $formElementFactory;
        $this->search             = $search;
        $this->hierarchy_factory  = $hierarchy_factory;
        $this->tracker_factory    = $tracker_factory;
        $this->renderer = new MustacheRenderer(dirname(__FILE__).'/../../../templates');
        $this->view_builder = isset($view_builder) ? $view_builder : new Tracker_CrossSearch_ViewBuilder();
    }

    public function search() {
        try {
            $request_criteria           = $this->request->get('criteria');
            $project_id         = $this->request->get('group_id');
            $project            = $this->getProject($project_id, $this->projectManager);
            
            
            $view = $this->view_builder->buildView($project
                    , $this->formElementFactory
                    , $request_criteria
                    , $this->tracker_factory);
            
            $content_view = $this->view_builder->buildContentView($project
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

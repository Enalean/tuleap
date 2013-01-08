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


class Tracker_CrossSearch_SearchController {
    /**
     * @var Codendi_Request
     */
    private $request;
    
    /**
     * @var ProjectManager
     */
    private $project_manager;
    
    /**
     * @var Layout
     */
    private $layout;
    
    public function __construct(Codendi_Request                 $request,
                                ProjectManager                  $project_manager, 
                                Layout                          $layout,
                                Tracker_CrossSearch_SearchViewBuilder $view_builder) {
        
        $this->request         = $request;
        $this->project_manager = $project_manager;
        $this->layout          = $layout;
        $this->view_builder    = $view_builder;
    }

    public function search($user) {
        try {
            $cross_search_criteria = $this->getCrossSearchCriteriaFromRequest();
            
            $project_id            = $this->request->get('group_id');
            $project               = $this->getProject($project_id, $this->project_manager);
            $view                  = $this->view_builder->build($user, $project, $cross_search_criteria);
            $view->render($user);
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
    
    protected function getCrossSearchCriteriaFromRequest() {
        $criteria_vars = array('criteria', 'semantic_criteria', 'artifact_criteria');
        $criteria      = array();
        foreach ($criteria_vars as $criterion_name) {
            $criterion_value = $this->request->get($criterion_name);
            if ($criterion_value === false) {
                $criteria[$criterion_name] = array();
            } else {
                $criteria[$criterion_name] = $criterion_value;
            }
        }
        return new Tracker_CrossSearch_Query($criteria['criteria'], $criteria['semantic_criteria'], $criteria['artifact_criteria']);
    }
    
    /**
     * @return Project
     */
    private function getProject($project_id, $project_manager) {
        $project = $project_manager->getProject($project_id);
        
        if ($project->isError()) {
            $error_message = $GLOBALS['Language']->getText('project', 'does_not_exist');
            throw new Tracker_CrossSearch_ProjectNotFoundException($error_message);
        } else {
            return $project;
        }
    }
    
}
?>

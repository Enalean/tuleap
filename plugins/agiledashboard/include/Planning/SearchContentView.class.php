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

require_once 'common/mustache/MustacheRenderer.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/CrossSearch/SearchContentView.class.php';
require_once 'ArtifactTreeNodeVisitor.class.php';

class Planning_SearchContentView extends Tracker_CrossSearch_SearchContentView {

    private $renderer;
    
    // Presenter properties
    public $planning;
    public $planning_redirect_parameter = '';

    public function __construct(Tracker_Report             $report,
                                array                      $criteria,
                                TreeNode                   $tree_of_artifacts, 
                                Tracker_ArtifactFactory    $artifact_factory, 
                                Tracker_FormElementFactory $factory,
                                User                       $user,
                                Planning                   $planning,
                                                           $planning_redirect_param) {
        parent::__construct($report, $criteria, $tree_of_artifacts, $artifact_factory, $factory, $user);
        
        $this->planning                    = $planning;
        $this->planning_redirect_parameter = $planning_redirect_param;
        $this->renderer = new MustacheRenderer(dirname(__FILE__) .'/../../templates');
    }
    
    public function fetchResultActions() {
        return $this->renderer->render('backlog-actions', $this, true);
    }
    
    protected function fetchTable() {
        Planning_ArtifactTreeNodeVisitor::build('planning-draggable-toplan')->visit($this->tree_of_artifacts);
        return $this->renderer->render('backlog', $this, true);
    }

    public function getChildren() {
        return $this->tree_of_artifacts->getChildren();
    }
    
    public function allowedChildrenTypes() {
        return $this->planning->getBacklogTrackers();
    }
    
    public function addLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'backlog_add');
    }

    public function setRenderer($renderer) {
        $this->renderer = $renderer;
        
    }
}
?>

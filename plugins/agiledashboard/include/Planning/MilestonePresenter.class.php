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

require_once 'ArtifactTreeNodeVisitor.class.php';
require_once 'PlanningPresenter.class.php';
require_once 'MilestoneLinkPresenter.class.php';

/**
 * Provides the presentation logic for a planning milestone.
 */
class Planning_MilestonePresenter extends PlanningPresenter {
    
    /**
     * @var array of Planning_Milestone
     */
    private $available_milestones;
    
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    
    /**
     * @var Tracker_CrossSearch_SearchContentView 
     */
    private $backlog_search_view;
    
    /**
     * @var User
     */
    private $current_user;
    
    /**
     * @var string
     */
    public $planning_redirect_parameter;
    
    /**
     * Instanciates a new presenter.
     * 
     * TODO:
     *   - $planning could be retrieved from $milestone
     *   - use $milestone->getPlanning()->getAllMilestones() instead of $available_milestones ?
     * 
     * @param Planning                              $planning                    The planning (e.g. Release planning, Sprint planning).
     * @param Tracker_CrossSearch_SearchContentView $backlog_search_view         The view allowing to search through the backlog artifacts.
     * @param array                                 $available_milestones        The available milestones for a given planning (e.g. Sprint 2, Release 1.0).
     * @param Tracker_Artifact                      $milestone                   The artifact with planning being displayed right now.
     * @param User                                  $current_user                The user to which the artifact plannification UI is presented.
     * @param string                                $planning_redirect_parameter The request parameter representing the artifact being planned, used for redirection (e.g: "planning[2]=123").
     */
    public function __construct(Planning                              $planning,
                                Tracker_CrossSearch_SearchContentView $backlog_search_view,
                                array                                 $available_milestones,
                                Planning_Milestone                    $milestone, 
                                User                                  $current_user,
                                                                      $planning_redirect_parameter) {
        parent::__construct($planning);
        
        $this->milestone                   = $milestone;
        $this->available_milestones        = $available_milestones;
        $this->backlog_search_view         = $backlog_search_view;
        $this->current_user                = $current_user;
        $this->planning_redirect_parameter = $planning_redirect_parameter;
    }
    
    /**
     * @return bool
     */
    public function hasSelectedArtifact() {
        return !is_a($this->milestone, 'Planning_NoMilestone');
    }
    
    /**
     * @return TreeNode
     */
    public function plannedArtifactsTree($child_depth = 1) {
        $root_node = null;
        
        if ($this->canAccessPlannedItem()) {
            $root_node = $this->milestone->getPlannedArtifacts();
            
            //TODO use null object pattern while still possible?
            if ($root_node) {
                Planning_ArtifactTreeNodeVisitor::build($this->milestone->getPlanning(),
                                                        'planning-draggable-alreadyplanned')->visit($root_node);
            }
        }
        return $root_node;
    }
    
    private function canAccessPlannedItem() {
        return $this->milestone && $this->milestone->userCanView($this->current_user);
    }
    
    /**
     * @return string html
     */
    public function backlogSearchView() {
        return $this->backlog_search_view->fetch();
    }
    
    
    /**
     * @return string
     */
    public function pleaseChoose() {
        return $GLOBALS['Language']->getText('global', 'please_choose_dashed');
    }
    
    /**
     * @return array of (id, title, selected)
     */
    public function selectableArtifacts() {
        $hp             = Codendi_HTMLPurifier::instance();
        $artifacts_data = array();
        $selected_id    = $this->milestone->getArtifactId();
        
        foreach ($this->available_milestones as $milestone) {
            $artifacts_data[] = array(
                'id'       => $milestone->getArtifactId(),
                'title'    => $hp->purify($milestone->getArtifactTitle()),
                'selected' => ($milestone->getArtifactId() == $selected_id) ? 'selected="selected"' : '',
            );
        }
        return $artifacts_data;
    }
    
    /**
     * @return string
     */
    public function plannedArtifactsHelp() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_destination_help');
    }
    
    /**
     * @return string
     */
    public function planningDroppableClass() {
        if ($this->canDrop()) {
            return 'planning-droppable';
        }
        return false;
    }
    
    /**
     * @return string
     */
    public function getPlanningTrackerArtifactCreationLabel() {
        $new       = $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_artifact_new');
        $item_name = $this->planning->getPlanningTracker()->getItemName();
        
        return "$new $item_name";
    }
    
    /**
     * @return bool
     */
    public function canDrop() {
        if ($this->milestone) {
            $art_link_field = $this->milestone->getArtifact()->getAnArtifactLinkField($this->current_user);
            if ($art_link_field && $art_link_field->userCanUpdate($this->current_user)) {
                return true;
            }
        }
        return false;
    }
    
    public function hasSubMilestones() {
        return $this->milestone->hasSubMilestones();
    }
    
    public function getSubMilestones() {
        return array_map(array($this, 'getMilestoneLinkPresenter'), $this->milestone->getSubMilestones());
    }
    
    private function getMilestoneLinkPresenter(Planning_Milestone $milestone) {
        return new Planning_MilestoneLinkPresenter($milestone);
    }
    
    /**
     * @return string html
     */
    public function errorCantDrop() {
        if ($this->canDrop()) {
            return false;
        }
        return '<div class="feedback_warning">'. $GLOBALS['Language']->getText('plugin_tracker', 'must_have_artifact_link_field') .'</div>';
    }

    /**
     * @return string
     */
    public function createNewItemToPlan() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan');
    }
    
    /**
     * @return string
     */
    public function editLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
}
?>

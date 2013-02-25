<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/TreeNode/TreeNodeMapper.class.php';

/**
 * This class is responsible to build the view of "MilestonePlanning"
 * It's the view you have when you want to do "Sprint Planning".
 * It's a Pane content
 */
class AgileDashboard_MilestonePlanningPresenter extends PlanningPresenter {


    /**
     * @var Planning_Milestone
     */
    private $milestone_plan;

    /**
     * @var Tracker_CrossSearch_SearchContentView
     */
    private $backlog_search_view;

    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var string
     */
    private $planning_redirect_parameter;

    /**
     * @var TreeNode
     */
    private $planned_artifacts_tree;

    /**
     * Instanciates a new presenter.
     *
     * @param Tracker_CrossSearch_SearchContentView $backlog_search_view         The view allowing to search through the backlog artifacts.
     * @param Planning_Milestone                    $milestone_plan                   The artifact with planning being displayed right now.
     * @param PFUser                                  $current_user                The user to which the artifact plannification UI is presented.
     */
    public function __construct(
        Tracker_CrossSearch_SearchContentView $backlog_search_view,
        Planning_MilestonePlan                $milestone_plan,
        PFUser                                  $current_user,
                                              $planning_redirect_parameter
    ) {
        parent::__construct($milestone_plan->getMilestone()->getPlanning());
        $this->milestone_plan              = $milestone_plan;
        $this->backlog_search_view         = $backlog_search_view;
        $this->current_user                = $current_user;
        $this->planning_redirect_parameter = $planning_redirect_parameter;
        $this->planned_artifacts_tree      = $this->buildPlannedArtifactsTree();
    }

    public function planning_redirect_parameter() {
        return $this->planning_redirect_parameter;
    }

    public function backlogSearchView() {
        return $this->backlog_search_view->fetch();
    }

    /**
     * @return bool
     */
    public function hasSelectedArtifact() {
        return !is_a($this->milestone_plan->getMilestone(), 'Planning_NoMilestone');
    }

    /**
     * @return TreeNode
     */
    private function buildPlannedArtifactsTree($child_depth = 1) {
        $presenter_root_node = null;

        if ($this->canAccessPlannedItem()) {
            $root_node = $this->milestone_plan->getMilestone()->getPlannedArtifacts();

            //TODO use null object pattern while still possible?
            if ($root_node) {
                $card_mapper = new TreeNodeMapper(
                    new Planning_ItemCardPresenterCallback(
                        $this->milestone_plan->getMilestone()->getPlanning(),
                        new Tracker_CardFields(),
                        $this->current_user,
                        'planning-draggable-alreadyplanned'
                    )
                );
                $presenter_root_node = $card_mapper->map($root_node);
            }
        }
        return $presenter_root_node;
    }

    public function getPlannedArtifactsTree() {
        return $this->planned_artifacts_tree;
    }

    /**
     * @todo (later) we should not check if we can display things when we are in
     *       the presenter but rather build only things that can be displayed
     *       therefore permission checking should be done in Model or Controller
     * @return boolean
     */
    private function canAccessPlannedItem() {
        return $this->milestone_plan->getMilestone() && $this->milestone_plan->getMilestone()->userCanView($this->current_user);
    }




    /**
     * @return string
     */
    public function pleaseChoose() {
        return $GLOBALS['Language']->getText('global', 'please_choose_dashed');
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
     * @return bool
     */
    public function canDrop() {
        if ($this->milestone_plan->getMilestone()) {
            $art_link_field = $this->milestone_plan->getMilestone()->getArtifact()->getAnArtifactLinkField($this->current_user);
            if ($art_link_field && $art_link_field->userCanUpdate($this->current_user)) {
                return true;
            }
        }
        return false;
    }

    public function hasSubMilestones() {
        return $this->milestone_plan->hasSubMilestones();
    }

    public function getSubMilestones() {
        return array_map(array($this, 'getMilestoneLinkPresenter'), $this->milestone_plan->getSubMilestones());
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
    public function editLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }

    public function canDisplayRemainingEffort() {
        return $this->milestone_plan->getRemainingEffort() !== null && $this->milestone_plan->getCapacity() !== null;
    }

    public function getRemainingEffort() {
        $remaining_effort = $this->milestone_plan->getRemainingEffort() != null ? $this->milestone_plan->getRemainingEffort() : 0;
        $capacity         = $this->milestone_plan->getCapacity() != null ? $this->milestone_plan->getCapacity() : 0;

        $html  = '';
        $html .= $GLOBALS['Language']->getText('plugin_agiledashboard', 'capacity');
        $html .= '&nbsp;<span class="planning_remaining_effort">'.$remaining_effort.'</span>';
        $html .= '&nbsp;/&nbsp;'.$capacity;
        return $html;
    }

    public function isOverCapacity() {
        return $this->canDisplayRemainingEffort() &&
               $this->milestone_plan->getRemainingEffort() > $this->milestone_plan->getCapacity();
    }
}

?>

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

require_once 'common/TreeNode/TreeNodeMapper.class.php';

/**
 * This presenter build the top view of a milestone (milestone title + switch on another milestone).
 * It delegates the displaye to an AgileDashboardPane for the content
 * @see AgileDashboard_Pane
 */
class AgileDashboard_MilestonePresenter {
    /**
     * @var array
     */
    private $additional_panes = array();

    /**
     * @var array of Planning_Milestone
     */
    private $available_milestones;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var AgileDashboard_Pane
     */
    private $active_pane;

    /**
     * @var string
     */
    private $planning_redirect_to_new;

    public function __construct(
            Planning_Milestone $milestone,
            PFUser $current_user,
            Codendi_Request $request,
            $active_pane,
            array $additional_panes,
            array $available_milestones,
            $planning_redirect_to_new
            ) {
        $this->milestone                = $milestone;
        $this->current_user             = $current_user;
        $this->request                  = $request;
        $this->active_pane              = $active_pane;
        $this->additional_panes         = $additional_panes;
        $this->available_milestones     = $available_milestones;
        $this->planning_redirect_to_new = $planning_redirect_to_new;
    }

    public function milestoneTitle() {
        return $this->milestone->getArtifactTitle();
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
                'title'    => $hp->purify($milestone->getArtifactTitle()),
                'selected' => ($milestone->getArtifactId() == $selected_id) ? 'selected="selected"' : '',
                'url'      => $this->active_pane->getUriForMilestone($milestone)
            );
        }
        return $artifacts_data;
    }

    public function createNewItemToPlan() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan', array($this->milestone->getPlanning()->getPlanningTracker()->getItemName()));
    }

    public function createNewItemToPlanUrl() {
        return '/plugins/tracker/?tracker='.$this->milestone->getPlanning()->getPlanningTrackerId().'&func=new-artifact-link&id='.$this->getParentArtifactId().'&immediate=1&'.$this->planning_redirect_to_new;
    }

    private function getParentArtifactId() {
        $ancestors = $this->milestone->getAncestors();
        if (count($ancestors) > 0) {
            return $ancestors[0]->getArtifactId();
        }
    }

    public function getActivePane() {
        return $this->active_pane;
    }

    /**
     * @return array
     */
    public function getPaneInfoList() {
        return $this->additional_panes;
    }

    public function startDate() {
        $start_date = $this->milestone->getStartDate();
        if (! $start_date) {
            return null;
        }
        return $this->formatDate($start_date);
    }

    public function endDate() {
        $end_date = $this->milestone->getEndDate();
        if (! $end_date) {
            return null;
        }
        return $this->formatDate($end_date);
    }

    public function displayMilestoneDates() {
        $start_date = $this->startDate();
        $end_date   = $this->endDate();

        return $start_date && $end_date;
    }

    private function formatDate($date) {
        return date($GLOBALS['Language']->getText('system', 'datefmt_day_and_month'), $date);
    }
}

?>

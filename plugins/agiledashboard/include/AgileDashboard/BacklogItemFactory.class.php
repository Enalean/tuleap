<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_BacklogItemFactory {

    public function __construct(AgileDashboard_BacklogItemDao $dao, Tracker_ArtifactFactory $artifact_factory) {
        $this->dao = $dao;
        $this->artifact_factory = $artifact_factory;
    }

    public function getMilestoneContent(Planning_ArtifactMilestone $milestone) {
        $redirect_paremeter = new Planning_MilestoneRedirectParameter();
        $redirect_to_self   = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_ContentPaneInfo::IDENTIFIER);

        $backlog_items = array();
        $artifacts = $this->dao->getBacklogArtifacts($milestone->getArtifactId())->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
        foreach ($artifacts as $artifact) {
            /* @var $artifact Tracker_Artifact */
            $backlog_items[$artifact->getId()] = new AgileDashboard_BacklogItem($artifact, $redirect_to_self);
        }
        $backlog_item_ids = array_keys($backlog_items);
        $parents = $this->artifact_factory->getParents($backlog_item_ids);
        foreach ($parents as $child_id => $parent) {
            $backlog_items[$child_id]->setParent($parent);
        }

        return $backlog_items;
    }

    public function getMilestoneContentPresenter(Planning_ArtifactMilestone $milestone) {
        $this->row_collection = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
        foreach ($this->getMilestoneContent($milestone) as $artifact) {
            /* @var $artifact AgileDashboard_Milestone_Pane_ContentRowPresenter */
            $this->row_collection->push($artifact);
        }

        $backlog_item_type = 'Story';
        $can_add_backlog_item_type = true;
        return new AgileDashboard_Milestone_Pane_ContentPresenter($this->row_collection, new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection(), $backlog_item_type, $can_add_backlog_item_type);
    }
}

class AgileDashboard_BacklogItem implements AgileDashboard_Milestone_Pane_ContentRowPresenter {
    /** @var Tracker_Artifact */
    private $artifact;

    /** @var String */
    private $parent_url;

    /** @var Title */
    private $parent_title;

    /** @var Title */
    private $redirect_to_self;

    public function __construct(Tracker_Artifact $artifact, $redirect_to_self) {
        $this->artifact         = $artifact;
        $this->redirect_to_self = $redirect_to_self;
    }

    public function setParent(Tracker_Artifact $parent) {
        $this->parent_title = $parent->getTitle();
        $this->parent_url   = $parent->getUri() .'&'. $this->redirect_to_self;
    }

    public function id() {
        return $this->artifact->getId();
    }

    public function title() {
        return $this->artifact->getTitle();
    }

    public function url() {
        return $this->artifact->getUri() .'&'. $this->redirect_to_self;
    }

    public function points() {
        return '';
    }

    public function parent_title() {
        return $this->parent_title;
    }

    public function parent_url() {
        return $this->parent_url;
    }
}

?>

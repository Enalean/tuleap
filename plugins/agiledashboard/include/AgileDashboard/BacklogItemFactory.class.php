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

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(AgileDashboard_BacklogItemDao $dao, Tracker_ArtifactFactory $artifact_factory) {
        $this->dao = $dao;
        $this->artifact_factory = $artifact_factory;
    }

    public function getMilestoneContentPresenter(Planning_ArtifactMilestone $milestone) {
        $todo_collection = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
        $done_collection = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
        $this->getMilestoneContent($milestone, $todo_collection, $done_collection);

        $backlog_item_type = 'Story';
        $can_add_backlog_item_type = true;
        return new AgileDashboard_Milestone_Pane_ContentPresenter($todo_collection, $done_collection, $backlog_item_type, $can_add_backlog_item_type);
    }

    protected function getMilestoneContent(
        Planning_ArtifactMilestone $milestone,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $todo_collection,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $done_collection
    ) {
        $redirect_paremeter = new Planning_MilestoneRedirectParameter();
        $redirect_to_self   = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_ContentPaneInfo::IDENTIFIER);

        $artifacts = array();
        foreach ($this->getBacklogArtifacts($milestone) as $artifact) {
            /* @var $artifact Tracker_Artifact */
            $artifacts[$artifact->getId()] = $artifact;
        }
        $backlog_item_ids = array_keys($artifacts);
        $parents = $this->artifact_factory->getParents($backlog_item_ids);
        $status  = $this->dao->getArtifactsStatusAndTitle($backlog_item_ids);
        foreach ($status as $row) {
            if (isset($artifacts[$row['id']])) {
                $artifacts[$row['id']]->setTitle($row['title']);
                $backlog_item = new AgileDashboard_BacklogItem($artifacts[$row['id']], $redirect_to_self);
                if (isset($parents[$artifact->getId()])) {
                    $backlog_item->setParent($parents[$artifact->getId()]);
                }
                if ($row['status'] == AgileDashboard_BacklogItemDao::STATUS_OPEN) {
                    $todo_collection->push($backlog_item);
                } else {
                    $done_collection->push($backlog_item);
                }
            }
        }
    }

    protected function getBacklogArtifacts(Planning_ArtifactMilestone $milestone) {
        return $this->dao->getBacklogArtifacts($milestone->getArtifactId())->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
    }

}

class AgileDashboard_BacklogItem implements AgileDashboard_Milestone_Pane_ContentRowPresenter {
    /** @var Int */
    private $id;

    /** @var String */
    private $title;

    /** @var String */
    private $url;

    /** @var String */
    private $parent_url;

    /** @var Title */
    private $parent_title;

    /** @var Title */
    private $redirect_to_self;

    public function __construct(Tracker_Artifact $artifact, $redirect_to_self) {
        $this->id    = $artifact->getId();
        $this->title = $artifact->getTitle();
        $this->url   = $artifact->getUri();
        $this->redirect_to_self = $redirect_to_self;
    }

    public function setParent(Tracker_Artifact $parent) {
        $this->parent_title = $parent->getTitle();
        $this->parent_url   = $parent->getUri() .'&'. $this->redirect_to_self;
    }

    public function id() {
        return $this->id;
    }

    public function title() {
        return $this->title;
    }

    public function url() {
        return $this->url .'&'. $this->redirect_to_self;
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

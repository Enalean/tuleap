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

/**
 * I am the backlog of the first descendant of the current milestone
 */
class AgileDashboard_Milestone_Pane_ContentDescendantBacklogStrategy extends AgileDashboard_Milestone_Pane_ContentBacklogStrategy {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    public function __construct($milestone_backlog_artifacts, $item_name, AgileDashboard_BacklogItemDao $dao) {
        parent::__construct($milestone_backlog_artifacts, $item_name);
        $this->dao = $dao;
    }

    /** @return Tracker_Artifact[] */
    public function getArtifacts(PFUser $user) {
        $backlog = array();
        foreach ($this->milestone_backlog_artifacts as $artifact) {
            /* @var $artifact Tracker_Artifact */
            $backlog = array_merge($backlog, $artifact->getChildrenForUser($user));
        }
        $backlog = array_filter($backlog);
        return $this->sortByPriority($backlog);
    }

    private function sortByPriority(array $artifacts) {
        $ids              = array_map(array($this, 'extractId'), $artifacts);
        $artifacts        = array_combine($ids, $artifacts);
        $sorted_ids       = $this->dao->getIdsSortedByPriority($ids);
        $sorted_artifacts = array_flip($sorted_ids);

        foreach ($sorted_artifacts as $id => $nop) {
            $sorted_artifacts[$id] = $artifacts[$id];
        }

        return $sorted_artifacts;
    }

    private function extractId($artifact) {
        return $artifact->getId();
    }
}
?>

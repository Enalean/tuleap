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
 * I retrieve the content of the backlog
 */
abstract class AgileDashboard_Milestone_Pane_BacklogStrategy {

    /** @var Tracker_Artifact[] */
    protected $milestone_backlog_artifacts;

    /** @var string */
    protected $backlogitem_name;

    public function __construct($milestone_backlog_artifacts, $backlogitem_name) {
        $this->milestone_backlog_artifacts = $milestone_backlog_artifacts;
        $this->backlogitem_name            = $backlogitem_name;
    }

    /** @return string */
    public function getItemName() {
        return $this->backlogitem_name;
    }

    /** @return Tracker_Artifact[] */
    public abstract function getArtifacts(PFUser $user);
}
?>

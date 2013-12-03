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

class AgileDashboard_Milestone_Backlog_BacklogItem implements AgileDashboard_Milestone_Backlog_IBacklogItem {

    /** @var Int */
    private $id;

    /** @var String */
    private $title;

    /** @var String */
    private $type;

    /** @var String */
    private $url;

    /** @var Int */
    private $initial_effort;

    /** @var String */
    private $status;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_Artifact */
    private $parent;

    public function __construct(Tracker_Artifact $artifact) {
        $this->id               = $artifact->getId();
        $this->title            = $artifact->getTitle();
        $this->url              = $artifact->getUri();
        $this->artifact         = $artifact;
        $this->type             = $this->artifact->getTracker()->getName();
    }

    public function setParent(Tracker_Artifact $parent) {
        $this->parent = $parent;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getParent() {
        return $this->parent;
    }

    public function setInitialEffort($value) {
        $this->initial_effort = $value;
    }

    public function getInitialEffort() {
        return $this->initial_effort;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function id() {
        return $this->id;
    }

    public function title() {
        return $this->title;
    }

    public function type() {
        return $this->type;
    }

    public function points() {
        return $this->initial_effort;
    }

    public function parent_title() {
        if ($this->parent) {
            return $this->parent->getTitle();
        }
    }

    public function status() {
        return $this->status;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact() {
        return $this->artifact;
    }
}

?>

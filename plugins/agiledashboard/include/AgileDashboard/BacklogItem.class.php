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

class AgileDashboard_BacklogItem implements AgileDashboard_Milestone_Backlog_BacklogRowPresenter {
    /** @var Int */
    private $id;

    /** @var String */
    private $title;

    /** @var String */
    private $url;

    /** @var Int */
    private $remaining_effort;

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

    public function setRemainingEffort($value) {
        $this->remaining_effort = $value;
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
        return $this->remaining_effort;
    }

    public function parent_title() {
        return $this->parent_title;
    }

    public function parent_url() {
        return $this->parent_url;
    }
}

?>

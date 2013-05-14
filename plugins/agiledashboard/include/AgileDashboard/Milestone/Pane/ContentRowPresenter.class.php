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

class AgileDashboard_Milestone_Pane_ContentRowPresenter {
    private $id;
    private $title;
    private $url;
    private $points;
    private $parent_title;
    private $parent_url;

    public function __construct($id, $title, $url, $points, $parent_title, $parent_url) {
        $this->id           = $id;
        $this->title        = $title;
        $this->url          = $url;
        $this->points       = $points;
        $this->parent_title = $parent_title;
        $this->parent_url   = $parent_url;
    }

    public function id() {
        return $this->id;
    }

    public function title() {
        return $this->title;
    }

    public function url() {
        return $this->url;
    }

    public function points() {
        return $this->points;
    }

    public function parent_title() {
        return $this->parent_title;
    }

    public function parent_url() {
        return $this->parent_url;
    }
}

?>

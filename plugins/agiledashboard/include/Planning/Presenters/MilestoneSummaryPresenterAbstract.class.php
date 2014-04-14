<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

abstract class Planning_Presenter_MilestoneSummaryPresenterAbstract {

    /** @var Planning_Milestone */
    public $milestone;

    /** @var string */
    private $plugin_path;

    /** @var string */
    public $has_cardwall;

    public function __construct(Planning_Milestone $milestone, $plugin_path, $has_cardwall) {
        $this->milestone    = $milestone;
        $this->plugin_path  = $plugin_path;
        $this->has_cardwall = $has_cardwall;
    }

    public function content() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','content_pane_title');
    }

    public function cardwall() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cardwall');
    }

    public function breadcrumbs() {
        $breadcrumbs_merger = new BreadCrumb_Merger();
        foreach(array_reverse($this->milestone->getAncestors()) as $milestone) {
            $breadcrumbs_merger->push(new BreadCrumb_Milestone($this->plugin_path, $milestone));
        }

        return $breadcrumbs_merger->getCrumbs();
    }

    public function milestone_title() {
        return $this->milestone->getArtifactTitle();
    }

    public abstract function has_burndown();

    public function planning_id() {
        return $this->milestone->getPlanningId();
    }

    public function artifact_id() {
        return $this->milestone->getArtifactId();
    }

    public function edit_base_link() {
        return '/plugins/tracker/?aid=';
    }
}
?>

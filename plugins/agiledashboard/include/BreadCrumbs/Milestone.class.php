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


class BreadCrumb_Milestone implements BreadCrumb_BreadCrumbGenerator {

    private $plugin_path;
    private $milestone;

    public function __construct($plugin_path, Planning_Milestone $milestone) {
        $this->plugin_path = $plugin_path;
        $this->milestone   = $milestone;
    }

    public function getCrumbs() {
        $hp             = Codendi_HTMLPurifier::instance();
        $tracker        = $this->milestone->getArtifact()->getTracker();
        $url_parameters = array(
            'planning_id' => $this->milestone->getPlanningId(),
            'action'      => 'show',
            'pane'        => 'planning-v2',
            'group_id'    => $this->milestone->getGroupId(),
            'aid'         => $this->milestone->getArtifactId()
        );
        return array(
            array(
                'url'   => $this->plugin_path .'/?'. http_build_query($url_parameters),
                'title' => $hp->purify($this->milestone->getArtifactTitle()),
                'default_name' => $hp->purify($tracker->getName().' #' . $this->milestone->getArtifactId()),
        ));
    }
}
?>

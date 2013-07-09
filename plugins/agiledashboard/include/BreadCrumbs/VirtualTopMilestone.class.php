<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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


class BreadCrumb_VirtualTopMilestone implements BreadCrumb_BreadCrumbGenerator {

    private $plugin_path;
    private $project;

    public function __construct($plugin_path, Project $project) {
        $this->plugin_path = $plugin_path;
        $this->project     = $project;
    }

    public function getCrumbs() {
        $hp             = Codendi_HTMLPurifier::instance();
        $url_top_parameters = array(
            'action'   => 'show-top',
            'pane'     => 'topblcontent',
            'group_id' => (int) $this->project->getGroupId()
        );
        return array(
            array(
                'url'   => $this->plugin_path .'/?'. http_build_query($url_top_parameters),
                'title' => $GLOBALS['Language']->getText('plugin_agiledashboard', 'top_planning_link'),
            ),
        );
    }
}
?>

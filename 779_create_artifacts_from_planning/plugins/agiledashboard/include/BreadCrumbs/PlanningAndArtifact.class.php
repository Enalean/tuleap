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

class BreadCrumb_PlanningAndArtifact implements BreadCrumb_BreadCrumbGenerator {

    public function __construct($plugin_path, $artifact, $planning) {
        $this->artifact    = $artifact;
        $this->plugin_path = $plugin_path;
        $this->planning    = $planning;
    }
    public function getCrumbs() {
        $hp             = Codendi_HTMLPurifier::instance();
        $planning = $this->planning;
        $url_parameters['planning_id'] = (int) $planning->getId();
        $url_parameters['action']      = 'show';
        $breadcrumbs[] = array(
            'url'   => $this->plugin_path .'/?'. http_build_query($url_parameters),
            'title' => $hp->purify($planning->getName()),
        );
        if ($this->artifact) {
            $url_parameters['aid'] = (int) $this->artifact->getId();
            $breadcrumbs[] = array(
                'url'   => $this->plugin_path .'/?'. http_build_query($url_parameters),
                'title' => $hp->purify($this->artifact->getTitle()),
            );
        }
        return $breadcrumbs;
    }
}
?>

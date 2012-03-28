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
require_once dirname(__FILE__).'/BreadCrumbGenerator.class.php';

class BreadCrumb_AgileDashboard implements BreadCrumb_BreadCrumbGenerator {

    public function __construct($project_id, $plugin_path) {
        $this->project_id = $project_id;
        $this->plugin_path = $plugin_path;
    }
    
    public function getCrumbs() {
        $url_parameters = array(
            'group_id' => (int) $this->project_id,
        );
        $breadcrumbs   = array();
        $breadcrumbs[] = array(
            'url'   => $this->plugin_path .'/?'. http_build_query($url_parameters),
            'title' => $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key')
        );
        return $breadcrumbs;
    }
}
?>

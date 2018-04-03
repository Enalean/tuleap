<?php

/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

class BreadCrumb_AgileDashboard implements BreadCrumb_BreadCrumbGenerator
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var string
     */
    private $plugin_path;

    public function __construct($plugin_path, Project $project)
    {
        $this->project = $project;
        $this->plugin_path = $plugin_path;
    }

    public function getCrumbs()
    {
        $encoded_id = urlencode($this->project->getID());

        return array(
            array('url' => $this->plugin_path . '/?group_id=' . $encoded_id, 'title' => dgettext(
                'tuleap-agiledashboard',
                'Agile Dashboard'
            ))
        );
    }
}

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

/**
 * Display the page header and footer for the current service.
 */
interface Tracker_IDisplayTrackerLayout {
    
    /**
     * Display header for the current service
     *
     * @param Project $project    The project
     * @param string  $title      The title for this page
     * @param array   $breadcrumb The breadcrumbs for this page
     * @param ?       $toolbar    The toolbar
     *
     * @return void
     */
    public function displayHeader($project, $title, $breadcrumbs, $toolbar);
    
    /**
     * Display footer for the current service.
     *
     * @param Project $project The project
     */
    public function displayFooter($project);
}
?>

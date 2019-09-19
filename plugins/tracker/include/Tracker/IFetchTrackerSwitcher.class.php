<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
 * Render a tracker switching selectbox. Also display headers and footers
 * See:
 *  + Tracker_IDisplayTrackerLayout
 *  + fetchTrackerSwitcher
 */
interface Tracker_IFetchTrackerSwitcher extends Tracker_IDisplayTrackerLayout
{

    /**
     * Display a selectbox to switch to a tracker of:
     *  + any projects the user is member of
     *  + an additional project
     *
     * The additionnal project may be useful for example in the ArtifactLink selector,
     * To make sure that the project of the main artifact is included.
     *
     * @param PFUser    $user            the user
     * @param string  $separator       the separator between the title and the selectbox (eg: '<br />' or ' ')
     * @param Project $include_project the project to include in the selectbox (null if no one)
     * @param Tracker $current_tracker the current tracker (default is null, aka no current tracker)
     *
     * @return string html
     */
    public function fetchTrackerSwitcher(PFUser $user, $separator, ?Project $include_project = null, ?Tracker $current_tracker = null);
}

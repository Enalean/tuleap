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

require_once 'IDisplayTrackerLayout.class.php';
require_once 'common/user/User.class.php';
require_once 'common/project/Project.class.php';
require_once 'Tracker.class.php';

interface Tracker_IFetchTrackerSwitcher extends Tracker_IDisplayTrackerLayout {
    public function fetchTrackerSwitcher(User $user, $separator, Project $include_project = null, Tracker $current_tracker = null);
}
?>

<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v2;

use Tuleap\Tracker\REST\TrackerReference;

class BacklogRepresentation
{
    public const ROUTE = 'backlog_items';

    /**
     * @var BacklogItemRepresentation[]
     */
    public $content;

    /**
     *
     * @var array
     */
    public $accept;

    /**
     * @var bool
     */
    public $has_user_priority_change_permission;

    public function build(
        array $backlog_items,
        array $accepted_trackers,
        array $parent_trackers,
        $has_user_priority_change_permission
    ) {
        $this->content = $backlog_items;

        $this->accept['trackers']        = $this->getTrackersRepresentation($accepted_trackers);
        $this->accept['parent_trackers'] = $this->getTrackersRepresentation($parent_trackers);

        $this->has_user_priority_change_permission = $has_user_priority_change_permission;

        return $this;
    }

    private function getTrackersRepresentation(array $trackers)
    {
        $trackers_representation = array();
        foreach ($trackers as $tracker) {
            $tracker_reference = new TrackerReference();
            $tracker_reference->build($tracker);
            $trackers_representation[] = $tracker_reference;
        }
        return $trackers_representation;
    }
}

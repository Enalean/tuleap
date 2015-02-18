<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
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
use Tuleap\REST\v2\BacklogRepresentationBase;

class BacklogRepresentation extends BacklogRepresentationBase {

    public function build(array $backlog_items, array $accepted_trackers, $has_user_priority_change_permission) {
        $this->content = $backlog_items;

        $this->accept = array('trackers' => array());
        foreach ($accepted_trackers as $accepted_tracker) {
            $reference = new TrackerReference();
            $reference->build($accepted_tracker);

            $this->accept['trackers'][] = $reference;
        }

        $this->has_user_priority_change_permission = $has_user_priority_change_permission;

        return $this;
    }
}

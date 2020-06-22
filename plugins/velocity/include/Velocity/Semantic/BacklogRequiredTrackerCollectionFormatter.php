<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Tracker;

class BacklogRequiredTrackerCollectionFormatter
{
    public function formatTrackerWithoutDoneSemantic(Tracker $tracker)
    {
        $url = TRACKER_BASE_URL . "?" . http_build_query(
            [
                "tracker"  => $tracker->getId(),
                "func"     => "admin-semantic",
                "semantic" => "done"
            ]
        );

        return new MissingRequiredSemanticPresenter(
            $url,
            dgettext('tuleap-agiledashboard', 'Done')
        );
    }

    public function formatTrackerWithoutInitialEffortSemantic(Tracker $tracker)
    {
        $url = TRACKER_BASE_URL . "?" . http_build_query(
            [
                "tracker"  => $tracker->getId(),
                "func"     => "admin-semantic",
                "semantic" => "initial_effort"
            ]
        );

        return new MissingRequiredSemanticPresenter(
            $url,
            dgettext('tuleap-agiledashboard', 'Initial Effort')
        );
    }
}

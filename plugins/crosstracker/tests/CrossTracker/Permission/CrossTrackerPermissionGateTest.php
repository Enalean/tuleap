<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Permission;

require_once __DIR__ . '/../../bootstrap.php';

class CrossTrackerPermissionGateTest extends \TuleapTestCase
{
    public function itDoesNotBlockLegitimateUser()
    {
        $user    = mock('PFUser');
        $tracker = mock('Tracker');
        stub($tracker)->userCanView()->returns(true);
        $report  = mock('\\Tuleap\\CrossTracker\\CrossTrackerReport');
        stub($report)->getTrackers()->returns(array($tracker));

        $permission_gate = new CrossTrackerPermissionGate();
        $permission_gate->check($user, $report);
    }

    public function itBlocksUserThatCannotAccessToTrackers()
    {
        $user     = mock('PFUser');
        $tracker1 = mock('Tracker');
        stub($tracker1)->userCanView()->returns(true);
        $tracker2 = mock('Tracker');
        stub($tracker2)->userCanView()->returns(false);
        $report   = mock('\\Tuleap\\CrossTracker\\CrossTrackerReport');
        stub($report)->getTrackers()->returns(array($tracker1, $tracker2));

        $permission_gate = new CrossTrackerPermissionGate();
        $this->expectException('Tuleap\\CrossTracker\\Permission\\CrossTrackerUnauthorizedTrackerException');
        $permission_gate->check($user, $report);
    }
}

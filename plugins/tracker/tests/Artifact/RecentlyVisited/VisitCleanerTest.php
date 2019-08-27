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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

require_once __DIR__.'/../../bootstrap.php';

class VisitCleanerTest extends \TuleapTestCase
{
    public function itClearsVisitedArtifacts()
    {
        $user_id = 101;
        $user    = mock('PFUser');
        stub($user)->getId()->returns($user_id);

        $recently_visited_dao = mock('Tuleap\\Tracker\\Artifact\\RecentlyVisited\\RecentlyVisitedDao');
        $recently_visited_dao->expectOnce('deleteVisitByUserId', array($user_id));

        $visit_cleaner = new VisitCleaner($recently_visited_dao);
        $visit_cleaner->clearVisitedArtifacts($user);
    }
}

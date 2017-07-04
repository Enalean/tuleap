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

namespace Tuleap\Project;

use TuleapTestCase;

class HeartbeatsEntryCollectionTest extends TuleapTestCase
{
    public function itReturnsEntriesOrderedByDate()
    {
        $collection = new HeartbeatsEntryCollection(mock('Project'), mock('PFUser'));

        $entry1 = new HeartbeatsEntry(100, 'icon', 'message');
        $entry2 = new HeartbeatsEntry(50, 'icon', 'message');
        $entry3 = new HeartbeatsEntry(10, 'icon', 'message');

        $collection->add($entry3);
        $collection->add($entry1);
        $collection->add($entry2);

        $this->assertEqual(
            $collection->getLatestEntries(),
            array($entry1, $entry2, $entry3)
        );
    }
}

<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project;

use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HeartbeatsEntryCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsEntriesOrderedByDate(): void
    {
        $collection = new HeartbeatsEntryCollection(new \Project(['group_id' => 101]), UserTestBuilder::aUser()->build());

        $entry1 = new HeartbeatsEntry(100, 'message', 'fa-list');
        $entry2 = new HeartbeatsEntry(50, 'message', 'fa-list');
        $entry3 = new HeartbeatsEntry(10, 'message', 'fa-list');

        $collection->add($entry3);
        $collection->add($entry1);
        $collection->add($entry2);

        $this->assertEquals(
            [$entry1, $entry2, $entry3],
            $collection->getLatestEntries()
        );
    }
}

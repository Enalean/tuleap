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

use PHPUnit\Framework\TestCase;

final class HeartbeatsEntryCollectionTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItReturnsEntriesOrderedByDate(): void
    {
        $collection = new HeartbeatsEntryCollection(\Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class));

        $icon = \Mockery::spy(\Tuleap\Glyph\Glyph::class);

        $entry1 = new HeartbeatsEntry(100, $icon, $icon, 'message');
        $entry2 = new HeartbeatsEntry(50, $icon, $icon, 'message');
        $entry3 = new HeartbeatsEntry(10, $icon, $icon, 'message');

        $collection->add($entry3);
        $collection->add($entry1);
        $collection->add($entry2);

        $this->assertEquals(
            array($entry1, $entry2, $entry3),
            $collection->getLatestEntries()
        );
    }
}

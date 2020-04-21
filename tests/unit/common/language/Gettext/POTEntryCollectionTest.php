<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Language\Gettext;

final class POTEntryCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testItHasNoEntriesByDefault(): void
    {
        $collection = new POTEntryCollection('mydomain');

        $this->assertEquals([], $collection->getEntries());
    }

    public function testItAddsEntries(): void
    {
        $collection = new POTEntryCollection('mydomain');

        $entry1 = new POTEntry('a', 'b');
        $entry2 = new POTEntry('c', 'd');
        $collection->add('mydomain', $entry1);
        $collection->add('mydomain', $entry2);

        $this->assertEquals(array($entry1, $entry2), $collection->getEntries());
    }

    public function testItDoesNotAddEntryIfNotSameDomain(): void
    {
        $collection = new POTEntryCollection('mydomain');

        $entry = new POTEntry('a', 'b');
        $collection->add('another-domain', $entry);

        $this->assertEquals([], $collection->getEntries());
    }

    public function testItDoesNotAddTwiceTheSameEntryInSameDomain(): void
    {
        $collection = new POTEntryCollection('mydomain');

        $entry1 = new POTEntry('a', '');
        $entry2 = new POTEntry('a', '');
        $collection->add('mydomain', $entry1);
        $collection->add('mydomain', $entry2);

        $this->assertEquals(array($entry1), $collection->getEntries());
    }
}

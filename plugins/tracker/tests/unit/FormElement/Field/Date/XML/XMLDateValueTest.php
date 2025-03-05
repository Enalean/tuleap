<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Date\XML;

use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLDateValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasAttributes(): void
    {
        $value = (new XMLDateChangesetValue('start_date', new \DateTimeImmutable()))
            ->export(new \SimpleXMLElement('<changeset />'), new XMLFormElementFlattenedCollection([]));

        assertEquals('start_date', $value['field_name']);
        assertEquals('date', $value['type']);
    }

    public function testDateValueAsFormat(): void
    {
        $date  = new \DateTimeImmutable('2021-02-15 14:17');
        $value = (new XMLDateChangesetValue('start_date', $date))
            ->export(new \SimpleXMLElement('<changeset />'), new XMLFormElementFlattenedCollection([]));

        assertEquals('ISO8601', $value->value['format']);
        assertEquals($date->format('c'), $value->value);
    }
}

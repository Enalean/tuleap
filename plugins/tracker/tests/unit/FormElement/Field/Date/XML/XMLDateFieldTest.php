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

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;

final class XMLDateFieldTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasNoPropertiesByDefault(): void
    {
        $field = (new XMLDateField('some_id', 'start_date'))
            ->export(new \SimpleXMLElement('<formElements />'));

        assertFalse(isset($field->properties));
    }

    public function testItHasDateTimeProperty(): void
    {
        $field = (new XMLDateField('some_id', 'start_date'))
            ->withDateTime()
            ->export(new \SimpleXMLElement('<formElements />'));

        assertEquals('1', $field->properties['display_time']);
    }
}

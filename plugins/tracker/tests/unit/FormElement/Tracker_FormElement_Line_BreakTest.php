<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_StaticField_LineBreak;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Line_BreakTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalLanguageMock;

    public function testFetchDescription(): void
    {
        $line_break = new Tracker_FormElement_StaticField_LineBreak(
            2,
            254,
            0,
            'linebreak2',
            'Line Break Label',
            'Line Break Description that should not be kept',
            true,
            'S',
            false,
            false,
            25,
            null,
        );

        self::assertEquals('Line Break Label', $line_break->getLabel());
        self::assertEquals('', $line_break->getDescription());
        self::assertEquals('', $line_break->getCannotRemoveMessage());
    }
}

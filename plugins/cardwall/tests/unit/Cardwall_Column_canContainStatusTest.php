<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Cardwall_Column;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Cardwall_Column_canContainStatusTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Cardwall_Column $column;

    protected function setUp(): void
    {
        $this->column = new Cardwall_Column(100, 'whatever', 'whatever');
    }

    public function testItReturnsTrueOnNoneColumnIfStatusIsNone(): void
    {
        self::assertTrue($this->column->canContainStatus('None'));
    }

    public function testItReturnsTrueOnNoneColumnIfStatusIsNull(): void
    {
        self::assertTrue($this->column->canContainStatus(null));
    }
}

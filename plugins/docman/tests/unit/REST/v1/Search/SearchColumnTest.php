<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchColumnTest extends TestCase
{
    public function testBuildForHardcodedProperty(): void
    {
        $column = SearchColumn::buildForHardcodedProperty('name', 'label');

        self::assertEquals('name', $column->getName());
        self::assertEquals('label', $column->getLabel());
        self::assertFalse($column->isMultipleValueAllowed());
        self::assertFalse($column->isCustomProperty());
    }

    public function buildForSingleValueCustomProperty(): void
    {
        $column = SearchColumn::buildForSingleValueCustomProperty('name', 'label');

        self::assertEquals('name', $column->getName());
        self::assertEquals('label', $column->getLabel());
        self::assertFalse($column->isMultipleValueAllowed());
        self::assertTrue($column->isCustomProperty());
    }

    public function testBuildForCustomProperty(): void
    {
        $column = SearchColumn::buildForMultipleValuesCustomProperty('name', 'label');

        self::assertEquals('name', $column->getName());
        self::assertEquals('label', $column->getLabel());
        self::assertTrue($column->isMultipleValueAllowed());
        self::assertTrue($column->isCustomProperty());
    }
}

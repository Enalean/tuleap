<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Container;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ColumnContainerBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FieldsetContainerBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class FieldsExtractorTest extends TestCase
{
    private FieldsExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new FieldsExtractor();
    }

    public function testItExtractsFieldsDirectlyInsideTheContainer(): void
    {
        $field_01 = IntegerFieldBuilder::anIntField(1)->build();
        $field_02 = IntegerFieldBuilder::anIntField(2)->build();

        $container = FieldsetContainerBuilder::aFieldset(1486)->containsFormElements($field_01, $field_02)->build();

        self::assertSame(
            [$field_01, $field_02],
            $this->extractor->extractFieldsInsideContainer($container)
        );
    }

    public function testItExtractsFieldsDirectlyInsideTheContainerAndInsideContainerIntoContainer(): void
    {
        $field_01 = IntegerFieldBuilder::anIntField(1)->build();
        $field_02 = IntegerFieldBuilder::anIntField(2)->build();
        $field_03 = IntegerFieldBuilder::anIntField(3)->build();

        $column_02 = ColumnContainerBuilder::aColumn(12)->containsFormElements($field_02)->build();
        $column_03 = ColumnContainerBuilder::aColumn(13)->containsFormElements($field_03)->build();
        $column_01 = ColumnContainerBuilder::aColumn(11)->containsFormElements($column_02, $column_03)->build();

        $container = FieldsetContainerBuilder::aFieldset(14)->containsFormElements($field_01, $column_01)->build();

        self::assertSame(
            [$field_01, $field_02, $field_03],
            $this->extractor->extractFieldsInsideContainer($container)
        );
    }
}

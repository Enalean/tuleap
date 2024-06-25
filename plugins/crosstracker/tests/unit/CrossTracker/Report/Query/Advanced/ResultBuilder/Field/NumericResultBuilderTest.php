<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field;

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectResultKey;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;

final class NumericResultBuilderTest extends TestCase
{
    private DuckTypedFieldSelect $field;
    private string $alias;

    protected function setUp(): void
    {
        $field_result = DuckTypedFieldSelect::build(
            RetrieveFieldTypeStub::withDetectionOfType(),
            'numeric_field',
            [
                IntFieldBuilder::anIntField(1)->build(),
                FloatFieldBuilder::aFloatField(2)->build(),
            ],
            [1, 2],
        );
        self::assertTrue(Result::isOk($field_result));
        $this->field = $field_result->value;
        $this->alias = (string) SelectResultKey::fromDuckTypedField($this->field);
    }

    public function testItReturnsCorrectValues(): void
    {
        $builder = new NumericResultBuilder();
        $result  = $builder->getResult($this->field, [
            ['id' => 51, "int_$this->alias" => 6, "float_$this->alias" => null],
            ['id' => 52, "int_$this->alias" => null, "float_$this->alias" => 3.1415],
            ['id' => 53, "int_$this->alias" => null, "float_$this->alias" => null],
        ]);

        self::assertNotNull($result->selected);
        self::assertSame('numeric_field', $result->selected->name);
        $values = $result->values;
        self::assertCount(3, $values);
        self::assertArrayHasKey(51, $values);
        self::assertArrayHasKey(52, $values);
        self::assertArrayHasKey(53, $values);
        self::assertInstanceOf(NumericResultRepresentation::class, $values[51]->value);
        self::assertInstanceOf(NumericResultRepresentation::class, $values[52]->value);
        self::assertInstanceOf(NumericResultRepresentation::class, $values[53]->value);
        self::assertSame(6, $values[51]->value->value);
        self::assertSame(3.1415, $values[52]->value->value);
        self::assertSame(null, $values[53]->value->value);
    }
}

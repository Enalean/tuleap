<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Option;

/**
 * PHPUnit will always succeed these tests. They are meant to raise errors in Psalm instead.
 * Psalm will raise errors here if breaking changes are introduced on type annotations.
 */
final class TypeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNothingIsAssignableToGivenType(): void
    {
        $test = new class {
            /** @var Option<string> */
            public Option $expectation;
        };

        $test->expectation = Option::nothing(\Psl\Type\string());
        self::assertTrue($test->expectation->isNothing());
    }

    private function getNullable(bool $return_null): ?int
    {
        return ($return_null) ? null : 54;
    }

    public function testFromNullableInfersType(): void
    {
        $test = new class {
            /** @var Option<int> */
            public Option $expectation;
        };

        $test->expectation = Option::fromNullable($this->getNullable(true));
        self::assertTrue($test->expectation->isNothing());

        $test->expectation = Option::fromNullable($this->getNullable(false));
        self::assertTrue($test->expectation->isValue());
        self::assertSame(54, $test->expectation->unwrapOr(0));
    }

    public function testCanMapValueToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::fromValue(new CustomValueType(90, 'coparty'));

        $test = new class {
            /** @var Option<int> */
            public Option $expectation;
        };

        $test->expectation = $option->map(static fn(CustomValueType $value) => 40);
        self::assertTrue($test->expectation->isValue());
        self::assertSame(40, $test->expectation->unwrapOr(null));
    }

    public function testCanMapNothingToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::nothing(\Psl\Type\int());

        $test = new class {
            /** @var Option<CustomValueType> */
            public Option $expectation;
        };

        $test->expectation = $option->map(static fn(int $value) => new CustomValueType($value, 'conduction'));
        self::assertTrue($test->expectation->isNothing());
    }

    public function testAndThenCanMapValueToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::fromValue(10);

        $test = new class {
            /** @var Option<string> */
            public Option $expectation;
        };

        $test->expectation = $option->andThen(static fn(int $value) => Option::fromValue('observe'));
        self::assertTrue($test->expectation->isValue());
        self::assertSame('observe', $test->expectation->unwrapOr(null));
    }

    public function testAndThenCanMapNothingToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::nothing(\Psl\Type\string());

        $test = new class {
            /** @var Option<CustomValueType> */
            public Option $expectation;
        };

        $test->expectation = $option->andThen(static fn(string $value) => Option::fromValue(
            new CustomValueType(55, $value)
        ));
        self::assertTrue($test->expectation->isNothing());
    }

    public function testOrElseCanMapValueToAUnionType(): void
    {
        $option = Option::fromValue('observe');

        $test = new class {
            /** @var Option<int|string> */
            public Option $expectation;
        };

        $test->expectation = $option->orElse(static fn() => Option::fromValue(646));
        self::assertTrue($test->expectation->isValue());
        self::assertSame('observe', $test->expectation->unwrapOr(null));
    }

    public function testOrElseCanMapNothingToAUnionType(): void
    {
        $option = Option::nothing(\Psl\Type\string());

        $test = new class {
            /** @var Option<int|string> */
            public Option $expectation;
        };

        $test->expectation = $option->orElse(static fn() => Option::fromValue(55));
        self::assertTrue($test->expectation->isValue());
        self::assertSame(55, $test->expectation->unwrapOr(null));
    }

    public function testMapOrCanMapToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::fromValue('33');

        $test = new class {
            public int $expectation;
        };

        $test->expectation = $option->mapOr(
            static fn(string $value) => (int) $value + 10,
            99
        );
        self::assertTrue($option->isValue());
        self::assertSame(43, $test->expectation);
    }

    public function testMapOrCanReturnADifferentTypeThanTheMappedTypeOrTheInitialOption(): void
    {
        $option = Option::nothing(\Psl\Type\string());

        $test = new class {
            public int|CustomValueType $expectation;
        };

        $test->expectation = $option->mapOr(
            static fn(string $value) => (int) $value + 10,
            new CustomValueType(21, 'pick')
        );
        self::assertInstanceOf(CustomValueType::class, $test->expectation);
    }

    public function testUnwrapOrCanDefaultToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::nothing(\Psl\Type\string());

        $test = new class {
            public int|string $expectation;
        };

        $test->expectation = $option->unwrapOr(101);
        self::assertSame(101, $test->expectation);
    }
}

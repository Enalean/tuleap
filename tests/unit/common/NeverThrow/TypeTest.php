<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\NeverThrow;

use Tuleap\NeverThrow\Tests\CustomErrorType;
use Tuleap\NeverThrow\Tests\CustomValueType;

/**
 * PHPUnit will always succeed these tests. They are meant to raise errors in Psalm instead.
 * Psalm will raise errors here if breaking changes are introduced on type annotations.
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TypeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testIsOkAndNotIsErrAllowAccessingValue(): void
    {
        $result = Result::ok(123)->andThen(static fn(int $value) => $value === 123 ? Result::ok(456) : Result::err('Ooops'));
        if (Result::isOk($result)) {
            self::assertSame(456, $result->value);
        }
        if (! Result::isErr($result)) {
            self::assertSame(456, $result->value);
        }
    }

    public function testIsErrAndNotIsOkAllowAccessingError(): void
    {
        $result = Result::ok(123)->andThen(
            static fn(int $value) => $value === 123 ? Result::err('Ooops') : Result::ok(456)
        );
        if (Result::isErr($result)) {
            self::assertSame('Ooops', $result->error);
        }
        if (! Result::isOk($result)) {
            self::assertSame('Ooops', $result->error);
        }
    }

    public function testAndThenCombinesIdenticalScalarOkTypes(): void
    {
        $result = Result::ok(123);


        $test = new class {
            /**
             * @var Ok<int> | Err<mixed>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->andThen(
            static function (int $value) {
                if ($value === 1) {
                    return Result::err('Ooops');
                }
                return Result::ok($value + 456);
            }
        );
        self::assertTrue(Result::isOk($test->expectation));
    }

    public function testAndThenReplacesOkTypeWhenDifferentFromInitial(): void
    {
        $result = Result::ok(123);

        $test = new class {
            /**
             * @var Ok<string> | Err<mixed>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->andThen(
            static function (int $value) {
                if ($value === 1) {
                    return Result::err('Ooops');
                }
                return Result::ok('Code: ' . $value);
            }
        );
        self::assertTrue(Result::isOk($test->expectation));
    }

    public function testAndThenCombinesIdenticalScalarErrorTypes(): void
    {
        /**
         * @var Ok<int> | Err<string> $result
         */
        $result = Result::ok(123);

        $test = new class {
            /**
             * @var Ok<int> | Err<string>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->andThen(
            static fn(int $value) => Result::err('Error with value: ' . $value)
        );
        self::assertTrue(Result::isErr($test->expectation));
    }

    public function testAndThenCombinesIdenticalCustomErrorTypes(): void
    {
        /**
         * @var Ok<int> | Err<CustomErrorType> $result
         */
        $result = Result::ok(123);

        $test = new class {
            /**
             * @var Ok<int> | Err<CustomErrorType>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->andThen(
            static fn(int $value) => Result::err(new CustomErrorType('Ooops', 456))
        );
        self::assertTrue(Result::isErr($test->expectation));
    }

    public function testAndThenCreatesAUnionTypeForError(): void
    {
        /**
         * @var Ok<int> | Err<CustomErrorType> $result
         */
        $result = Result::ok(123);

        $test = new class {
            /**
             * @var Ok<int> | Err<CustomErrorType> | Err<string[]>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->andThen(
            static fn(int $value) => Result::err(['Ooops'])
        );
        self::assertTrue(Result::isErr($test->expectation));
    }

    public function testOrElseCombinesIdenticalScalarOkTypes(): void
    {
        /**
         * @var Ok<int> | Err<string> $result
         */
        $result = Result::err('Ooops');

        $test = new class {
            /**
             * @var Ok<int> | Err<string>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->orElse(
            static fn(string $error) => Result::ok(456)
        );
        self::assertTrue(Result::isOk($test->expectation));
    }

    public function testOrElseCombinesIdenticalCustomValueTypes(): void
    {
        /**
         * @var Ok<CustomValueType> | Err<string> $result
         */
        $result = Result::err('Ooops');

        $test = new class {
            /**
             * @var Ok<CustomValueType> | Err<string>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->orElse(
            static fn(string $error) => Result::ok(new CustomValueType(123, 'A value'))
        );
        self::assertTrue(Result::isOk($test->expectation));
    }

    public function testOrElseCombinesIdenticalScalarErrorTypes(): void
    {
        $result = Result::err(404);

        $test = new class {
            /**
             * @var Ok<mixed> | Err<int>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->orElse(
            static function (int $error) {
                if ($error === 1) {
                    return Result::ok(123);
                }
                return Result::err(500);
            }
        );
        self::assertTrue(Result::isErr($test->expectation));
    }

    public function testOrElseReplacesErrorTypeWhenDifferentFromInitial(): void
    {
        $result = Result::err(404);

        $test = new class {
            /**
             * @var Ok<mixed> | Err<string>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->orElse(
            static function (int $error) {
                if ($error === 1) {
                    return Result::ok(123);
                }
                return Result::err('Code: ' . $error);
            }
        );
        self::assertTrue(Result::isErr($test->expectation));
    }

    public function testOrElseCreatesAUnionTypeForOk(): void
    {
        /**
         * @var Ok<CustomValueType> | Err<int> $result
         */
        $result = Result::err(404);

        $test = new class {
            /**
             * @var Ok<CustomValueType> | Ok<array<int>> | Err<mixed>
             */
            public Ok|Err $expectation;
        };

        $test->expectation = $result->orElse(
            static fn(int $error) => Result::ok([123])
        );
        self::assertTrue(Result::isOk($test->expectation));
    }
}

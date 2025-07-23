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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OkTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Closure $error_callback;

    #[\Override]
    protected function setUp(): void
    {
        $this->error_callback = static fn(string $message): string => 'An error: ' . $message;
    }

    public function testItBuildsAnOk(): void
    {
        $ok = new Ok("It's okay");
        self::assertTrue(Result::isOk($ok));
        self::assertFalse(Result::isErr($ok));
        self::assertSame("It's okay", $ok->value);
    }

    public function testOkAreComparable(): void
    {
        $ok       = new Ok(10);
        $other_ok = new Ok(10);
        self::assertEquals($ok, $other_ok);
        $third_ok = new Ok(20);
        self::assertNotEquals($ok, $third_ok);
    }

    public function testMapAppliesCallbackToValueAndReturnsANewResult(): void
    {
        $ok     = new Ok(10);
        $new_ok = $ok->map(static fn(int $value) => $value * 2);

        self::assertNotSame($ok, $new_ok);
        self::assertTrue(Result::isOk($new_ok));
        self::assertSame(20, $new_ok->value);
    }

    public function testMapErrLeavesValueUntouched(): void
    {
        $ok     = new Ok(10);
        $new_ok = $ok->mapErr($this->error_callback);

        self::assertTrue(Result::isOk($new_ok));
        self::assertEquals($ok, $new_ok);
        self::assertNotSame($ok, $new_ok);
    }

    public function testAndThenReturnsOkFromCallingCallbackOnValue(): void
    {
        $ok     = new Ok(10);
        $new_ok = $ok->andThen(static fn(int $value) => new Ok('Value is ' . $value));

        self::assertTrue(Result::isOk($new_ok));
        self::assertNotSame($ok, $new_ok);
        self::assertSame('Value is 10', $new_ok->value);
    }

    public function testAndThenReturnsErrFromCallingCallbackOnValue(): void
    {
        $ok      = new Ok(10);
        $new_err = $ok->andThen(static fn(int $value) => new Err('Invalid value ' . $value));

        self::assertTrue(Result::isErr($new_err));
        self::assertSame('Invalid value 10', $new_err->error);
    }

    public function testAndThenCanFlattenNestedResults(): void
    {
        $ok      = new Ok(new Ok(10));
        $flat_ok = $ok->andThen(static fn(IResult $value) => $value);

        self::assertTrue(Result::isOk($flat_ok));
        self::assertNotSame($ok, $flat_ok);
        self::assertSame(10, $flat_ok->value);
    }

    public function testOrElseLeavesValueUntouched(): void
    {
        $ok     = new Ok(10);
        $new_ok = $ok->orElse(static fn(string $message) => new Ok(66));

        self::assertTrue(Result::isOk($new_ok));
        self::assertEquals($ok, $new_ok);
        self::assertNotSame($ok, $new_ok);
    }

    public function testMatchAppliesOkCallbackAndReturnsItsValue(): void
    {
        $ok = new Ok(10);
        self::assertSame(
            20,
            $ok->match(
                static fn(int $value) => $value * 2,
                $this->error_callback
            )
        );
    }

    public function testUnwrapOrReturnsTheValue(): void
    {
        $ok = new Ok(10);
        self::assertSame(10, $ok->unwrapOr(66));
    }
}

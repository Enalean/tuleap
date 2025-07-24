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
final class ErrTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Closure $ok_callback;

    #[\Override]
    protected function setUp(): void
    {
        $this->ok_callback = static fn(int $value): int => $value * 2;
    }

    public function testItBuildsAnErr(): void
    {
        $err = new Err('Ooops');
        self::assertFalse(Result::isOk($err));
        self::assertTrue(Result::isErr($err));
        self::assertSame('Ooops', $err->error);
    }

    public function testErrAreComparable(): void
    {
        $err       = new Err('Ooops');
        $other_err = new Err('Ooops');
        self::assertEquals($err, $other_err);
        $third_err = new Err('Failure');
        self::assertNotEquals($err, $third_err);
    }

    public function testMapLeavesErrUntouched(): void
    {
        $err     = new Err('Ooops');
        $new_err = $err->map($this->ok_callback);

        self::assertTrue(Result::isErr($new_err));
        self::assertEquals($err, $new_err);
        self::assertNotSame($err, $new_err);
    }

    public function testMapErrAppliesCallbackToErrorAndReturnsANewResult(): void
    {
        $err     = new Err('Ooops');
        $new_err = $err->mapErr(static fn(string $message) => 'An error: ' . $message);

        self::assertNotSame($err, $new_err);
        self::assertTrue(Result::isErr($new_err));
        self::assertSame('An error: Ooops', $new_err->error);
    }

    public function testAndThenLeavesErrUntouched(): void
    {
        $err     = new Err('Ooops');
        $new_err = $err->andThen(static fn(int $value) => new Ok('Value is ' . $value));

        self::assertTrue(Result::isErr($new_err));
        self::assertEquals($err, $new_err);
        self::assertNotSame($err, $new_err);
    }

    public function testOrElseReturnsOkFromCallingCallbackOnErr(): void
    {
        $err    = new Err('Ooops');
        $new_ok = $err->orElse(static fn(string $message) => new Ok('Error recovered: ' . $message));

        self::assertTrue(Result::isOk($new_ok));
        self::assertSame('Error recovered: Ooops', $new_ok->value);
    }

    public function testOrElseReturnsErrFromCallingCallbackOnErr(): void
    {
        $err     = new Err(123);
        $new_err = $err->orElse(static fn(int $code) => new Err('Error code: ' . $code));

        self::assertTrue(Result::isErr($new_err));
        self::assertNotSame($err, $new_err);
        self::assertSame('Error code: 123', $new_err->error);
    }

    public function testOrElseCanFlattenNestedResults(): void
    {
        $err      = new Err(new Err('Ooops'));
        $flat_err = $err->orElse(static fn(IResult $err) => $err);

        self::assertTrue(Result::isErr($flat_err));
        self::assertNotSame($err, $flat_err);
        self::assertSame('Ooops', $flat_err->error);
    }

    public function testMatchAppliesErrorCallbackAndReturnsItsValue(): void
    {
        $err = new Err('Ooops');
        self::assertSame(
            'An error: Ooops',
            $err->match(
                $this->ok_callback,
                static fn(string $message) => 'An error: ' . $message
            )
        );
    }

    public function testUnwrapOrReturnsTheGivenDefaultValue(): void
    {
        $err = new Err('Ooops');
        self::assertSame(66, $err->unwrapOr(66));
    }
}

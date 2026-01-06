<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\SeatManagement;

use Tuleap\NeverThrow\Result;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class PublicKeyPresenceCheckerTest extends TestCase
{
    public function testItReturnsErrWhenNoPublicKeyIsPresent(): void
    {
        $checker = new PublicKeyPresenceChecker();

        self::assertTrue(Result::isErr($checker->checkPresence(__DIR__ . '/_fixtures/empty')));
    }

    public function testItReturnsOkWhenAtLeastOnePublicKeyIsPresent(): void
    {
        $checker = new PublicKeyPresenceChecker();

        self::assertTrue(Result::isOk($checker->checkPresence(__DIR__ . '/_fixtures/keys')));
    }

    public function testItReturnsErrWhenKeysDirectoryDoesNotExist(): void
    {
        $checker = new PublicKeyPresenceChecker();

        self::assertTrue(Result::isErr($checker->checkPresence('abc')));
    }

    public function testItReturnsErrWhenKeysDirectoryIsAFile(): void
    {
        $checker = new PublicKeyPresenceChecker();

        self::assertTrue(Result::isErr($checker->checkPresence(__FILE__)));
    }
}

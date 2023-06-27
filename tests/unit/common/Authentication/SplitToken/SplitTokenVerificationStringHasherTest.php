<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Authentication\SplitToken;

use Tuleap\Cryptography\ConcealedString;

final class SplitTokenVerificationStringHasherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testVerificationStringCanBeHashed(): void
    {
        $access_key_verification_string = $this->createMock(SplitTokenVerificationString::class);
        $access_key_verification_string->method('getString')
            ->willReturn(new ConcealedString('random_string'));

        $hasher = new SplitTokenVerificationStringHasher();

        $hashed_verification_string = $hasher->computeHash($access_key_verification_string);

        self::assertNotEmpty($hashed_verification_string);
    }

    public function testVerificationStringCanBeVerified(): void
    {
        $access_key_verification_string = $this->createMock(SplitTokenVerificationString::class);
        $access_key_verification_string->method('getString')
            ->willReturn(new ConcealedString('random_string'));
        $precomputed_hash_value = '528b36022f3bc7b1de66f30bbd011bb84fce3067c5eb593400d1b39055c32891';

        $hasher = new SplitTokenVerificationStringHasher();

        self::assertTrue($hasher->verifyHash($access_key_verification_string, $precomputed_hash_value));
    }
}

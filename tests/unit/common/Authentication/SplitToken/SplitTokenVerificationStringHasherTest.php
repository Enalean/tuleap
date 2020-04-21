<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;

class SplitTokenVerificationStringHasherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testVerificationStringCanBeHashed()
    {
        $access_key_verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $access_key_verification_string->shouldReceive('getString')
            ->andReturns(new ConcealedString('random_string'));

        $hasher = new SplitTokenVerificationStringHasher();

        $hashed_verification_string = $hasher->computeHash($access_key_verification_string);

        $this->assertNotEmpty($hashed_verification_string);
    }

    public function testVerificationStringCanBeVerified()
    {
        $access_key_verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $access_key_verification_string->shouldReceive('getString')
            ->andReturns(new ConcealedString('random_string'));
        $precomputed_hash_value = '528b36022f3bc7b1de66f30bbd011bb84fce3067c5eb593400d1b39055c32891';

        $hasher = new SplitTokenVerificationStringHasher();

        $this->assertTrue($hasher->verifyHash($access_key_verification_string, $precomputed_hash_value));
    }
}

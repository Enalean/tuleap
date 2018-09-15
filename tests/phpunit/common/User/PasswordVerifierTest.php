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

namespace Tuleap\User;

use Tuleap\user\PasswordVerifier;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PasswordVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider passwordProvider
     */
    public function testPasswordVerification($is_password_valid, $legacy_hashed_password, $given_password, $expected_result)
    {
        $password_handler = \Mockery::mock(\PasswordHandler::class);
        $password_handler->shouldReceive('verifyHashPassword')->andReturns($is_password_valid);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserPw');
        $user->shouldReceive('getLegacyUserPw')->andReturns($legacy_hashed_password);

        $password_verifier = new PasswordVerifier($password_handler);
        $is_valid          = $password_verifier->verifyPassword($user, $given_password);

        $this->assertEquals($expected_result, $is_valid);
    }

    public function passwordProvider()
    {
        return [
            [false, md5('not valid'), 'Tuleap', false],
            [true, md5('not valid'), 'Tuleap', true],
            [false, md5('Tuleap'), 'Tuleap', true],
            [true, md5('Tuleap'), 'Tuleap', true]
        ];
    }
}

<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\User;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;

class PasswordVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider passwordProvider
     */
    public function testPasswordVerification(bool $is_password_valid, string $legacy_hashed_password, string $given_password, bool $expected_result): void
    {
        $password_handler = \Mockery::mock(\PasswordHandler::class);
        $password_handler->shouldReceive('verifyHashPassword')->andReturns($is_password_valid);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserPw')->andReturn('something');
        $user->shouldReceive('getLegacyUserPw')->andReturns($legacy_hashed_password);

        $password_verifier = new PasswordVerifier($password_handler);
        $is_valid          = $password_verifier->verifyPassword($user, new ConcealedString($given_password));

        $this->assertEquals($expected_result, $is_valid);
    }

    public function passwordProvider(): array
    {
        return [
            [false, md5('not valid'), 'Tuleap', false],
            [true, md5('not valid'), 'Tuleap', true],
            [false, md5('Tuleap'), 'Tuleap', true],
            [true, md5('Tuleap'), 'Tuleap', true]
        ];
    }

    public function testCannotVerifyAPasswordWhenTheUserDoesNotHaveOne(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserPw')->andReturn(null);

        $password_verifier = new PasswordVerifier(\Mockery::mock(\PasswordHandler::class));

        $this->assertFalse($password_verifier->verifyPassword($user, new ConcealedString('password')));
    }
}

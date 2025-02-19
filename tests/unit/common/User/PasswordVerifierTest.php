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

use Tuleap\Cryptography\ConcealedString;

final class PasswordVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testPasswordVerificationSuccess(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('getUserPw')->willReturn('something');

        $password_verifier = new PasswordVerifier($this->buildPasswordHandler());

        self::assertTrue($password_verifier->verifyPassword($user, new ConcealedString('something')));
    }

    public function testCannotVerifyAPasswordWhenTheUserDoesNotHaveOne(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('getUserPw')->willReturn(null);

        $password_verifier = new PasswordVerifier($this->buildPasswordHandler());

        $this->assertFalse($password_verifier->verifyPassword($user, new ConcealedString('password')));
    }

    private function buildPasswordHandler(): \PasswordHandler
    {
        return new class () implements \PasswordHandler {
            public function __construct()
            {
            }

            public function verifyHashPassword(ConcealedString $plain_password, string $hash_password): bool
            {
                return true;
            }

            public function computeHashPassword(ConcealedString $plain_password): string
            {
                throw new \RuntimeException('Not expected to be called');
            }

            public function isPasswordNeedRehash(string $hash_password): bool
            {
                throw new \RuntimeException('Not expected to be called');
            }
        };
    }
}

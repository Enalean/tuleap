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

namespace Tuleap\Password;

use PHPUnit\Framework\TestCase;
use Tuleap\Password\HaveIBeenPwned\PwnedPasswordChecker;

class PasswordCompromiseValidatorTest extends TestCase
{
    public function testPasswordAcceptation()
    {
        $pwned_password_checker = $this->createMock(PwnedPasswordChecker::class);
        $pwned_password_checker->method('isPasswordCompromised')->willReturn(false);

        $password_validator = new PasswordCompromiseValidator($pwned_password_checker);
        $this->assertTrue($password_validator->validate('not_compromised_password'));
    }

    public function testPasswordRejection()
    {
        $pwned_password_checker = $this->createMock(PwnedPasswordChecker::class);
        $pwned_password_checker->method('isPasswordCompromised')->willReturn(true);

        $password_validator = new PasswordCompromiseValidator($pwned_password_checker);
        $this->assertFalse($password_validator->validate('compromised_password'));
    }
}

<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;
use StandardPasswordHandler;

final class StandardPasswordHandlerTest extends TestCase
{
    private const HASHED_WORD = 'Tuleap';
    private const MD5_HASH    = '$1$aa$yURlyd26QSZm44JDJtAuT/';
    private const SHA512_HASH = '$6$rounds=50000$aaaaaaaaaaaaaaaa$sI3KG111U.auUFeiO.PlagitndbvX7gVnzecFnuCBs/TV.qUCla1mz3Zmaq1JTJWT2eErh4ea9Iw995D//pfo/';
    private const BCRYPT_HASH = '$2y$10$aaaaaaaaaaaaaaaaaaaaaOBkuuklGwTPKAtCkHvUX3Lk5UDwjLI5O';

    /**
     * @var StandardPasswordHandler
     */
    private $password_handler;

    public function setUp(): void
    {
        $this->password_handler = new StandardPasswordHandler();
    }

    /**
     * @test
     */
    public function itVerifyPassword(): void
    {
        $check_password = $this->password_handler->verifyHashPassword(self::HASHED_WORD, self::BCRYPT_HASH);
        $this->assertTrue($check_password);
    }

    /**
     * @test
     */
    public function itVerifyPasswordSaltedMD5(): void
    {
        $check_password = $this->password_handler->verifyHashPassword(self::HASHED_WORD, self::MD5_HASH);
        $this->assertTrue($check_password);
    }

    /**
     * @test
     */
    public function itVerifyPasswordSaltedSHA512(): void
    {
        $check_password = $this->password_handler->verifyHashPassword(self::HASHED_WORD, self::SHA512_HASH);
        $this->assertTrue($check_password);
    }

    /**
     * @test
     */
    public function itChecksIfRehashingIsNeeded(): void
    {
        $rehash_needed = $this->password_handler->isPasswordNeedRehash(self::MD5_HASH);
        $this->assertTrue($rehash_needed);
        $rehash_needed = $this->password_handler->isPasswordNeedRehash(self::SHA512_HASH);
        $this->assertTrue($rehash_needed);
    }
}

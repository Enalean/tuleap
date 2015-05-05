<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class WeakPasswordHandlerTest extends TuleapTestCase {
    const HASHED_WORD = 'Tuleap';
    const MD5_HASH    = '$1$aa$yURlyd26QSZm44JDJtAuT/';

    private $password_handler;

    public function setUp() {
        parent::setUp();
        $this->password_handler = new WeakPasswordHandler();
    }

    public function itVerifyPassword() {
        $check_password = $this->password_handler->verifyHashPassword(self::HASHED_WORD, self::MD5_HASH);
        $this->assertTrue($check_password);
    }

    public function itCheckIfRehashingIsNeeded() {
        $rehash_needed = $this->password_handler->isPasswordNeedRehash(self::MD5_HASH);
        $this->assertFalse($rehash_needed);
    }

}
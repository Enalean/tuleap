<?php
/**
* Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class RuleNameTest extends TuleapTestCase {

    public function itForbidsSpecialsChars() {
        $rule = new RuleName();

        // Special chars
        $this->assertFalse($rule->isValid("user\n"));
        $this->assertFalse($rule->isValid("\nuser"));
        $this->assertFalse($rule->isValid("user\nuser"));
        $this->assertFalse($rule->isValid("user\0"));
        $this->assertFalse($rule->isValid("\0user"));
        $this->assertFalse($rule->isValid("user\0user"));

        // Punctuation
        $this->assertFalse($rule->isValid("user a"));
        $this->assertFalse($rule->isValid("user;a"));

        // Specials char allowed
        $this->assertTrue($rule->isValid("user.a"));
        $this->assertTrue($rule->isValid("user-a"));
        $this->assertTrue($rule->isValid("user_a"));

        $this->assertFalse($rule->isValid("user,a"));
        $this->assertFalse($rule->isValid("user:a"));
        $this->assertFalse($rule->isValid("user'a"));
        $this->assertFalse($rule->isValid("user`a"));
        $this->assertFalse($rule->isValid('user"a'));
        $this->assertFalse($rule->isValid("user<a"));
        $this->assertFalse($rule->isValid("user>a"));
        $this->assertFalse($rule->isValid("user[a"));
        $this->assertFalse($rule->isValid("user]a"));
        $this->assertFalse($rule->isValid("user{a"));
        $this->assertFalse($rule->isValid("user}a"));
        $this->assertFalse($rule->isValid("user(a"));
        $this->assertFalse($rule->isValid("user)a"));
        $this->assertFalse($rule->isValid("user|a"));

        // Maths
        $this->assertFalse($rule->isValid("user+a"));
        $this->assertFalse($rule->isValid("user=a"));
        $this->assertFalse($rule->isValid("user/a"));

        // Misc
        $this->assertFalse($rule->isValid("user~a"));
        $this->assertFalse($rule->isValid("user@a"));
        $this->assertFalse($rule->isValid("user!a"));
        $this->assertFalse($rule->isValid('user#a'));
        $this->assertFalse($rule->isValid('user$a'));
        $this->assertFalse($rule->isValid("user%a"));
        $this->assertFalse($rule->isValid("user^a"));
        $this->assertFalse($rule->isValid("user&a"));
        $this->assertFalse($rule->isValid("user*a"));

        // Accent & language
        $this->assertFalse($rule->isValid("useré"));
        $this->assertFalse($rule->isValid("userç"));
    }

    public function itForbidsSpaces() {
        $rule = new RuleName();

        $this->assertFalse($rule->isValid("user test"));
        $this->assertFalse($rule->isValid(" usertest"));
        $this->assertFalse($rule->isValid("usertest "));
        $this->assertFalse($rule->isValid("user test 1"));
        $this->assertTrue($rule->isValid("user"));
    }

    public function itForbidsBeginnigByAChar() {
        $rule = new RuleName();

        $this->assertFalse($rule->isValid("1"));
        $this->assertFalse($rule->isValid("1deux"));
        $this->assertTrue($rule->isValid("a1b"));
    }
}
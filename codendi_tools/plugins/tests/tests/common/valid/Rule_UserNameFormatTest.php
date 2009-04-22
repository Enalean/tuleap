<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/valid/Rule.class.php');

class Rule_UserNameFormatTest extends UnitTestCase {

    function UnitTestCase($name = 'Rule_UserNameFormat test') {
        $this->UnitTestCase($name);
    }

    function testOk() {
        $r = new Rule_UserNameFormat();
        $this->assertTrue($r->isValid("user"));
        $this->assertTrue($r->isValid("user_name"));
        $this->assertTrue($r->isValid("user-name"));
    }

    function testReservedNames() {
        $r = new Rule_UserNameFormat();
        $this->assertFalse($r->isValid("root"));
        $this->assertFalse($r->isValid("bin"));
        $this->assertFalse($r->isValid("daemon"));
        $this->assertFalse($r->isValid("adm"));
        $this->assertFalse($r->isValid("lp"));
        $this->assertFalse($r->isValid("sync"));
        $this->assertFalse($r->isValid("shutdown"));
        $this->assertFalse($r->isValid("halt"));
        $this->assertFalse($r->isValid("mail"));
        $this->assertFalse($r->isValid("news"));
        $this->assertFalse($r->isValid("uucp"));
        $this->assertFalse($r->isValid("operator"));
        $this->assertFalse($r->isValid("games"));
        $this->assertFalse($r->isValid("mysql"));
        $this->assertFalse($r->isValid("httpd"));
        $this->assertFalse($r->isValid("nobody"));
        $this->assertFalse($r->isValid("dummy"));
        $this->assertFalse($r->isValid("www"));
        $this->assertFalse($r->isValid("cvs"));
        $this->assertFalse($r->isValid("shell"));
        $this->assertFalse($r->isValid("ftp"));
        $this->assertFalse($r->isValid("irc"));
        $this->assertFalse($r->isValid("debian"));
        $this->assertFalse($r->isValid("ns"));
        $this->assertFalse($r->isValid("download"));

        $this->assertFalse($r->isValid("ROOT"));
        $this->assertFalse($r->isValid("WWW"));
        $this->assertFalse($r->isValid("DUMMY"));
    }

    function testCVSNames() {
        $r = new Rule_UserNameFormat();
        $this->assertFalse($r->isValid("anoncvs_"));
        $this->assertFalse($r->isValid("anoncvs_test"));
        $this->assertFalse($r->isValid("ANONCVS_"));
        $this->assertFalse($r->isValid("ANONCVS_TEST"));
    }

    function testMinLen() {
        $r = new Rule_UserNameFormat();
        $this->assertFalse($r->isValid(""));
        $this->assertFalse($r->isValid("a"));
        $this->assertFalse($r->isValid("ab"));
        $this->assertTrue($r->isValid("abc"));
        $this->assertTrue($r->isValid("abcd"));
    }

    function testMaxLen() {
        $r = new Rule_UserNameFormat();
        $this->assertTrue($r->isValid("abcdefghijklmnopkrstuvwxyzabc"));
        $this->assertTrue($r->isValid("abcdefghijklmnopkrstuvwxyzabcd"));
        $this->assertFalse($r->isValid("abcdefghijklmnopkrstuvwxyzabcde"));
    }

    function testIllegalChars() {
        $r = new Rule_UserNameFormat();

        // Special chars
        $this->assertFalse($r->isValid("user\n"));
        $this->assertFalse($r->isValid("\nuser"));
        $this->assertFalse($r->isValid("user\nuser"));
        $this->assertFalse($r->isValid("user\0"));
        $this->assertFalse($r->isValid("\0user"));
        $this->assertFalse($r->isValid("user\0user"));

        // Punctuation
        $this->assertFalse($r->isValid("user a"));
        $this->assertFalse($r->isValid("user;a"));
        $this->assertFalse($r->isValid("user.a"));
        $this->assertFalse($r->isValid("user,a"));
        $this->assertFalse($r->isValid("user:a"));
        $this->assertFalse($r->isValid("user'a"));
        $this->assertFalse($r->isValid("user`a"));
        $this->assertFalse($r->isValid('user"a'));
        $this->assertFalse($r->isValid("user<a"));
        $this->assertFalse($r->isValid("user>a"));
        $this->assertFalse($r->isValid("user[a"));
        $this->assertFalse($r->isValid("user]a"));
        $this->assertFalse($r->isValid("user{a"));
        $this->assertFalse($r->isValid("user}a"));
        $this->assertFalse($r->isValid("user(a"));
        $this->assertFalse($r->isValid("user)a"));
        $this->assertFalse($r->isValid("user|a"));

        // Maths
        $this->assertFalse($r->isValid("user+a"));
        $this->assertFalse($r->isValid("user=a"));
        $this->assertFalse($r->isValid("user/a"));

        // Misc
        $this->assertFalse($r->isValid("user~a"));
        $this->assertFalse($r->isValid("user@a"));
        $this->assertFalse($r->isValid("user!a"));
        $this->assertFalse($r->isValid('user#a'));
        $this->assertFalse($r->isValid('user$a'));
        $this->assertFalse($r->isValid("user%a"));
        $this->assertFalse($r->isValid("user^a"));
        $this->assertFalse($r->isValid("user&a"));
        $this->assertFalse($r->isValid("user*a"));

        // Accent & language
        $this->assertFalse($r->isValid("useré"));
        $this->assertFalse($r->isValid("userç"));
    }

}
?>

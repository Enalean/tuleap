<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

Mock::generatePartial('Rule_UserName', 'Rule_UserNameTestVersion', array('_getProjectManager', '_getUserManager', '_getBackend'));

Mock::generate('UserManager');
Mock::generate('PFUser');

Mock::generate('ProjectManager');
Mock::generate('Project');

Mock::generate('Backend');

Mock::generate('BaseLanguage');

class Rule_UserNameTest extends TuleapTestCase
{

    function testReservedNames()
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isReservedName("root"));
        $this->assertTrue($r->isReservedName("bin"));
        $this->assertTrue($r->isReservedName("daemon"));
        $this->assertTrue($r->isReservedName("adm"));
        $this->assertTrue($r->isReservedName("lp"));
        $this->assertTrue($r->isReservedName("sync"));
        $this->assertTrue($r->isReservedName("shutdown"));
        $this->assertTrue($r->isReservedName("halt"));
        $this->assertTrue($r->isReservedName("mail"));
        $this->assertTrue($r->isReservedName("news"));
        $this->assertTrue($r->isReservedName("uucp"));
        $this->assertTrue($r->isReservedName("operator"));
        $this->assertTrue($r->isReservedName("games"));
        $this->assertTrue($r->isReservedName("mysql"));
        $this->assertTrue($r->isReservedName("httpd"));
        $this->assertTrue($r->isReservedName("nobody"));
        $this->assertTrue($r->isReservedName("dummy"));
        $this->assertTrue($r->isReservedName("www"));
        $this->assertTrue($r->isReservedName("cvs"));
        $this->assertTrue($r->isReservedName("shell"));
        $this->assertTrue($r->isReservedName("ftp"));
        $this->assertTrue($r->isReservedName("irc"));
        $this->assertTrue($r->isReservedName("ns"));
        $this->assertTrue($r->isReservedName("download"));
        $this->assertTrue($r->isReservedName("munin"));
        $this->assertTrue($r->isReservedName("mailman"));
        $this->assertTrue($r->isReservedName("ftpadmin"));
        $this->assertTrue($r->isReservedName("codendiadm"));
        $this->assertTrue($r->isReservedName("imadmin-bot"));
        $this->assertTrue($r->isReservedName("apache"));
        $this->assertTrue($r->isReservedName("nscd"));
        $this->assertTrue($r->isReservedName("git"));
        $this->assertTrue($r->isReservedName("gitolite"));

        $this->assertTrue($r->isReservedName("ROOT"));
        $this->assertTrue($r->isReservedName("WWW"));
        $this->assertTrue($r->isReservedName("DUMMY"));
    }

    function testReservedPrefix()
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isReservedName("forge__"));
        $this->assertTrue($r->isReservedName("forge__tutu"));
        $this->assertFalse($r->isReservedName("forge_loic"));
        $this->assertFalse($r->isReservedName("forgeron"));
    }

    function testCVSNames()
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isCvsAccount("anoncvs_"));
        $this->assertTrue($r->isCvsAccount("anoncvs_test"));
        $this->assertTrue($r->isCvsAccount("ANONCVS_"));
        $this->assertTrue($r->isCvsAccount("ANONCVS_TEST"));
    }

    function testMinLen()
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->lessThanMin(""));
        $this->assertTrue($r->lessThanMin("a"));
        $this->assertTrue($r->lessThanMin("ab"));

        $this->assertFalse($r->lessThanMin("abc"));
        $this->assertFalse($r->lessThanMin("abcd"));
    }

    function testMaxLen()
    {
        $r = new Rule_UserName();
        $this->assertFalse($r->greaterThanMax("abcdefghijklmnopkrstuvwxyzabc"));
        $this->assertFalse($r->greaterThanMax("abcdefghijklmnopkrstuvwxyzabcd"));
        $this->assertTrue($r->greaterThanMax("abcdefghijklmnopkrstuvwxyzabcde"));
    }

    function testIllegalChars()
    {
        $r = new Rule_UserName();

        // Special chars
        $this->assertTrue($r->containsIllegalChars("user\n"));
        $this->assertTrue($r->containsIllegalChars("\nuser"));
        $this->assertTrue($r->containsIllegalChars("user\nuser"));
        $this->assertTrue($r->containsIllegalChars("user\0"));
        $this->assertTrue($r->containsIllegalChars("\0user"));
        $this->assertTrue($r->containsIllegalChars("user\0user"));

        // Punctuation
        $this->assertTrue($r->containsIllegalChars("user a"));
        $this->assertTrue($r->containsIllegalChars("user;a"));

        // Since rev #12892, this char is allowed
        $this->assertFalse($r->containsIllegalChars("user.a"));

        $this->assertTrue($r->containsIllegalChars("user,a"));
        $this->assertTrue($r->containsIllegalChars("user:a"));
        $this->assertTrue($r->containsIllegalChars("user'a"));
        $this->assertTrue($r->containsIllegalChars("user`a"));
        $this->assertTrue($r->containsIllegalChars('user"a'));
        $this->assertTrue($r->containsIllegalChars("user<a"));
        $this->assertTrue($r->containsIllegalChars("user>a"));
        $this->assertTrue($r->containsIllegalChars("user[a"));
        $this->assertTrue($r->containsIllegalChars("user]a"));
        $this->assertTrue($r->containsIllegalChars("user{a"));
        $this->assertTrue($r->containsIllegalChars("user}a"));
        $this->assertTrue($r->containsIllegalChars("user(a"));
        $this->assertTrue($r->containsIllegalChars("user)a"));
        $this->assertTrue($r->containsIllegalChars("user|a"));

        // Maths
        $this->assertTrue($r->containsIllegalChars("user+a"));
        $this->assertTrue($r->containsIllegalChars("user=a"));
        $this->assertTrue($r->containsIllegalChars("user/a"));

        // Misc
        $this->assertTrue($r->containsIllegalChars("user~a"));
        $this->assertTrue($r->containsIllegalChars("user@a"));
        $this->assertTrue($r->containsIllegalChars("user!a"));
        $this->assertTrue($r->containsIllegalChars('user#a'));
        $this->assertTrue($r->containsIllegalChars('user$a'));
        $this->assertTrue($r->containsIllegalChars("user%a"));
        $this->assertTrue($r->containsIllegalChars("user^a"));
        $this->assertTrue($r->containsIllegalChars("user&a"));
        $this->assertTrue($r->containsIllegalChars("user*a"));

        // Accent & language
        $this->assertTrue($r->containsIllegalChars("useré"));
        $this->assertTrue($r->containsIllegalChars("userç"));
    }

    function testBeginnigByAChar()
    {
        $r = new Rule_UserName();

        $this->assertFalse($r->atLeastOneChar("1"));
        $this->assertFalse($r->atLeastOneChar("1deux"));
        $this->assertTrue($r->atLeastOneChar("a1b"));
    }

    function testNoSpaces()
    {
        $r = new Rule_UserName();

        $this->assertFalse($r->noSpaces("user test"));
        $this->assertFalse($r->noSpaces(" usertest"));
        $this->assertFalse($r->noSpaces("usertest "));
        $this->assertFalse($r->noSpaces("user test 1"));
        $this->assertTrue($r->noSpaces("user"));
    }

    function testUserNameNotExists()
    {
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', null);

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getUserManager', $um);

        $this->assertFalse($r->isAlreadyUserName("usertest"));
    }

    function testUserNameExists()
    {
        $u = mock('PFUser');

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', $u, array("usertest"));

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getUserManager', $um);

        $this->assertTrue($r->isAlreadyUserName("usertest"));
    }

    function testProjectNameNotExists()
    {
        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', null);

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getProjectManager', $pm);

        $this->assertFalse($r->isAlreadyProjectName("usertest"));
    }

    function testProjectNameExists()
    {
        $p = new MockProject($this);

        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', $p, array("usertest"));

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getProjectManager', $pm);

        $this->assertTrue($r->isAlreadyProjectName("usertest"));
    }

    function testUnixUserExists()
    {
        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', true);
        $backend->setReturnValue('unixGroupExists', false);

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getBackend', $backend);

        $this->assertTrue($r->isSystemName("usertest"));
    }

    function testUnixGroupExists()
    {
        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', true);

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getBackend', $backend);

        $this->assertTrue($r->isSystemName("usertest"));
    }

    function testUnixUserAndGroupExists()
    {
        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', true);
        $backend->setReturnValue('unixGroupExists', true);

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getBackend', $backend);

        $this->assertTrue($r->isSystemName("usertest"));
    }

    function testNoUnixUserOrGroupExists()
    {
        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', false);

        $r = new Rule_UserNameTestVersion($this);
        $r->setReturnValue('_getBackend', $backend);

        $this->assertFalse($r->isSystemName("usertest"));
    }
}

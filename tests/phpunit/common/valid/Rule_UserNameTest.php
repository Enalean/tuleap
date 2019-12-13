<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_UserNameTest extends TestCase
{
    use GlobalLanguageMock;

    public function testReservedNames(): void
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

    public function testReservedPrefix(): void
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isReservedName("forge__"));
        $this->assertTrue($r->isReservedName("forge__tutu"));
        $this->assertFalse($r->isReservedName("forge_loic"));
        $this->assertFalse($r->isReservedName("forgeron"));
    }

    public function testCVSNames(): void
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isCvsAccount("anoncvs_"));
        $this->assertTrue($r->isCvsAccount("anoncvs_test"));
        $this->assertTrue($r->isCvsAccount("ANONCVS_"));
        $this->assertTrue($r->isCvsAccount("ANONCVS_TEST"));
    }

    public function testMinLen(): void
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->lessThanMin(""));
        $this->assertTrue($r->lessThanMin("a"));
        $this->assertTrue($r->lessThanMin("ab"));

        $this->assertFalse($r->lessThanMin("abc"));
        $this->assertFalse($r->lessThanMin("abcd"));
    }

    public function testMaxLen(): void
    {
        $r = new Rule_UserName();
        $this->assertFalse($r->greaterThanMax("abcdefghijklmnopkrstuvwxyzabc"));
        $this->assertFalse($r->greaterThanMax("abcdefghijklmnopkrstuvwxyzabcd"));
        $this->assertTrue($r->greaterThanMax("abcdefghijklmnopkrstuvwxyzabcde"));
    }

    public function testIllegalChars(): void
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

    public function testBeginnigByAChar(): void
    {
        $r = new Rule_UserName();

        $this->assertFalse($r->atLeastOneChar("1"));
        $this->assertFalse($r->atLeastOneChar("1deux"));
        $this->assertTrue($r->atLeastOneChar("a1b"));
    }

    public function testNoSpaces(): void
    {
        $r = new Rule_UserName();

        $this->assertFalse($r->noSpaces("user test"));
        $this->assertFalse($r->noSpaces(" usertest"));
        $this->assertFalse($r->noSpaces("usertest "));
        $this->assertFalse($r->noSpaces("user test 1"));
        $this->assertTrue($r->noSpaces("user"));
    }

    public function testUserNameNotExists(): void
    {
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUserName')->andReturns(null);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);

        $this->assertFalse($r->isAlreadyUserName("usertest"));
    }

    public function testUserNameExists(): void
    {
        $u = \Mockery::spy(\PFUser::class);

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUserName')->with("usertest")->andReturns($u);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);

        $this->assertTrue($r->isAlreadyUserName("usertest"));
    }

    public function testProjectNameNotExists(): void
    {
        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(null);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getProjectManager')->andReturns($pm);

        $this->assertFalse($r->isAlreadyProjectName("usertest"));
    }

    public function testProjectNameExists(): void
    {
        $p = \Mockery::spy(\Project::class);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->with("usertest")->andReturns($p);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getProjectManager')->andReturns($pm);

        $this->assertTrue($r->isAlreadyProjectName("usertest"));
    }

    public function testUnixUserExists(): void
    {
        $backend = \Mockery::spy(\Backend::class);
        $backend->shouldReceive('unixUserExists')->andReturns(true);
        $backend->shouldReceive('unixGroupExists')->andReturns(false);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getBackend')->andReturns($backend);

        $this->assertTrue($r->isSystemName("usertest"));
    }

    public function testUnixGroupExists(): void
    {
        $backend = \Mockery::spy(\Backend::class);
        $backend->shouldReceive('unixUserExists')->andReturns(false);
        $backend->shouldReceive('unixGroupExists')->andReturns(true);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getBackend')->andReturns($backend);

        $this->assertTrue($r->isSystemName("usertest"));
    }

    public function testUnixUserAndGroupExists(): void
    {
        $backend = \Mockery::spy(\Backend::class);
        $backend->shouldReceive('unixUserExists')->andReturns(true);
        $backend->shouldReceive('unixGroupExists')->andReturns(true);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getBackend')->andReturns($backend);

        $this->assertTrue($r->isSystemName("usertest"));
    }

    public function testNoUnixUserOrGroupExists(): void
    {
        $backend = \Mockery::spy(\Backend::class);
        $backend->shouldReceive('unixUserExists')->andReturns(false);
        $backend->shouldReceive('unixGroupExists')->andReturns(false);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getBackend')->andReturns($backend);

        $this->assertFalse($r->isSystemName("usertest"));
    }
}

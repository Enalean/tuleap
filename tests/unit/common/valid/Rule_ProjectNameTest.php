<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalSVNPollution;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_ProjectNameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalSVNPollution;

    public function testNoUnderscore(): void
    {
        $r = new Rule_ProjectName();
        $this->assertFalse($r->isDNSCompliant("group_test"));
        $this->assertFalse($r->isDNSCompliant("_grouptest"));
        $this->assertFalse($r->isDNSCompliant("grouptest_"));
        $this->assertFalse($r->isDNSCompliant("group_test_1"));
    }

    public function testNoSpaces(): void
    {
        $r = new Rule_ProjectName();
        $this->assertFalse($r->noSpaces("group test"));
        $this->assertFalse($r->noSpaces(" grouptest"));
        $this->assertFalse($r->noSpaces("grouptest "));
        $this->assertFalse($r->noSpaces("group test 1"));
    }

    public function testNoDot(): void
    {
        $r = new Rule_ProjectName();
        $this->assertFalse($r->isValid("group.test"));
        $this->assertFalse($r->isValid(".grouptest"));
        $this->assertFalse($r->isValid("grouptest."));
        $this->assertFalse($r->isValid("group.test.1"));
    }

    public function testNotStartWithNonAlphanumericCharacter(): void
    {
        $r = new Rule_ProjectName();
        self::assertFalse($r->isValid("-shortname"));
        self::assertFalse($r->isValid("'shortname"));
        self::assertFalse($r->isValid("&shortname"));
        self::assertFalse($r->isValid("\"shortname"));
        self::assertFalse($r->isValid("(shortname"));
        self::assertFalse($r->isValid("-shortname"));
        self::assertFalse($r->isValid("çshortname"));
        self::assertFalse($r->isValid("àshortname"));
        self::assertFalse($r->isValid(")shortname"));
        self::assertFalse($r->isValid("=shortname"));
        self::assertFalse($r->isValid("?shortname"));
        self::assertFalse($r->isValid(",shortname"));
        self::assertFalse($r->isValid(";shortname"));
        self::assertFalse($r->isValid(".shortname"));
        self::assertFalse($r->isValid("/shortname"));
        self::assertFalse($r->isValid("!shortname"));
        self::assertFalse($r->isValid("*shortname"));
        self::assertFalse($r->isValid("%shortname"));
        self::assertFalse($r->isValid("£shortname"));
    }

    public function testReservedNames(): void
    {
        $r = new Rule_ProjectName();
        $this->assertTrue($r->isReservedName("www"));
        $this->assertTrue($r->isReservedName("www1"));
        $this->assertTrue($r->isReservedName("cvs"));
        $this->assertTrue($r->isReservedName("cvs1"));
        $this->assertTrue($r->isReservedName("shell"));
        $this->assertTrue($r->isReservedName("shell1"));
        $this->assertTrue($r->isReservedName("ftp"));
        $this->assertTrue($r->isReservedName("ftp1"));
        $this->assertTrue($r->isReservedName("irc"));
        $this->assertTrue($r->isReservedName("irc1"));
        $this->assertTrue($r->isReservedName("news"));
        $this->assertTrue($r->isReservedName("news1"));
        $this->assertTrue($r->isReservedName("mail"));
        $this->assertTrue($r->isReservedName("mail1"));
        $this->assertTrue($r->isReservedName("ns"));
        $this->assertTrue($r->isReservedName("ns1"));
        $this->assertTrue($r->isReservedName("download"));
        $this->assertTrue($r->isReservedName("download1"));
        $this->assertTrue($r->isReservedName("pub"));
        $this->assertTrue($r->isReservedName("users"));
        $this->assertTrue($r->isReservedName("compile"));
        $this->assertTrue($r->isReservedName("lists"));
        $this->assertTrue($r->isReservedName("slayer"));
        $this->assertTrue($r->isReservedName("orbital"));
        $this->assertTrue($r->isReservedName("tokyojoe"));
        $this->assertTrue($r->isReservedName("webdev"));
        $this->assertTrue($r->isReservedName("monitor"));
        $this->assertTrue($r->isReservedName("mirrors"));
        $this->assertTrue($r->isReservedName("mirror"));
        $this->assertTrue($r->isReservedName("git"));
        $this->assertTrue($r->isReservedName("gitolite"));
    }

    public function testReservedNamesUpperCase(): void
    {
        $r = new Rule_ProjectName();
        $this->assertTrue($r->isReservedName("WWW"));
        $this->assertTrue($r->isReservedName("WWW1"));
        $this->assertTrue($r->isReservedName("CVS"));
        $this->assertTrue($r->isReservedName("CVS1"));
        $this->assertTrue($r->isReservedName("SHELL"));
        $this->assertTrue($r->isReservedName("SHELL1"));
        $this->assertTrue($r->isReservedName("FTP"));
        $this->assertTrue($r->isReservedName("FTP1"));
        $this->assertTrue($r->isReservedName("IRC"));
        $this->assertTrue($r->isReservedName("IRC1"));
        $this->assertTrue($r->isReservedName("NEWS"));
        $this->assertTrue($r->isReservedName("NEWS1"));
        $this->assertTrue($r->isReservedName("MAIL"));
        $this->assertTrue($r->isReservedName("MAIL1"));
        $this->assertTrue($r->isReservedName("NS"));
        $this->assertTrue($r->isReservedName("NS1"));
        $this->assertTrue($r->isReservedName("DOWNLOAD"));
        $this->assertTrue($r->isReservedName("DOWNLOAD1"));
        $this->assertTrue($r->isReservedName("PUB"));
        $this->assertTrue($r->isReservedName("USERS"));
        $this->assertTrue($r->isReservedName("COMPILE"));
        $this->assertTrue($r->isReservedName("LISTS"));
        $this->assertTrue($r->isReservedName("SLAYER"));
        $this->assertTrue($r->isReservedName("ORBITAL"));
        $this->assertTrue($r->isReservedName("TOKYOJOE"));
        $this->assertTrue($r->isReservedName("WEBDEV"));
        $this->assertTrue($r->isReservedName("MONITOR"));
        $this->assertTrue($r->isReservedName("MIRRORS"));
        $this->assertTrue($r->isReservedName("MIRROR"));
    }

    public function testReservedPrefix(): void
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isReservedName("forge__"));
        $this->assertFalse($r->isReservedName("forgeron"));
    }

    public function testIsNameAvailableSuccess(): void
    {
        $r = $this->createPartialMock(\Rule_ProjectName::class, ['_getBackend']);

        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('isNameAvailable')->with('foobar')->willReturn(true);

        $backendSystem = $this->createMock(\BackendSystem::class);

        $r->method('_getBackend')->willReturnMap(
            [
                ['SVN', $backendSVN],
                ['System', $backendSystem],
            ]
        );


        $this->assertTrue($r->isNameAvailable('foobar'));
    }

    public function testIsNameAvailableSVNFailure(): void
    {
        $r = $this->createPartialMock(\Rule_ProjectName::class, ['_getBackend']);

        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('isNameAvailable')->with('foobar')->willReturn(false);
        $r->method('_getBackend')->with('SVN')->willReturn($backendSVN);

        $this->assertFalse($r->isNameAvailable('foobar'));
    }
}

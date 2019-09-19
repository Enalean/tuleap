<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

Mock::generatePartial('Rule_ProjectName', 'Rule_ProjectNameTestVersion', array('_getBackend'));

Mock::generate('BaseLanguage');

Mock::generate('BackendSystem');

Mock::generate('BackendSVN');

Mock::generate('BackendCVS');


class Rule_ProjectNameTest extends TuleapTestCase
{

    function testNoUnderscore()
    {
        $r = new Rule_ProjectName();
        $this->assertFalse($r->isDNSCompliant("group_test"));
        $this->assertFalse($r->isDNSCompliant("_grouptest"));
        $this->assertFalse($r->isDNSCompliant("grouptest_"));
        $this->assertFalse($r->isDNSCompliant("group_test_1"));
    }

    function testNoSpaces()
    {
        $r = new Rule_ProjectName();
        $this->assertFalse($r->noSpaces("group test"));
        $this->assertFalse($r->noSpaces(" grouptest"));
        $this->assertFalse($r->noSpaces("grouptest "));
        $this->assertFalse($r->noSpaces("group test 1"));
    }

    function testNoDot()
    {
        $r = new Rule_ProjectName();
        $this->assertFalse($r->isValid("group.test"));
        $this->assertFalse($r->isValid(".grouptest"));
        $this->assertFalse($r->isValid("grouptest."));
        $this->assertFalse($r->isValid("group.test.1"));
    }

    function testReservedNames()
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

    function testReservedNamesUpperCase()
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

    function testReservedPrefix()
    {
        $r = new Rule_UserName();
        $this->assertTrue($r->isReservedName("forge__"));
        $this->assertFalse($r->isReservedName("forgeron"));
    }

    function testIsNameAvailableSuccess()
    {
        $r = new Rule_ProjectNameTestVersion();

        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('isNameAvailable', true, array('foobar'));
        $r->setReturnValue('_getBackend', $backendSVN, array('SVN'));

        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('isNameAvailable', true, array('foobar'));
        $r->setReturnValue('_getBackend', $backendCVS, array('CVS'));

        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('isProjectNameAvailable', true, array('foobar'));
        $r->setReturnValue('_getBackend', $backendSystem, array('System'));

        $this->assertTrue($r->isNameAvailable('foobar'));
    }

    function testIsNameAvailableSVNFailure()
    {
        $r = new Rule_ProjectNameTestVersion();

        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('isNameAvailable', false, array('foobar'));
        $r->setReturnValue('_getBackend', $backendSVN, array('SVN'));

        $backendCVS = new MockBackendCVS($this);
        $backendCVS->expectNever('isNameAvailable', array('foobar'));

        $this->assertFalse($r->isNameAvailable('foobar'));
    }

    function testIsNameAvailableCVSFailure()
    {
        $r = new Rule_ProjectNameTestVersion();

        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('isNameAvailable', true, array('foobar'));
        $r->setReturnValue('_getBackend', $backendSVN, array('SVN'));

        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('isNameAvailable', false, array('foobar'));
        $r->setReturnValue('_getBackend', $backendCVS, array('CVS'));

        $backendSystem = new MockBackendSystem($this);
        $backendSystem->expectNever('isProjectNameAvailable', array('foobar'));

        $this->assertFalse($r->isNameAvailable('foobar'));
    }

    function testIsNameAvailableSystemFailure()
    {
        $r = new Rule_ProjectNameTestVersion();

        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('isNameAvailable', true, array('foobar'));
        $r->setReturnValue('_getBackend', $backendSVN, array('SVN'));

        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('isNameAvailable', true, array('foobar'));
        $r->setReturnValue('_getBackend', $backendCVS, array('CVS'));

        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('isProjectNameAvailable', false, array('foobar'));
        $r->setReturnValue('_getBackend', $backendSystem, array('System'));

        $this->assertFalse($r->isNameAvailable('foobar'));
    }
}

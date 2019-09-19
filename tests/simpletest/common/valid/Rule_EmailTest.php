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


class Rule_EmailTest extends TuleapTestCase
{

    function UnitTestCase($name = 'Rule_Email test')
    {
        $this->UnitTestCase($name);
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($GLOBALS['sys_disable_subdomains']);
    }

    function testWithoutSubDomains()
    {
        $GLOBALS['sys_disable_subdomains'] = 1;
        $r = new Rule_Email();
        $this->assertTrue($r->isValid('user@codendi'));
        $this->assertTrue($r->isValid('user@codendi.domain.com'));
    }

    function testWithSubDomains()
    {
        $GLOBALS['sys_disable_subdomains'] = 0;
        $r = new Rule_Email();
        $this->assertFalse($r->isValid('user@codendi'));
        $this->assertTrue($r->isValid('user@codendi.domain.com'));
    }

    function testSpecialCharsWoSD()
    {
        $GLOBALS['sys_disable_subdomains'] = 1;
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@codendi.domain.com\n"));
        $this->assertFalse($r->isValid("\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0"));
        $this->assertFalse($r->isValid("\0user@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0user@codendi.domain.com"));
    }

    function testSpecialCharsWithSD()
    {
        $GLOBALS['sys_disable_subdomains'] = 0;
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@codendi.domain.com\n"));
        $this->assertFalse($r->isValid("\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0"));
        $this->assertFalse($r->isValid("\0user@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0user@codendi.domain.com"));
    }

    function testMultipleEmails()
    {
        $r = new Rule_Email(',');

        $this->assertTrue($r->isValid("user@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com, user2@codendi.domain.com"));

        $this->assertFalse($r->isValid("user@codendi.domain.com; user2@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com, toto l'asticot"));
        $this->assertFalse($r->isValid("toto l'asticot, user@codendi.domain.com"));
        $this->assertFalse($r->isValid("toto l'asticot"));
    }

    function testMultipleEmailsMultipleSeparator()
    {
        $r = new Rule_Email('[,;]');

        $this->assertTrue($r->isValid("user@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com, user2@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com; user2@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com; user2@codendi.domain.com, user3@codendi.domain.com"));

        $this->assertFalse($r->isValid("user@codendi.domain.com; toto l'asticot, user3@codendi.domain.com"));
    }
}

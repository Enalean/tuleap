<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/valid/Rule.class.php');

class Rule_EmailTest extends UnitTestCase {

    function UnitTestCase($name = 'Valid_String test') {
        $this->UnitTestCase($name);
    }

    function tearDown() {
        unset($GLOBALS['sys_disable_subdomains']);
    }

    function testWithoutSubDomains() {
        $GLOBALS['sys_disable_subdomains'] = 1;
        $r = new Rule_Email();
        $this->assertTrue($r->isValid('user@codex'));
        $this->assertTrue($r->isValid('user@codex.domain.com'));
    }

    function testWithSubDomains() {
        $GLOBALS['sys_disable_subdomains'] = 0;
        $r = new Rule_Email();
        $this->assertFalse($r->isValid('user@codex'));
        $this->assertTrue($r->isValid('user@codex.domain.com'));
    }

    function testSpecialCharsWoSD() {
        $GLOBALS['sys_disable_subdomains'] = 1;
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@codex.domain.com\n"));
        $this->assertFalse($r->isValid("\nuser@codex.domain.com"));
        $this->assertFalse($r->isValid("user@codex.domain.com\nuser@codex.domain.com"));
        $this->assertFalse($r->isValid("user@codex.domain.com\0"));
        $this->assertFalse($r->isValid("\0user@codex.domain.com"));
        $this->assertFalse($r->isValid("user@codex.domain.com\0user@codex.domain.com"));
    }

    function testSpecialCharsWithSD() {
        $GLOBALS['sys_disable_subdomains'] = 0;
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@codex.domain.com\n"));
        $this->assertFalse($r->isValid("\nuser@codex.domain.com"));
        $this->assertFalse($r->isValid("user@codex.domain.com\nuser@codex.domain.com"));
        $this->assertFalse($r->isValid("user@codex.domain.com\0"));
        $this->assertFalse($r->isValid("\0user@codex.domain.com"));
        $this->assertFalse($r->isValid("user@codex.domain.com\0user@codex.domain.com"));
    }

}

?>

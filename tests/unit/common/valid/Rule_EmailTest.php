<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 */

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_EmailTest extends TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    public function testWithoutSubDomains()
    {
        ForgeConfig::set('sys_disable_subdomains', 1);
        $r = new Rule_Email();
        $this->assertTrue($r->isValid('user@codendi'));
        $this->assertTrue($r->isValid('user@codendi.domain.com'));
    }

    public function testWithSubDomains()
    {
        ForgeConfig::set('sys_disable_subdomains', 0);
        $r = new Rule_Email();
        $this->assertFalse($r->isValid('user@codendi'));
        $this->assertTrue($r->isValid('user@codendi.domain.com'));
    }

    public function testSpecialCharsWoSD()
    {
        ForgeConfig::set('sys_disable_subdomains', 1);
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@codendi.domain.com\n"));
        $this->assertFalse($r->isValid("\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0"));
        $this->assertFalse($r->isValid("\0user@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0user@codendi.domain.com"));
    }

    public function testSpecialCharsWithSD()
    {
        ForgeConfig::set('sys_disable_subdomains', 0);
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@codendi.domain.com\n"));
        $this->assertFalse($r->isValid("\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\nuser@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0"));
        $this->assertFalse($r->isValid("\0user@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com\0user@codendi.domain.com"));
    }

    public function testMultipleEmails()
    {
        $r = new Rule_Email(',');

        $this->assertTrue($r->isValid("user@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com, user2@codendi.domain.com"));

        $this->assertFalse($r->isValid("user@codendi.domain.com; user2@codendi.domain.com"));
        $this->assertFalse($r->isValid("user@codendi.domain.com, toto l'asticot"));
        $this->assertFalse($r->isValid("toto l'asticot, user@codendi.domain.com"));
        $this->assertFalse($r->isValid("toto l'asticot"));
    }

    public function testMultipleEmailsMultipleSeparator()
    {
        $r = new Rule_Email('[,;]');

        $this->assertTrue($r->isValid("user@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com, user2@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com; user2@codendi.domain.com"));
        $this->assertTrue($r->isValid("user@codendi.domain.com; user2@codendi.domain.com, user3@codendi.domain.com"));

        $this->assertFalse($r->isValid("user@codendi.domain.com; toto l'asticot, user3@codendi.domain.com"));
    }
}

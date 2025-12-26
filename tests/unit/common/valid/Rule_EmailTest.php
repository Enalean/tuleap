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


//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Rule_EmailTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBase(): void
    {
        $r = new Rule_Email();
        $this->assertTrue($r->isValid('user@tuleap'));
        $this->assertTrue($r->isValid('user@tuleap.example.com'));
    }

    public function testSpecialChars(): void
    {
        $r = new Rule_Email();
        $this->assertFalse($r->isValid("user@tuleap.example.com\n"));
        $this->assertFalse($r->isValid("\nuser@tuleap.example.com"));
        $this->assertFalse($r->isValid("user@tuleap.example.com\nuser@tuleap.example.com"));
        $this->assertFalse($r->isValid("user@tuleap.example.com\0"));
        $this->assertFalse($r->isValid("\0user@tuleap.example.com"));
        $this->assertFalse($r->isValid("user@tuleap.example.com\0user@tuleap.example.com"));
    }

    public function testMultipleEmails(): void
    {
        $r = new Rule_Email(',');

        $this->assertTrue($r->isValid('user@tuleap.example.com'));
        $this->assertTrue($r->isValid('user@tuleap.example.com, user2@tuleap.example.com'));

        $this->assertFalse($r->isValid('user@tuleap.example.com; user2@tuleap.example.com'));
        $this->assertFalse($r->isValid("user@tuleap.example.com, toto l'asticot"));
        $this->assertFalse($r->isValid("toto l'asticot, user@tuleap.example.com"));
        $this->assertFalse($r->isValid("toto l'asticot"));
    }

    public function testMultipleEmailsMultipleSeparator(): void
    {
        $r = new Rule_Email('[,;]');

        $this->assertTrue($r->isValid('user@tuleap.example.com'));
        $this->assertTrue($r->isValid('user@tuleap.example.com, user2@tuleap.example.com'));
        $this->assertTrue($r->isValid('user@tuleap.example.com; user2@tuleap.example.com'));
        $this->assertTrue($r->isValid('user@tuleap.example.com; user2@tuleap.example.com, user3@tuleap.example.com'));

        $this->assertFalse($r->isValid("user@tuleap.example.com; toto l'asticot, user3@tuleap.example.com"));
    }
}

<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_FRSFileNameTest extends TestCase
{

    public function testNameValid(): void
    {
        $r = new Rule_FRSFileName();
        $this->assertTrue($r->isValid('toto.txt'));

        $this->assertTrue($r->isValid('toto tutu.txt'));
    }

    private function assertStringWithChar($c): void
    {
        $r = new Rule_FRSFileName();

        // start
        $this->assertFalse($r->isValid($c . 'tototutu'), $c . " is not allowed");

        // middle
        $this->assertFalse($r->isValid('toto' . $c . 'tutu'), $c . " is not allowed");

        // end
        $this->assertFalse($r->isValid('tototutu' . $c), $c . " is not allowed");
    }

    public function testNameContainsInvalidCharacterAnywhere(): void
    {
        $str = "`!\"$%^,&*();=|{}<>?/";
        for ($i = 0; $i < strlen($str); $i++) {
            $this->assertStringWithChar($str[$i]);
        }
    }

    public function testNameContainsSpecialCharAtBeginning(): void
    {
        $r = new Rule_FRSFileName();
        $this->assertTrue($r->isValid('toto@tutu'));

        $this->assertTrue($r->isValid('toto~tutu'));

        $this->assertFalse($r->isValid('@toto'));

        $this->assertFalse($r->isValid('~toto'));
    }

    public function testNameContainsDot(): void
    {
        $r = new Rule_FRSFileName();

        $this->assertFalse($r->isValid('../coin'));

        $this->assertFalse($r->isValid('zata/../toto'));
    }
}

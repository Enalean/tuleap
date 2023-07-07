<?php
/**
* Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;

class RuleNameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Tuleap\SVN\Dao&MockObject $dao;
    private \Project $project;
    private RuleName $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao     = $this->createMock(\Tuleap\SVN\Dao::class);
        $this->project = ProjectTestBuilder::aProject()->build();
        $this->rule    = new RuleName($this->project, $this->dao);
    }

    public function testItVerifyRepositoryNameNotAlreadyUsedInProject(): void
    {
        $this->dao->method('doesRepositoryAlreadyExist')->willReturnMap([
            ["repository1", $this->project, true],
            ["repository2", $this->project, false],
        ]);

        self::assertFalse($this->rule->isValid("repository1"));
        self::assertTrue($this->rule->isValid("repository2"));
    }

    public function testItForbidsSpecialsChars(): void
    {
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);

        // Special chars
        self::assertFalse($this->rule->isValid("user\n"));
        self::assertFalse($this->rule->isValid("\nuser"));
        self::assertFalse($this->rule->isValid("user\nuser"));
        self::assertFalse($this->rule->isValid("user\0"));
        self::assertFalse($this->rule->isValid("\0user"));
        self::assertFalse($this->rule->isValid("user\0user"));

        // Punctuation
        self::assertFalse($this->rule->isValid("user a"));
        self::assertFalse($this->rule->isValid("user;a"));

        // Specials char allowed
        self::assertTrue($this->rule->isValid("user.a"));
        self::assertTrue($this->rule->isValid("user-a"));
        self::assertTrue($this->rule->isValid("user_a"));

        self::assertFalse($this->rule->isValid("user,a"));
        self::assertFalse($this->rule->isValid("user:a"));
        self::assertFalse($this->rule->isValid("user'a"));
        self::assertFalse($this->rule->isValid("user`a"));
        self::assertFalse($this->rule->isValid('user"a'));
        self::assertFalse($this->rule->isValid("user<a"));
        self::assertFalse($this->rule->isValid("user>a"));
        self::assertFalse($this->rule->isValid("user[a"));
        self::assertFalse($this->rule->isValid("user]a"));
        self::assertFalse($this->rule->isValid("user{a"));
        self::assertFalse($this->rule->isValid("user}a"));
        self::assertFalse($this->rule->isValid("user(a"));
        self::assertFalse($this->rule->isValid("user)a"));
        self::assertFalse($this->rule->isValid("user|a"));

        // Maths
        self::assertFalse($this->rule->isValid("user+a"));
        self::assertFalse($this->rule->isValid("user=a"));
        self::assertFalse($this->rule->isValid("user/a"));

        // Misc
        self::assertFalse($this->rule->isValid("user~a"));
        self::assertFalse($this->rule->isValid("user@a"));
        self::assertFalse($this->rule->isValid("user!a"));
        self::assertFalse($this->rule->isValid('user#a'));
        self::assertFalse($this->rule->isValid('user$a'));
        self::assertFalse($this->rule->isValid("user%a"));
        self::assertFalse($this->rule->isValid("user^a"));
        self::assertFalse($this->rule->isValid("user&a"));
        self::assertFalse($this->rule->isValid("user*a"));

        // Accent & language
        self::assertFalse($this->rule->isValid("useré"));
        self::assertFalse($this->rule->isValid("userç"));
    }

    public function testItForbidsSpaces(): void
    {
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);

        self::assertFalse($this->rule->isValid("user test"));
        self::assertFalse($this->rule->isValid(" usertest"));
        self::assertFalse($this->rule->isValid("usertest "));
        self::assertFalse($this->rule->isValid("user test 1"));
        self::assertTrue($this->rule->isValid("user"));
    }

    public function testItForbidsBeginnigByAChar(): void
    {
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);

        self::assertFalse($this->rule->isValid("1"));
        self::assertFalse($this->rule->isValid("1deux"));
        self::assertTrue($this->rule->isValid("a1b"));
    }
}

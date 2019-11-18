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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RuleNameTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $project;
    private $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao = \Mockery::spy(\Tuleap\SVN\Dao::class);

        $this->project = \Mockery::spy(\Project::class);

        $this->rule = new RuleName($this->project, $this->dao);
    }

    public function testItVerifyRepositoryNameNotAlreadyUsedInProject(): void
    {
        $this->dao->shouldReceive('doesRepositoryAlreadyExist')->withArgs(["repository1", $this->project])->andReturnTrue();

        $this->assertFalse($this->rule->isValid("repository1"));
        $this->assertTrue($this->rule->isValid("repository2"));
    }

    public function testItForbidsSpecialsChars(): void
    {
        // Special chars
        $this->assertFalse($this->rule->isValid("user\n"));
        $this->assertFalse($this->rule->isValid("\nuser"));
        $this->assertFalse($this->rule->isValid("user\nuser"));
        $this->assertFalse($this->rule->isValid("user\0"));
        $this->assertFalse($this->rule->isValid("\0user"));
        $this->assertFalse($this->rule->isValid("user\0user"));

        // Punctuation
        $this->assertFalse($this->rule->isValid("user a"));
        $this->assertFalse($this->rule->isValid("user;a"));

        // Specials char allowed
        $this->assertTrue($this->rule->isValid("user.a"));
        $this->assertTrue($this->rule->isValid("user-a"));
        $this->assertTrue($this->rule->isValid("user_a"));

        $this->assertFalse($this->rule->isValid("user,a"));
        $this->assertFalse($this->rule->isValid("user:a"));
        $this->assertFalse($this->rule->isValid("user'a"));
        $this->assertFalse($this->rule->isValid("user`a"));
        $this->assertFalse($this->rule->isValid('user"a'));
        $this->assertFalse($this->rule->isValid("user<a"));
        $this->assertFalse($this->rule->isValid("user>a"));
        $this->assertFalse($this->rule->isValid("user[a"));
        $this->assertFalse($this->rule->isValid("user]a"));
        $this->assertFalse($this->rule->isValid("user{a"));
        $this->assertFalse($this->rule->isValid("user}a"));
        $this->assertFalse($this->rule->isValid("user(a"));
        $this->assertFalse($this->rule->isValid("user)a"));
        $this->assertFalse($this->rule->isValid("user|a"));

        // Maths
        $this->assertFalse($this->rule->isValid("user+a"));
        $this->assertFalse($this->rule->isValid("user=a"));
        $this->assertFalse($this->rule->isValid("user/a"));

        // Misc
        $this->assertFalse($this->rule->isValid("user~a"));
        $this->assertFalse($this->rule->isValid("user@a"));
        $this->assertFalse($this->rule->isValid("user!a"));
        $this->assertFalse($this->rule->isValid('user#a'));
        $this->assertFalse($this->rule->isValid('user$a'));
        $this->assertFalse($this->rule->isValid("user%a"));
        $this->assertFalse($this->rule->isValid("user^a"));
        $this->assertFalse($this->rule->isValid("user&a"));
        $this->assertFalse($this->rule->isValid("user*a"));

        // Accent & language
        $this->assertFalse($this->rule->isValid("useré"));
        $this->assertFalse($this->rule->isValid("userç"));
    }

    public function testItForbidsSpaces(): void
    {
        $this->assertFalse($this->rule->isValid("user test"));
        $this->assertFalse($this->rule->isValid(" usertest"));
        $this->assertFalse($this->rule->isValid("usertest "));
        $this->assertFalse($this->rule->isValid("user test 1"));
        $this->assertTrue($this->rule->isValid("user"));
    }

    public function testItForbidsBeginnigByAChar(): void
    {
        $this->assertFalse($this->rule->isValid("1"));
        $this->assertFalse($this->rule->isValid("1deux"));
        $this->assertTrue($this->rule->isValid("a1b"));
    }
}

<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

class PathJoinUtilTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEmptyArrayReturnsEmptyPath(): void
    {
        $this->assertEquals('', PathJoinUtil::unixPathJoin([]));
    }

    public function testTheSlashInFrontOfTheFirstElementIsKept(): void
    {
        $this->assertEquals('/toto', PathJoinUtil::unixPathJoin(['/toto']));
        $this->assertEquals('/toto/tata/', PathJoinUtil::unixPathJoin(['/toto', 'tata/']));
    }

    public function testAtTheEndThereIsASlashOnlyIfTheLastElementHasOne(): void
    {
        $this->assertEquals('toto/', PathJoinUtil::unixPathJoin(['toto/']));
        $this->assertEquals('toto/tata/', PathJoinUtil::unixPathJoin(['toto', 'tata/']));
    }

    public function testRemoveSlashesWhenThereAreMoreThanOne(): void
    {
        $this->assertEquals('/toto', PathJoinUtil::unixPathJoin(['//toto']));
        $this->assertEquals('toto/tata', PathJoinUtil::unixPathJoin(['toto/', '/tata']));
        $this->assertEquals('/toto/tata/titi/tutu', PathJoinUtil::unixPathJoin(['/toto/', '/tata/', '/titi/', '//tutu']));
    }

    public function testAllEmptyElementsAreIgnored(): void
    {
        $this->assertEquals('toto/0', PathJoinUtil::unixPathJoin(['', null, 'toto', '0']));
    }

    public function testUserRepoPathIsPrefixedByUsername(): void
    {
        $this->assertEquals('u/nicolas', PathJoinUtil::userRepoPath('nicolas', ''));
        $this->assertEquals('u/nicolas/toto', PathJoinUtil::userRepoPath('nicolas', 'toto'));
    }

    public function testUserRepoPathComplainsWhenThereAreDoubleDots(): void
    {
        $this->expectException('MalformedPathException');
        PathJoinUtil::userRepoPath('nicolas', '..');
    }

    public function testUserRepoPathComplainsWhenUserTriesToByPathItsHomeDirectory(): void
    {
        $this->expectException('MalformedPathException');
        $this->assertEquals('u/nicolas/root', PathJoinUtil::userRepoPath('nicolas', '/users/../root'));
    }
}

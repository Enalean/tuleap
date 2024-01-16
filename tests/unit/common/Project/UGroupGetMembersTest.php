<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project;

use Tuleap\GlobalLanguageMock;
use UserManager;

final class UGroupGetMembersTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private array $garfield_incomplete_row = ['user_id' => 1234, 'user_name' => 'garfield'];
    private array $goofy_incomplete_row    = ['user_id' => 5677, 'user_name' => 'goofy'];

    protected function setUp(): void
    {
        parent::setUp();
        $user_manager = $this->createMock(\UserManager::class);
        UserManager::setInstance($user_manager);

        $garfield = new \PFUser($this->garfield_incomplete_row);
        $goofy    = new \PFUser($this->goofy_incomplete_row);
        $user_manager->method('getUserById')
            ->withConsecutive(
                [$this->garfield_incomplete_row['user_id']],
                [$this->goofy_incomplete_row['user_id']]
            )
            ->willReturnOnConsecutiveCalls($garfield, $goofy);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function testItIsEmptyWhenTheGroupIsEmpty(): void
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id];
        $ugroup = new \ProjectUGroup($row);
        $dao    = $this->createMock(\UGroupUserDao::class);
        $dao->method('searchUserByStaticUGroupId')->with($id)->willReturn(\TestHelper::emptyDar());
        $ugroup->setUGroupUserDao($dao);
        self::assertCount(0, $ugroup->getMembers());
        self::assertCount(0, $ugroup->getMembersUserName());
    }

    public function testItReturnsTheMembersOfStaticGroups(): void
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id];
        $ugroup = new \ProjectUGroup($row);
        $dao    = $this->createMock(\UGroupUserDao::class);
        $dao->method('searchUserByStaticUGroupId')->with($id)->willReturn(\TestHelper::arrayToDar($this->garfield_incomplete_row, $this->goofy_incomplete_row));
        $ugroup->setUGroupUserDao($dao);
        self::assertNotEmpty($ugroup->getMembers());
        self::assertCount(2, $ugroup->getMembers());

        self::assertNotEmpty($ugroup->getMembersUserName());
        self::assertEquals(['garfield', 'goofy'], $ugroup->getMembersUserName());
    }

    public function testItReturnsTheMembersOfDynamicGroups(): void
    {
        $id       = 1;
        $group_id = 555;
        $row      = ['ugroup_id' => $id, 'group_id' => $group_id];
        $ugroup   = new \ProjectUGroup($row);
        $dao      = $this->createMock(\UGroupUserDao::class);
        $dao->method('searchUserByDynamicUGroupId')->with($id, $group_id)
            ->willReturn(\TestHelper::arrayToDar($this->garfield_incomplete_row, $this->goofy_incomplete_row));
        $ugroup->setUGroupUserDao($dao);
        self::assertNotEmpty($ugroup->getMembers());
        self::assertCount(2, $ugroup->getMembers());

        self::assertNotEmpty($ugroup->getMembersUserName());
        self::assertEquals(['garfield', 'goofy'], $ugroup->getMembersUserName());
    }
}

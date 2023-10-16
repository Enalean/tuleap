<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Test\PHPUnit\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class WikiTest extends TestCase
{
    /**
     * @var Int
     */
    private $user_id;
    /**
     * @var Wiki
     */
    private $wiki;
    /**
     * @var Int
     */
    private $group_id;
    private PFUser&\PHPUnit\Framework\MockObject\MockObject $user;

    protected function setUp(): void
    {
        parent::setUp();

        $user_manager = $this->createMock(\UserManager::class);
        UserManager::setInstance($user_manager);

        $this->group_id = 100;
        $this->wiki     = new Wiki($this->group_id);

        $this->user_id = 101;
        $this->user    = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn($this->user_id);

        $user_manager->method('getUserById')->with($this->user_id)->willReturn($this->user);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsTrueWhenUserIsProjectAdmin(): void
    {
        $this->user->method('isMember')->with($this->group_id, ProjectUGroup::PROJECT_ADMIN_PERMISSIONS)->willReturn(true);

        self::assertTrue($this->wiki->isAutorized($this->user_id));
    }

    public function testItReturnsTrueWhenUserIsWikiAdmin(): void
    {
        $this->user->method('isMember')->willReturnMap([
            [$this->group_id, ProjectUGroup::PROJECT_ADMIN_PERMISSIONS, false],
            [$this->group_id, ProjectUGroup::WIKI_ADMIN_PERMISSIONS, true],
        ]);

        self::assertTrue($this->wiki->isAutorized($this->user_id));
    }
}

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

class WikiTest extends \PHPUnit\Framework\TestCase  // @codingStandardsIgnoreLine
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
    /**
     * @var PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($user_manager);

        $this->group_id = 100;
        $this->wiki     = new Wiki($this->group_id);

        $this->user_id = 101;
        $this->user    = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns($this->user_id);

        $user_manager->shouldReceive('getUserById')->with($this->user_id)->andReturns($this->user);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsTrueWhenUserIsProjectAdmin(): void
    {
        $this->user->shouldReceive('isMember')->with($this->group_id, ProjectUGroup::PROJECT_ADMIN_PERMISSIONS)->andReturns(true);

        $this->assertTrue($this->wiki->isAutorized($this->user_id));
    }

    public function testItReturnsTrueWhenUserIsWikiAdmin(): void
    {
        $this->user->shouldReceive('isMember')->with($this->group_id, ProjectUGroup::WIKI_ADMIN_PERMISSIONS)->andReturns(true);

        $this->assertTrue($this->wiki->isAutorized($this->user_id));
    }
}

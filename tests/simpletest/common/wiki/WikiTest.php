<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class WikiTest extends TuleapTestCase  // @codingStandardsIgnoreLine
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
    /**
     * @var PFUser
     */
    private $user;

    public function setUp()
    {
        parent::setUp();

        $user_manager = mock('UserManager');
        UserManager::setInstance($user_manager);

        $this->group_id = 100;
        $this->wiki     = new Wiki($this->group_id);

        $this->user_id = 101;
        $this->user    = mock('PFUser');
        stub($this->user)->getId()->returns($this->user_id);

        stub($user_manager)->getUserById($this->user_id)->returns($this->user);
    }

    public function tearDown()
    {
        UserManager::clearInstance();

        parent::tearDown();
    }

    public function itReturnsTrueWhenUserIsProjectAdmin()
    {
        stub($this->user)->isMember($this->group_id, ProjectUGroup::PROJECT_ADMIN_PERMISSIONS)->returns(true);

        $this->assertTrue($this->wiki->isAutorized($this->user_id));
    }

    public function itReturnsTrueWhenUserIsWikiAdmin()
    {
        stub($this->user)->isMember($this->group_id, ProjectUGroup::WIKI_ADMIN_PERMISSIONS)->returns(true);

        $this->assertTrue($this->wiki->isAutorized($this->user_id));
    }
}

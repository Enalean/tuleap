<?php
/**
 * Copyright Enalean (c) 2017-Present. All rights reserved.
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

namespace Tuleap\Git\Hook;

require_once __DIR__ . '/../../bootstrap.php';

use GitRepository;
use Mockery;
use PFUser;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;

class PostReceiveMailsRetrieverTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var GitRepository */
    private $repository;

    /** @var PostReceiveMailsRetriever */
    private $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $project = \Mockery::spy(\Project::class, ['getID' => 42, 'getUnixName' => false, 'isPublic' => false]);

        $this->repository = Mockery::mock(GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturn(101);
        $this->repository->shouldReceive('getProject')->andReturn($project);
        $this->repository->shouldReceive('getNotifiedMails')->andReturn(array('jdoe@example.com', 'smith@example.com'));

        $notified_users_dao = Mockery::mock(UsersToNotifyDao::class);
        $notified_users_dao->shouldReceive('searchUsersByRepositoryId')->with(101)->andReturns(\TestHelper::arrayToDar(array('email' => 'andrew@example.com'), array('email' => 'smith@example.com')));

        $notified_ugroup_dao = Mockery::mock(UgroupsToNotifyDao::class);
        $notified_ugroup_dao->shouldReceive('searchUgroupsByRepositoryId')->with(101)->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => 104, 'name' => 'Developers')));

        $developers = Mockery::mock(\ProjectUGroup::class);
        $developers->shouldReceive('getMembers')->andReturn(array(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 201,
                'status' => PFUser::STATUS_ACTIVE,
                'email' => 'jdoe@example.com'
            ]),
            new PFUser([
                'language_id' => 'en',
                'user_id' => 202,
                'status' => PFUser::STATUS_RESTRICTED,
                'email' => 'charles@example.com'
            ]),
            new PFUser([
                'language_id' => 'en',
                'user_id' => 203,
                'status' => PFUser::STATUS_SUSPENDED,
                'email' => 'suspended@example.com'
            ])
        ));

        $ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $ugroup_manager->shouldReceive('getUGroup')->with($project, 104)->andReturns($developers);

        $this->retriever = new PostReceiveMailsRetriever($notified_users_dao, $notified_ugroup_dao, $ugroup_manager);
    }

    public function testItReturnsMailsForRepository(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('smith@example.com', $emails));
    }

    public function testItReturnsMailsOfUsersForRepository(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertTrue(in_array('andrew@example.com', $emails));
    }

    public function testItReturnsMailsOfUgroupMembersForRepository(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertTrue(in_array('charles@example.com', $emails));
    }

    public function testItRemovesGroupMembersThatAreNotAlive(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function testItRemovesDuplicates(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertEquals($emails, array_unique($emails));
    }
}

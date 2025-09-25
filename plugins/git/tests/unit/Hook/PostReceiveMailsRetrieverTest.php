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

declare(strict_types=1);

namespace Tuleap\Git\Hook;

use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostReceiveMailsRetrieverTest extends TestCase
{
    private GitRepository&MockObject $repository;
    private PostReceiveMailsRetriever $retriever;

    #[\Override]
    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(42)->withAccessPrivate()->build();

        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getId')->willReturn(101);
        $this->repository->method('getProject')->willReturn($project);
        $this->repository->method('getNotifiedMails')->willReturn(['jdoe@example.com', 'smith@example.com']);

        $notified_users_dao = $this->createMock(UsersToNotifyDao::class);
        $notified_users_dao->method('searchUsersByRepositoryId')->with(101)->willReturn(TestHelper::arrayToDar(['email' => 'andrew@example.com'], ['email' => 'smith@example.com']));

        $notified_ugroup_dao = $this->createMock(UgroupsToNotifyDao::class);
        $notified_ugroup_dao->method('searchUgroupsByRepositoryId')->with(101)->willReturn(TestHelper::arrayToDar(['ugroup_id' => 104, 'name' => 'Developers']));

        $developers = ProjectUGroupTestBuilder::aCustomUserGroup(104)
            ->withName('Developers')
            ->withProject($project)
            ->withUsers(
                new PFUser([
                    'language_id' => 'en',
                    'user_id'     => 201,
                    'status'      => PFUser::STATUS_ACTIVE,
                    'email'       => 'jdoe@example.com',
                ]),
                new PFUser([
                    'language_id' => 'en',
                    'user_id'     => 202,
                    'status'      => PFUser::STATUS_RESTRICTED,
                    'email'       => 'charles@example.com',
                ]),
                new PFUser([
                    'language_id' => 'en',
                    'user_id'     => 203,
                    'status'      => PFUser::STATUS_SUSPENDED,
                    'email'       => 'suspended@example.com',
                ]),
            )
            ->build();

        $ugroup_manager = $this->createMock(UGroupManager::class);
        $ugroup_manager->method('getUGroup')->with($project, 104)->willReturn($developers);

        $this->retriever = new PostReceiveMailsRetriever($notified_users_dao, $notified_ugroup_dao, $ugroup_manager);
    }

    public function testItReturnsMailsForRepository(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        self::assertTrue(in_array('jdoe@example.com', $emails));
        self::assertTrue(in_array('smith@example.com', $emails));
    }

    public function testItReturnsMailsOfUsersForRepository(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        self::assertTrue(in_array('andrew@example.com', $emails));
    }

    public function testItReturnsMailsOfUgroupMembersForRepository(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        self::assertTrue(in_array('charles@example.com', $emails));
    }

    public function testItRemovesGroupMembersThatAreNotAlive(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        self::assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function testItRemovesDuplicates(): void
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        self::assertEquals($emails, array_unique($emails));
    }
}

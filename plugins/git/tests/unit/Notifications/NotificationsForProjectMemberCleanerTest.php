<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Git\Notifications;

use Git_PostReceiveMailManager;
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotificationsForProjectMemberCleanerTest extends TestCase
{
    private Project $project;
    private PFUser&MockObject $user;
    private Git_PostReceiveMailManager&MockObject $mails_to_notify_manager;
    private UsersToNotifyDao&MockObject $users_to_notify_dao;
    private GitRepository&MockObject $unreadable_repository;
    private NotificationsForProjectMemberCleaner $cleaner;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(101)->withAccessPrivate()->build();
        $this->user    = $this->createMock(PFUser::class);
        $this->user->method('getId')->willReturn(107);
        $this->user->method('getEmail')->willReturn('jdoe@example.com');

        $this->mails_to_notify_manager = $this->createMock(Git_PostReceiveMailManager::class);
        $factory                       = $this->createMock(GitRepositoryFactory::class);

        $this->unreadable_repository = $this->createMock(GitRepository::class);
        $this->unreadable_repository->method('getId')->willReturn(1);
        $this->unreadable_repository->method('userCanRead')->with($this->user)->willReturn(false);
        $readable_repository = $this->createMock(GitRepository::class);
        $readable_repository->method('getId')->willReturn(2);
        $readable_repository->method('userCanRead')->with($this->user)->willReturn(true);

        $factory->method('getAllRepositories')->with($this->project)->willReturn([$this->unreadable_repository, $readable_repository]);

        $this->users_to_notify_dao = $this->createMock(UsersToNotifyDao::class);

        $this->cleaner = new NotificationsForProjectMemberCleaner(
            $factory,
            $this->mails_to_notify_manager,
            $this->users_to_notify_dao
        );
    }

    public function testItDoesNotRemoveAnythingIfUserIsStillMemberOfTheProject(): void
    {
        $this->user->method('isMember')->with($this->project->getID())->willReturn(true);

        $this->mails_to_notify_manager->expects(self::never())->method('removeMailByRepository');
        $this->users_to_notify_dao->expects(self::never())->method('delete');

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }

    public function testItRemovesNotificationForRepositoriesTheUserCannotAccess(): void
    {
        $this->user->method('isMember')->with($this->project->getID())->willReturn(false);

        $this->mails_to_notify_manager->expects(self::once())->method('removeMailByRepository')->with($this->unreadable_repository, 'jdoe@example.com');

        $this->users_to_notify_dao->expects(self::once())->method('delete')->with($this->unreadable_repository->getId(), $this->user->getId());

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }
}

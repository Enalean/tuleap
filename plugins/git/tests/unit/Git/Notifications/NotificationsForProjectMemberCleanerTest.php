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

namespace Tuleap\Git\Notifications;

class NotificationsForProjectMemberCleanerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $project;
    private $user;
    private $mails_to_notify_manager;
    private $users_to_notify_dao;
    private $unreadable_repository;
    private $factory;
    private $readable_repository;

    /** @var NotificationsForProjectMemberCleaner */
    private $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->user    = \Mockery::spy(\PFUser::class);

        $this->user->shouldReceive('getId')->andReturns(107);
        $this->user->shouldReceive('getEmail')->andReturns('jdoe@example.com');

        $this->mails_to_notify_manager = \Mockery::spy(\Git_PostReceiveMailManager::class);
        $this->factory                 = \Mockery::spy(\GitRepositoryFactory::class);

        $this->unreadable_repository = \Mockery::spy(\GitRepository::class)->shouldReceive('getId')->andReturns(1)->getMock();
        $this->readable_repository   = \Mockery::spy(\GitRepository::class)->shouldReceive('getId')->andReturns(2)->getMock();

        $this->unreadable_repository->shouldReceive('userCanRead')->with($this->user)->andReturns(false);
        $this->readable_repository->shouldReceive('userCanRead')->with($this->user)->andReturns(true);

        $this->factory->shouldReceive('getAllRepositories')->with($this->project)->andReturns(array($this->unreadable_repository, $this->readable_repository));

        $this->users_to_notify_dao = \Mockery::mock(UsersToNotifyDao::class);

        $this->cleaner = new NotificationsForProjectMemberCleaner(
            $this->factory,
            $this->mails_to_notify_manager,
            $this->users_to_notify_dao
        );
    }

    public function testItDoesNotRemoveAnythingIfUserIsStillMemberOfTheProject(): void
    {
        $this->user->shouldReceive('isMember')->with($this->project->getID())->andReturns(true);

        $this->mails_to_notify_manager->shouldReceive('removeMailByRepository')->never();
        $this->users_to_notify_dao->shouldReceive('delete')->never();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }

    public function testItRemovesNotificationForRepositoriesTheUserCannotAccess(): void
    {
        $this->user->shouldReceive('isMember')->with($this->project)->andReturns(false);

        $this->mails_to_notify_manager->shouldReceive('removeMailByRepository')->with($this->unreadable_repository, 'jdoe@example.com')
            ->once();

        $this->users_to_notify_dao->shouldReceive('delete')->with($this->unreadable_repository->getId(), $this->user->getId())->once();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }
}

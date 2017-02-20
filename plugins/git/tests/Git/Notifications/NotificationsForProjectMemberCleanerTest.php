<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

use TuleapTestCase;

class NotificationsForProjectMemberCleanerTest extends TuleapTestCase
{
    private $project;
    private $user;
    private $mails_to_notify_manager;
    private $users_to_notify_dao;
    private $unreadable_repository;
    private $factory;
    private $readable_repository;

    /** @var NotificationsForProjectMemberCleaner */
    private $cleaner;

    public function setUp()
    {
        parent::setUp();
        $this->project = aMockProject()->withId(101)->build();
        $this->user    = mock('PFUser');

        stub($this->user)->getId()->returns(107);
        stub($this->user)->getEmail()->returns('jdoe@example.com');

        $this->mails_to_notify_manager = mock('Git_PostReceiveMailManager');
        $this->factory                 = mock('GitRepositoryFactory');

        $this->unreadable_repository = stub('GitRepository')->getId()->returns(1);
        $this->readable_repository   = stub('GitRepository')->getId()->returns(2);

        stub($this->unreadable_repository)->userCanRead($this->user)->returns(false);
        stub($this->readable_repository)->userCanRead($this->user)->returns(true);

        stub($this->factory)
            ->getAllRepositories($this->project)
            ->returns(array($this->unreadable_repository, $this->readable_repository));

        $this->users_to_notify_dao = mock('Tuleap\Git\Notifications\UsersToNotifyDao');

        $this->cleaner = new NotificationsForProjectMemberCleaner(
            $this->factory,
            $this->mails_to_notify_manager,
            $this->users_to_notify_dao
        );
    }

    public function itDoesNotRemoveAnythingIfUserIsStillMemberOfTheProject()
    {
        stub($this->user)->isMember($this->project->getID())->returns(true);

        expect($this->mails_to_notify_manager)->removeMailByRepository()->never();
        expect($this->users_to_notify_dao)->delete()->never();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }

    public function itRemovesNotificationForRepositoriesTheUserCannotAccess()
    {
        stub($this->user)->isMember($this->project)->returns(false);

        expect($this->mails_to_notify_manager)
            ->removeMailByRepository()
            ->count(1);
        expect($this->mails_to_notify_manager)
            ->removeMailByRepository($this->unreadable_repository, 'jdoe@example.com')
            ->once();

        expect($this->users_to_notify_dao)
            ->delete()
            ->count(1);
        expect($this->users_to_notify_dao)
            ->delete($this->unreadable_repository->getId(), $this->user->getId())
            ->once();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }
}

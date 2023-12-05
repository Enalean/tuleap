<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Notifications;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVNCore\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class EmailsToBeNotifiedRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PFUser $user_suspended;
    private PFUser $user_jdoe;
    private PFUser $user_charles;
    private PFUser $user_jsmith;
    /**
     * @var UsersToNotifyDao&MockObject
     */
    private $user_dao;
    /**
     * @var Repository&MockObject
     */
    private $repository;
    /**
     * @var MailNotificationManager&MockObject
     */
    private $notification_manager;
    private EmailsToBeNotifiedRetriever $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository           = $this->createMock(\Tuleap\SVNCore\Repository::class);
        $this->notification_manager = $this->createMock(\Tuleap\SVN\Admin\MailNotificationManager::class);
        $this->user_dao             = $this->createMock(\Tuleap\SVN\Notifications\UsersToNotifyDao::class);

        $project = ProjectTestBuilder::aProject()->withId(222)->build();

        $this->repository->method('getProject')->willReturn($project);

        $this->user_jsmith    = UserTestBuilder::aUser()->withId(101)->withEmail('jsmith@example.com')->withStatus('A')->build();
        $this->user_charles   = UserTestBuilder::aUser()->withId(102)->withEmail('charles@example.com')->withStatus('A')->build();
        $this->user_jdoe      = UserTestBuilder::aUser()->withId(103)->withEmail('jdoe@example.com')->withStatus('A')->build();
        $this->user_suspended = UserTestBuilder::aUser()->withId(104)->withEmail('jsmith@example.com')->withStatus('S')->build();

        $this->retriever = new EmailsToBeNotifiedRetriever(
            $this->notification_manager
        );
    }

    public function testItReturnsEmailsAsArray(): void
    {
        $this->notification_manager->method('getByPath')->willReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    ['jdoe@example.com', 'jsmith@example.com'],
                    [],
                    []
                ),
            ]
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = ['jdoe@example.com', 'jsmith@example.com'];

        self::assertEquals($emails, $expected);
    }

    public function testItCombinesEmailsFromMultipleMatchingNotifications(): void
    {
        $this->notification_manager->method('getByPath')->willReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [],
                    []
                ),
                new MailNotification(
                    2,
                    $this->repository,
                    '/path',
                    ['jdoe@example.com'],
                    [],
                    []
                ),
            ]
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = ['jdoe@example.com', 'jsmith@example.com'];

        self::assertEquals($emails, $expected);
    }

    public function testItReturnsEmailsOfUsersForNotification(): void
    {
        $this->notification_manager->method('getByPath')->willReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    [],
                    [$this->user_jsmith],
                    []
                ),
            ]
        );
        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = ['jsmith@example.com'];

        self::assertEquals($emails, $expected);
    }

    public function testItReturnsEmailsOfUgroupMembersForNotification(): void
    {
        $user_group = $this->createMock(ProjectUGroup::class);
        $user_group->method('getMembers')->willReturn([$this->user_charles, $this->user_jdoe]);
        $this->notification_manager->method('getByPath')->willReturn(
            [
                new MailNotification(
                    101,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [],
                    [$user_group]
                ),
            ]
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        self::assertTrue(in_array('jdoe@example.com', $emails));
        self::assertTrue(in_array('charles@example.com', $emails));
    }

    public function testItRemovesGroupMembersThatAreNotAlive(): void
    {
        $user_group = $this->createMock(ProjectUGroup::class);
        $user_group->method('getMembers')->willReturn([$this->user_suspended]);
        $this->notification_manager->method('getByPath')->willReturn(
            [
                new MailNotification(
                    101,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [],
                    [$user_group]
                ),
            ]
        );
        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        self::assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function testItRemovesDuplicates(): void
    {
        $user_group = $this->createMock(ProjectUGroup::class);
        $user_group->method('getMembers')->willReturn([$this->user_jsmith]);
        $this->notification_manager->method('getByPath')->willReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [$this->user_jsmith],
                    [$user_group]
                ),
            ]
        );
        $this->user_dao->method('searchUsersByNotificationId')->willReturn(['email' => 'jsmith@example.com']);

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        self::assertEquals($emails, array_unique($emails));
    }
}

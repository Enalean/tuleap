<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Repository\Repository;
use TuleapTestCase;

require_once __DIR__ . '/../../bootstrap.php';

class EmailsToBeNotifiedRetrieverTest extends TuleapTestCase
{
    /**
     * @var PFUser
     */
    private $user_suspended;
    /**
     * @var PFUser
     */
    private $user_jdoe;
    /**
     * @var PFUser
     */
    private $user_charles;
    /**
     * @var PFUser
     */
    private $user_jsmith;
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var MailNotificationManager
     */
    private $notification_manager;

    /**
     * @var EmailsToBeNotifiedRetriever
     */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->repository           = mock('Tuleap\SVN\Repository\Repository');
        $this->notification_manager = mock('Tuleap\SVN\Admin\MailNotificationManager');
        $this->user_dao             = mock('Tuleap\SVN\Notifications\UsersToNotifyDao');

        $project = aMockProject()->withId(222)->build();
        stub($this->repository)->getProject()->returns($project);

        $notified_ugroups_dao = mock('Tuleap\SVN\Notifications\UgroupsToNotifyDao');
        $ugroup_manager       = mock('UGroupManager');

        $this->user_jsmith = aUser()->withEmail('jsmith@example.com')->build();

        $this->user_charles = mock('PFUser');
        stub($this->user_charles)->getEmail()->returns('charles@example.com');
        stub($this->user_charles)->isAlive()->returns(true);

        $this->user_jdoe = mock('PFUser');
        stub($this->user_jdoe)->getEmail()->returns('jdoe@example.com');
        stub($this->user_jdoe)->isAlive()->returns(true);

        $this->user_suspended = mock('PFUser');
        stub($this->user_suspended)->getEmail()->returns('suspended@example.com');
        stub($this->user_suspended)->isAlive()->returns(false);

        $this->retriever = new EmailsToBeNotifiedRetriever(
            $this->notification_manager,
            $this->user_dao,
            $notified_ugroups_dao,
            $ugroup_manager,
            mock('UserManager')
        );
    }

    public function itReturnsEmailsAsArray()
    {
        stub($this->notification_manager)->getByPath()->returns(
            array(
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    array('jdoe@example.com', 'jsmith@example.com'),
                    array(),
                    array()
                )
            )
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jdoe@example.com', 'jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }

    public function itCombinesEmailsFromMultipleMatchingNotifications()
    {
        stub($this->notification_manager)->getByPath()->returns(
            array(
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    array('jsmith@example.com'),
                    array(),
                    array()
                ),
                new MailNotification(
                    2,
                    $this->repository,
                    '/path',
                    array('jdoe@example.com'),
                    array(),
                    array()
                )
            )
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jdoe@example.com', 'jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }

    public function itReturnsEmailsOfUsersForNotification()
    {
        stub($this->notification_manager)->getByPath()->returns(
            array(
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    array(),
                    array($this->user_jsmith),
                    array()
                )
            )
        );
        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }

    public function itReturnsEmailsOfUgroupMembersForNotification()
    {
        $user_group = aMockUGroup()->withMembers(array($this->user_charles, $this->user_jdoe))->build();
        stub($this->notification_manager)->getByPath()->returns(
            array(
                new MailNotification(
                    101,
                    $this->repository,
                    '/path',
                    array('jsmith@example.com'),
                    array(),
                    array($user_group)
                ),
            )
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('charles@example.com', $emails));
    }

    public function itRemovesGroupMembersThatAreNotAlive()
    {
        $user_group = aMockUGroup()->withMembers(array($this->user_suspended))->build();
        stub($this->notification_manager)->getByPath()->returns(
            array(
                new MailNotification(
                    101,
                    $this->repository,
                    '/path',
                    array('jsmith@example.com'),
                    array(),
                    array($user_group)
                ),
            )
        );
        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $this->assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function itRemovesDuplicates()
    {
        $user_group = aMockUGroup()->withMembers(array($this->user_jsmith))->build();
        stub($this->notification_manager)->getByPath()->returns(
            array(
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    array('jsmith@example.com'),
                    array($this->user_jsmith),
                    array($user_group)
                ),
            )
        );
        stub($this->user_dao)->searchUsersByNotificationId()->returnsDar(array('email' => 'jsmith@example.com'));

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $this->assertEqual($emails, array_unique($emails));
    }
}

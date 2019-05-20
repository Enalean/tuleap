<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Mail;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Project_AccessPrivateException;
use Tuleap\Project\ProjectAccessChecker;
use TuleapTestCase;
use UserManager;

class MailFilterTest extends TuleapTestCase
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;

    /**
     * @var MailFilter
     */
    private $mail_filter;

    /**
     * @var \Project
     */
    private $project;

    /**
     * @var \Tuleap\Mail\MailLogger
     */
    private $mail_logger;

    private $user_registered;
    private $user_suspended;
    private $unknown_user;
    private $user_active;
    private $user_registered_bis;


    public function setUp()
    {
        $this->user_registered     = mock('PFUser');
        $this->user_registered_bis = mock('PFUser');
        $this->user_suspended      = mock('PFUser');
        $this->user_active         = mock('PFUser');

        stub($this->user_registered_bis)->isAlive()->returns(true);
        stub($this->user_registered)->isAlive()->returns(true);
        stub($this->user_active)->isAlive()->returns(true);
        stub($this->user_suspended)->isAlive()->returns(false);

        stub($this->user_registered_bis)->getEmail()->returns('user-registered@example.com');
        stub($this->user_registered)->getEmail()->returns('user-registered@example.com');
        stub($this->user_active)->getEmail()->returns('user-active@example.com');
        stub($this->user_suspended)->getEmail()->returns('user-suspended@example.com');

        $this->unknown_user = array();

        $this->user_manager           = mock('UserManager');
        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $this->mail_logger            = mock('Tuleap\Mail\MailLogger');

        $this->mail_filter = new MailFilter($this->user_manager, $this->project_access_checker, $this->mail_logger);

        $this->project = mock('Project');

        ForgeConfig::store();
        ForgeConfig::set('sys_mail_secure_mode', true);
    }

    public function tearDown()
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    private function initializeMails()
    {
        stub($this->user_manager)->getAllUsersByEmail('user-registered@example.com')->returns(
            array($this->user_registered)
        );
        stub($this->user_manager)->getAllUsersByEmail('user-suspended@example.com')->returns(
            array($this->user_suspended)
        );
        stub($this->user_manager)->getAllUsersByEmail('user-active@example.com')->returns(
            array($this->user_active)
        );
        stub($this->user_manager)->getAllUsersByEmail('unknown-user@example.com')->returns(
            array($this->unknown_user)
        );
    }

    public function itFilterPeopleWhoCanNotReadProject()
    {
        $this->initializeMails();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_active, $this->project)
            ->andThrow(Project_AccessPrivateException::class);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_active->getEmail()));

        $expected_mails = array();

        expect($this->mail_logger)->warn()->once();
        expect($this->mail_logger)->info()->never();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itFilterPeopleWhoCanReadProjectAndAreSuspendedOrDeleted()
    {
        $this->initializeMails();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_suspended, $this->project);

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_suspended->getEmail()));
        $expected_mails = array();

        expect($this->mail_logger)->warn()->once();
        expect($this->mail_logger)->info()->never();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itDoesNotFilterPeopleWhoCanReadProjectAndAreActive()
    {
        $this->initializeMails();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array($this->user_registered->getEmail());

        expect($this->mail_logger)->warn()->never();
        expect($this->mail_logger)->info()->once();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itKeepsOneMailWhenSeveralAccountsAreLinkedToTheSameMail()
    {
        stub($this->user_manager)->getAllUsersByEmail($this->user_registered->getEmail())->returns(
            array($this->user_registered, $this->user_registered_bis)
        );
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered_bis, $this->project);

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array($this->user_registered->getEmail());

        expect($this->mail_logger)->warn()->never();
        expect($this->mail_logger)->info()->once();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itKeepsOneMailWhenSeveralAccountsAreLinkedToTheSameMailEvenOneAccountCanNotAccessToProject()
    {
        stub($this->user_manager)->getAllUsersByEmail($this->user_registered->getEmail())->returns(
            array($this->user_registered, $this->user_registered_bis)
        );
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered_bis, $this->project)
            ->andThrow(Project_AccessPrivateException::class);

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array($this->user_registered->getEmail());

        expect($this->mail_logger)->warn()->never();
        expect($this->mail_logger)->info()->once();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itFilterPeopleWhoAreNotMemberOfProject()
    {
        stub($this->user_manager)->getAllUsersByEmail($this->user_registered->getEmail())->returns(array());

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array();

        expect($this->mail_logger)->warn()->once();
        expect($this->mail_logger)->info()->never();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itKeepsAllMailIfUserCanReadProject()
    {
        $this->initializeMails();

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_active, $this->project);

        $filtered_mails = $this->mail_filter->filter(
            $this->project,
            array(
                $this->user_registered->getEmail(),
                $this->user_active->getEmail()
            )
        );
        $expected_mails = array($this->user_registered->getEmail(), $this->user_active->getEmail());

        expect($this->mail_logger)->warn()->never();
        expect($this->mail_logger)->info()->count(2);
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itDoesNotFilterMailsWhenConfigurationAllowsIt()
    {
        ForgeConfig::set('sys_mail_secure_mode', false);
        $this->initializeMails();

        $filtered_mails = $this->mail_filter->filter(
            $this->project,
            array(
                $this->user_registered->getEmail(),
                $this->user_active->getEmail()
            )
        );
        $expected_mails = array($this->user_registered->getEmail(), $this->user_active->getEmail());

        expect($this->mail_logger)->warn()->never();
        expect($this->mail_logger)->info()->once();
        $this->assertEqual($filtered_mails, $expected_mails);
    }

    public function itFiltersDuplicateMails()
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            array(
                'user1@example.com',
                'user1@example.com'
            )
        );

        $this->assertEqual(array_values($filtered_emails), array('user1@example.com'));
    }

    public function itManageWhenEmailsAreNull()
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            array(
                null,
                'user-active@example.com',
                'user1@example.com'
            )
        );

        $this->assertEqual(array_values($filtered_emails), array('user-active@example.com', 'user1@example.com'));
    }

    public function itManageWhenEmailsAreFalse()
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            array(
                false,
                'user-active@example.com',
                'user1@example.com'
            )
        );

        $this->assertEqual(array_values($filtered_emails), array('user-active@example.com', 'user1@example.com'));
    }
}

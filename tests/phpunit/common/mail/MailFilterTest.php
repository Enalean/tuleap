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

declare(strict_types=1);

namespace Tuleap\Mail;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Project_AccessPrivateException;
use Psr\Log\LoggerInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\ProjectAccessChecker;
use UserManager;

final class MailFilterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

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
     * @var LoggerInterface
     */
    private $mail_logger;

    private $user_registered;
    private $user_suspended;
    private $unknown_user;
    private $user_active;
    private $user_registered_bis;


    protected function setUp(): void
    {
        parent::setUp();
        $this->user_registered     = \Mockery::spy(\PFUser::class);
        $this->user_registered_bis = \Mockery::spy(\PFUser::class);
        $this->user_suspended      = \Mockery::spy(\PFUser::class);
        $this->user_active         = \Mockery::spy(\PFUser::class);

        $this->user_registered_bis->shouldReceive('isAlive')->andReturns(true);
        $this->user_registered->shouldReceive('isAlive')->andReturns(true);
        $this->user_active->shouldReceive('isAlive')->andReturns(true);
        $this->user_suspended->shouldReceive('isAlive')->andReturns(false);

        $this->user_registered_bis->shouldReceive('getEmail')->andReturns('user-registered@example.com');
        $this->user_registered->shouldReceive('getEmail')->andReturns('user-registered@example.com');
        $this->user_active->shouldReceive('getEmail')->andReturns('user-active@example.com');
        $this->user_suspended->shouldReceive('getEmail')->andReturns('user-suspended@example.com');

        $this->unknown_user = array();

        $this->user_manager           = \Mockery::spy(\UserManager::class);
        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $this->mail_logger            = \Mockery::spy(LoggerInterface::class);

        $this->mail_filter = new MailFilter($this->user_manager, $this->project_access_checker, $this->mail_logger);

        $this->project = \Mockery::spy(\Project::class);

        ForgeConfig::set('sys_mail_secure_mode', true);
    }

    private function initializeMails(): void
    {
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('user-registered@example.com')->andReturns(array($this->user_registered));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('user-suspended@example.com')->andReturns(array($this->user_suspended));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('user-active@example.com')->andReturns(array($this->user_active));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('unknown-user@example.com')->andReturns(array($this->unknown_user));
    }

    public function testItFilterPeopleWhoCanNotReadProject(): void
    {
        $this->initializeMails();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_active, $this->project)
            ->andThrow(Project_AccessPrivateException::class);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->mail_logger->shouldReceive('warning')->once();
        $this->mail_logger->shouldReceive('info')->never();

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_active->getEmail()));

        $expected_mails = array();

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItFilterPeopleWhoCanReadProjectAndAreSuspendedOrDeleted(): void
    {
        $this->initializeMails();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_suspended, $this->project);

        $this->mail_logger->shouldReceive('warning')->once();
        $this->mail_logger->shouldReceive('info')->never();

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_suspended->getEmail()));
        $expected_mails = array();

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItDoesNotFilterPeopleWhoCanReadProjectAndAreActive(): void
    {
        $this->initializeMails();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);

        $this->mail_logger->shouldReceive('warn')->never();
        $this->mail_logger->shouldReceive('info')->once();

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array($this->user_registered->getEmail());

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItKeepsOneMailWhenSeveralAccountsAreLinkedToTheSameMail(): void
    {
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with($this->user_registered->getEmail())->andReturns(array($this->user_registered, $this->user_registered_bis));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered_bis, $this->project);

        $this->mail_logger->shouldReceive('warn')->never();
        $this->mail_logger->shouldReceive('info')->once();

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array($this->user_registered->getEmail());

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItKeepsOneMailWhenSeveralAccountsAreLinkedToTheSameMailEvenOneAccountCanNotAccessToProject(): void
    {
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with($this->user_registered->getEmail())->andReturns(array($this->user_registered, $this->user_registered_bis));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered_bis, $this->project)
            ->andThrow(Project_AccessPrivateException::class);

        $this->mail_logger->shouldReceive('warn')->never();
        $this->mail_logger->shouldReceive('info')->once();

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array($this->user_registered->getEmail());

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItFilterPeopleWhoAreNotMemberOfProject(): void
    {
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with($this->user_registered->getEmail())->andReturns(array());

        $this->mail_logger->shouldReceive('warning')->once();
        $this->mail_logger->shouldReceive('info')->never();

        $filtered_mails = $this->mail_filter->filter($this->project, array($this->user_registered->getEmail()));
        $expected_mails = array();

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItKeepsAllMailIfUserCanReadProject(): void
    {
        $this->initializeMails();

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with($this->user_active, $this->project);

        $this->mail_logger->shouldReceive('warn')->never();
        $this->mail_logger->shouldReceive('info')->times(2);

        $filtered_mails = $this->mail_filter->filter(
            $this->project,
            array(
                $this->user_registered->getEmail(),
                $this->user_active->getEmail()
            )
        );
        $expected_mails = array($this->user_registered->getEmail(), $this->user_active->getEmail());

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItDoesNotFilterMailsWhenConfigurationAllowsIt(): void
    {
        ForgeConfig::set('sys_mail_secure_mode', false);
        $this->initializeMails();

        $this->mail_logger->shouldReceive('warn')->never();
        $this->mail_logger->shouldReceive('info')->once();

        $filtered_mails = $this->mail_filter->filter(
            $this->project,
            array(
                $this->user_registered->getEmail(),
                $this->user_active->getEmail()
            )
        );
        $expected_mails = array($this->user_registered->getEmail(), $this->user_active->getEmail());

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItFiltersDuplicateMails(): void
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            array(
                'user1@example.com',
                'user1@example.com'
            )
        );

        $this->assertEquals(array('user1@example.com'), array_values($filtered_emails));
    }

    public function testItManageWhenEmailsAreNull(): void
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

        $this->assertEquals(array('user-active@example.com', 'user1@example.com'), array_values($filtered_emails));
    }

    public function testItManageWhenEmailsAreFalse(): void
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

        $this->assertEquals(array('user-active@example.com', 'user1@example.com'), array_values($filtered_emails));
    }
}

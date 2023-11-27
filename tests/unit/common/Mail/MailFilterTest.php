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
use PHPUnit\Framework\MockObject\MockObject;
use Project_AccessPrivateException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class MailFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var UserManager&MockObject
     */
    private $user_manager;

    /**
     * @var ProjectAccessChecker&MockObject
     */
    private $project_access_checker;

    private MailFilter $mail_filter;

    private \Project $project;

    private LoggerInterface $mail_logger;

    private \PFUser $user_registered;
    private \PFUser $user_suspended;
    private \PFUser $user_active;
    private \PFUser $user_registered_bis;


    protected function setUp(): void
    {
        parent::setUp();
        $this->user_registered     = UserTestBuilder::anActiveUser()
            ->withEmail('user-registered@example.com')
            ->build();
        $this->user_registered_bis = UserTestBuilder::anActiveUser()
            ->withEmail('user-registered@example.com')
            ->build();
        $this->user_suspended      = UserTestBuilder::aUser()
            ->withStatus('S')
            ->withEmail('user-suspended@example.com')
            ->build();
        $this->user_active         = UserTestBuilder::anActiveUser()
            ->withEmail('user-active@example.com')
            ->build();

        $this->user_manager           = $this->createMock(\UserManager::class);
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->mail_logger            = new NullLogger();

        $this->mail_filter = new MailFilter($this->user_manager, $this->project_access_checker, $this->mail_logger);

        $this->project = ProjectTestBuilder::aProject()
            ->withAccessPrivate()
            ->build();

        ForgeConfig::set('sys_mail_secure_mode', true);
    }

    public function testItFilterPeopleWhoCanNotReadProject(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->with($this->user_active, $this->project)
            ->willThrowException(new Project_AccessPrivateException());

        $this->user_manager->method('getAllUsersByEmail')
            ->with('user-active@example.com')
            ->willReturn([$this->user_active]);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $filtered_mails = $this->mail_filter->filter($this->project, [$this->user_active->getEmail()]);

        $expected_mails = [];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItFilterPeopleWhoCanReadProjectAndAreSuspendedOrDeleted(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->with($this->user_suspended, $this->project);

        $this->user_manager->method('getAllUsersByEmail')
            ->with('user-suspended@example.com')
            ->willReturn([$this->user_suspended]);

        $filtered_mails = $this->mail_filter->filter($this->project, [$this->user_suspended->getEmail()]);
        $expected_mails = [];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItDoesNotFilterPeopleWhoCanReadProjectAndAreActive(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->with($this->user_registered, $this->project);

        $this->user_manager->method('getAllUsersByEmail')
            ->with('user-registered@example.com')
            ->willReturn([$this->user_registered]);

        $filtered_mails = $this->mail_filter->filter($this->project, [$this->user_registered->getEmail()]);
        $expected_mails = [$this->user_registered->getEmail()];

        $this->assertEquals($expected_mails, $filtered_mails);
    }

    public function testItKeepsOneMailWhenSeveralAccountsAreLinkedToTheSameMail(): void
    {
        $this->user_manager->method('getAllUsersByEmail')
            ->with($this->user_registered->getEmail())
            ->willReturn([$this->user_registered, $this->user_registered_bis]);
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->withConsecutive(
                [$this->user_registered, $this->project],
                [$this->user_registered_bis, $this->project]
            );

        $filtered_mails = $this->mail_filter->filter($this->project, [$this->user_registered->getEmail()]);
        $expected_mails = [$this->user_registered->getEmail()];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItKeepsOneMailWhenSeveralAccountsAreLinkedToTheSameMailEvenOneAccountCanNotAccessToProject(): void
    {
        $this->user_manager->method('getAllUsersByEmail')
            ->with($this->user_registered->getEmail())
            ->willReturn([$this->user_registered, $this->user_registered_bis]);
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->withConsecutive(
                [$this->user_registered, $this->project],
                [$this->user_registered_bis, $this->project]
            );

        $filtered_mails = $this->mail_filter->filter($this->project, [$this->user_registered->getEmail()]);
        $expected_mails = [$this->user_registered->getEmail()];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItFilterPeopleWhoAreNotMemberOfProject(): void
    {
        $this->user_manager->method('getAllUsersByEmail')
            ->with($this->user_registered->getEmail())
            ->willReturn([]);

        $filtered_mails = $this->mail_filter->filter($this->project, [$this->user_registered->getEmail()]);
        $expected_mails = [];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItKeepsAllMailIfUserCanReadProject(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->withConsecutive(
                [$this->user_registered, $this->project],
                [$this->user_active, $this->project]
            );

        $this->user_manager->method('getAllUsersByEmail')
            ->withConsecutive(
                [$this->user_registered->getEmail()],
                [$this->user_active->getEmail()]
            )
            ->willReturnOnConsecutiveCalls(
                [$this->user_registered],
                [$this->user_active]
            );

        $filtered_mails = $this->mail_filter->filter(
            $this->project,
            [
                $this->user_registered->getEmail(),
                $this->user_active->getEmail(),
            ]
        );
        $expected_mails = [$this->user_registered->getEmail(), $this->user_active->getEmail()];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItDoesNotFilterMailsWhenConfigurationAllowsIt(): void
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $this->user_manager->method('getAllUsersByEmail')
            ->withConsecutive(
                [$this->user_registered->getEmail()],
                [$this->user_active->getEmail()]
            )
            ->willReturnOnConsecutiveCalls(
                [$this->user_registered],
                [$this->user_active]
            );

        $filtered_mails = $this->mail_filter->filter(
            $this->project,
            [
                $this->user_registered->getEmail(),
                $this->user_active->getEmail(),
            ]
        );
        $expected_mails = [$this->user_registered->getEmail(), $this->user_active->getEmail()];

        self::assertEquals($expected_mails, $filtered_mails);
    }

    public function testItFiltersDuplicateMails(): void
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            [
                'user1@example.com',
                'user1@example.com',
            ]
        );

        self::assertEquals(['user1@example.com'], array_values($filtered_emails));
    }

    public function testItManageWhenEmailsAreNull(): void
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            [
                null,
                'user-active@example.com',
                'user1@example.com',
            ]
        );

        self::assertEquals(['user-active@example.com', 'user1@example.com'], array_values($filtered_emails));
    }

    public function testItManageWhenEmailsAreFalse(): void
    {
        ForgeConfig::set('sys_mail_secure_mode', false);

        $filtered_emails = $this->mail_filter->filter(
            $this->project,
            [
                false,
                'user-active@example.com',
                'user1@example.com',
            ]
        );

        self::assertEquals(['user-active@example.com', 'user1@example.com'], array_values($filtered_emails));
    }
}

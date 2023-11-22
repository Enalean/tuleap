<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use Tuleap\Notification\Notification;
use PFUser;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\GitService;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class InvalidCredentialsNotifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItDoesNothingIfEmailIsAlreadySent(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $credentials = CredentialsTestBuilder::get()->withEmailAlreadySent()->build();

        $mail_builder = $this->createMock(\MailBuilder::class);
        $mail_builder
            ->expects(self::never())
            ->method('buildAndSendEmail');

        $dao = $this->createMock(IntegrationApiTokenDao::class);
        $dao
            ->expects(self::never())
            ->method('storeTheFactWeAlreadySendEmailForInvalidToken');

        $notifier = new InvalidCredentialsNotifier(
            $mail_builder,
            $dao,
            $this->createMock(LoggerInterface::class),
        );

        $notifier->notifyGitAdministratorsThatCredentialsAreInvalid($integration, $credentials);
    }

    public function testItDoesNothingIfGitIsNotActivatedInProject(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getService')->willReturn(null);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            $project,
            false
        );

        $credentials = CredentialsTestBuilder::get()->withoutEmailAlreadySent()->build();

        $mail_builder = $this->createMock(\MailBuilder::class);
        $mail_builder
            ->expects(self::never())
            ->method('buildAndSendEmail');

        $dao = $this->createMock(IntegrationApiTokenDao::class);
        $dao
            ->expects(self::never())
            ->method('storeTheFactWeAlreadySendEmailForInvalidToken');

        $notifier = new InvalidCredentialsNotifier(
            $mail_builder,
            $dao,
            $this->createMock(LoggerInterface::class),
        );

        $notifier->notifyGitAdministratorsThatCredentialsAreInvalid($integration, $credentials);
    }

    public function testItWarnsProjectAdministratorsForProjectTheRepositoryIsIntegratedIn(): void
    {
        $credentials = CredentialsTestBuilder::get()->withoutEmailAlreadySent()->build();

        $admin_1 = UserTestBuilder::anActiveUser()->withEmail('morpheus@example.com')->build();
        $admin_2 = UserTestBuilder::anActiveUser()->withEmail('neo@example.com')->build();
        $admin_3 = UserTestBuilder::aUser()
            ->withStatus(PFUser::STATUS_SUSPENDED)
            ->withEmail('cypher@example.com')
            ->build();

        $git_service = $this->createMock(GitService::class);
        $project     = $this->createMock(Project::class);
        $project->method('getService')->willReturn($git_service);
        $project->method('getAdmins')->willReturn([$admin_1, $admin_2, $admin_3]);
        $project->method('getUnixNameLowerCase')->willReturn('reloaded');

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            $project,
            false
        );

        $mail_builder = $this->createMock(\MailBuilder::class);
        $mail_builder
            ->expects(self::once())
            ->method('buildAndSendEmail')
            ->with(
                $project,
                $this->callback(
                    function (Notification $notification) {
                        return $notification->getEmails() === ['morpheus@example.com', 'neo@example.com']
                            && $notification->getSubject() === 'Invalid GitLab credentials'
                            && $notification->getTextBody() === 'It appears that the access token for the_full_url is invalid. Tuleap cannot perform actions on it. Please check configuration on https://tuleap.example.com/plugins/git/reloaded'
                            && $notification->getGotoLink() === 'https://tuleap.example.com/plugins/git/reloaded'
                            && $notification->getServiceName() === 'Git';
                    }
                ),
                self::isInstanceOf(\MailEnhancer::class),
            );

        $dao = $this->createMock(IntegrationApiTokenDao::class);
        $dao
            ->expects(self::once())
            ->method('storeTheFactWeAlreadySendEmailForInvalidToken')
            ->with(1);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with('Notification has been sent to project administrators to warn them that the token appears to be invalid');

        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');

        $notifier = new InvalidCredentialsNotifier(
            $mail_builder,
            $dao,
            $logger
        );

        $notifier->notifyGitAdministratorsThatCredentialsAreInvalid($integration, $credentials);
    }
}

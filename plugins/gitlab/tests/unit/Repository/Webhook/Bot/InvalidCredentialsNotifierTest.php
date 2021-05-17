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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Notification;
use PFUser;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Git\GitService;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenDao;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class InvalidCredentialsNotifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDoesNothingIfEmailIsAlreadySent(): void
    {
        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $credentials = CredentialsTestBuilder::get()->withEmailAlreadySent()->build();

        $mail_builder = Mockery::mock(\MailBuilder::class);
        $mail_builder->shouldReceive('buildAndSendEmail')->never();

        $dao = Mockery::mock(GitlabBotApiTokenDao::class);
        $dao->shouldReceive('storeTheFactWeAlreadySendEmailForInvalidToken')->never();

        $notifier = new InvalidCredentialsNotifier(
            $mail_builder,
            new InstanceBaseURLBuilder(),
            $dao,
            Mockery::mock(LoggerInterface::class),
        );

        $notifier->notifyGitAdministratorsThatCredentialsAreInvalid($repository, $credentials);
    }

    public function testItDoesNothingIfGitIsNotActivatedInProject(): void
    {
        $project = Mockery::mock(Project::class, ['getService' => null]);

        $repository = new GitlabRepository(
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

        $mail_builder = Mockery::mock(\MailBuilder::class);
        $mail_builder->shouldReceive('buildAndSendEmail')->never();

        $dao = Mockery::mock(GitlabBotApiTokenDao::class);
        $dao->shouldReceive('storeTheFactWeAlreadySendEmailForInvalidToken')->never();

        $notifier = new InvalidCredentialsNotifier(
            $mail_builder,
            new InstanceBaseURLBuilder(),
            $dao,
            Mockery::mock(LoggerInterface::class),
        );

        $notifier->notifyGitAdministratorsThatCredentialsAreInvalid($repository, $credentials);
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

        $git_service = Mockery::mock(GitService::class);
        $project     = Mockery::mock(
            Project::class,
            [
                'getService'           => $git_service,
                'getAdmins'            => [$admin_1, $admin_2, $admin_3],
                'getUnixNameLowerCase' => 'reloaded',
            ]
        );

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            $project,
            false
        );

        $mail_builder = Mockery::mock(\MailBuilder::class);
        $mail_builder
            ->shouldReceive('buildAndSendEmail')
            ->with(
                $project,
                Mockery::on(
                    function (Notification $notification) {
                        return $notification->getEmails() === ['morpheus@example.com', 'neo@example.com']
                            && $notification->getSubject() === 'Invalid GitLab credentials'
                            && $notification->getTextBody() === 'It appears that the access token for the_full_url is invalid. Tuleap cannot perform actions on it. Please check configuration on https://tuleap.example.com/plugins/git/reloaded'
                            && $notification->getGotoLink() === 'https://tuleap.example.com/plugins/git/reloaded'
                            && $notification->getServiceName() === 'Git';
                    }
                ),
                Mockery::type(\MailEnhancer::class),
            )
            ->once();

        $dao = Mockery::mock(GitlabBotApiTokenDao::class);
        $dao
            ->shouldReceive('storeTheFactWeAlreadySendEmailForInvalidToken')
            ->with(1)
            ->once();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->with('Notification has been sent to project administrators to warn them that the token appears to be invalid')
            ->once();

        $instance_base_url = Mockery::mock(InstanceBaseURLBuilder::class, ['build' => 'https://tuleap.example.com']);
        $notifier          = new InvalidCredentialsNotifier(
            $mail_builder,
            $instance_base_url,
            $dao,
            $logger
        );

        $notifier->notifyGitAdministratorsThatCredentialsAreInvalid($repository, $credentials);
    }
}

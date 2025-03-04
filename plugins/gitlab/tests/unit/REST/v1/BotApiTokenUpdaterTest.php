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

namespace Tuleap\Gitlab\REST\v1;

use GitPermissionsManager;
use Luracast\Restler\RestException;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\REST\I18NRestException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class BotApiTokenUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabProjectBuilder
     */
    private $project_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitPermissionsManager
     */
    private $permissions_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IntegrationApiTokenInserter
     */
    private $token_inserter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WebhookCreator
     */
    private $webhook_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    private BotApiTokenUpdater $updater;

    protected function setUp(): void
    {
        $this->repository_integration_factory = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->project_builder                = $this->createMock(GitlabProjectBuilder::class);
        $this->permissions_manager            = $this->createMock(GitPermissionsManager::class);
        $this->token_inserter                 = $this->createMock(IntegrationApiTokenInserter::class);
        $this->webhook_creator                = $this->createMock(WebhookCreator::class);
        $this->logger                         = $this->createMock(LoggerInterface::class);

        $this->updater = new BotApiTokenUpdater(
            $this->repository_integration_factory,
            $this->project_builder,
            $this->permissions_manager,
            $this->token_inserter,
            $this->webhook_creator,
            $this->logger,
        );
    }

    public function test404IfRequestedRepositoryIsNotFound(): void
    {
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            new ConcealedString('My New Token'),
        );

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->updater->update($patch, $this->createMock(\PFUser::class));
    }

    public function test404IfUserIsNotGitAdminOfTheProjectWhereTheGitlabRepositoryIsIntegrated(): void
    {
        $user = $this->createMock(\PFUser::class);

        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            new ConcealedString('My New Token'),
        );

        $repository = $this->createMock(GitlabRepositoryIntegration::class);

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project_a = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project_a);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project_a)
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->updater->update($patch, $user);
    }

    public function test400IfGitlabServerDoesNotAcceptsTheNewToken(): void
    {
        $user = $this->createMock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project_a = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project_a);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project_a)
            ->willReturn(true);

        $this->project_builder
            ->method('getProjectFromGitlabAPI')
            ->with(
                $this->callback(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getApiToken()->getToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            )
            ->willThrowException(new GitlabRequestException(400, 'not a valid token'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->updater->update($patch, $user);
    }

    public function test500IfGitlabServerAcceptsTheNewTokenButReturnsGibberish(): void
    {
        $user = $this->createMock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project_a = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project_a);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project_a)
            ->willReturn(true);

        $this->project_builder
            ->method('getProjectFromGitlabAPI')
            ->with(
                $this->callback(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getApiToken()->getToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            )
            ->willThrowException(new GitlabResponseAPIException('error'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->updater->update($patch, $user);
    }

    public function test400IfGitlabServerAcceptsTheNewTokenButNotTheCreationOfTheWebhook(): void
    {
        $user = $this->createMock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project_a = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project_a);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project_a)
            ->willReturn(true);

        $expected_credentials = $this->callback(
            function (Credentials $credentials) use ($token) {
                return $credentials->getApiToken()->getToken()->isIdenticalTo($token)
                    && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
            }
        );

        $this->project_builder
            ->method('getProjectFromGitlabAPI')
            ->with($expected_credentials, 123);

        $this->webhook_creator
            ->expects(self::once())
            ->method('generateWebhookInGitlabProject')
            ->with($expected_credentials, $repository)
            ->willThrowException(new GitlabRequestException(400, 'error at creation'));

        $this->token_inserter
            ->expects(self::never())
            ->method('insertToken');

        $this->logger
            ->expects(self::once())
            ->method('error');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->updater->update($patch, $user);
    }

    public function testItSavesTheNewTokenIfGitlabServerAcceptsTheNewTokenAndTheCreationOfTheWebhook(): void
    {
        $user = $this->createMock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project_a = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project_a);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project_a)
            ->willReturn(true);

        $expected_credentials = $this->callback(
            function (Credentials $credentials) use ($token) {
                return $credentials->getApiToken()->getToken()->isIdenticalTo($token)
                    && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
            }
        );

        $this->project_builder
            ->method('getProjectFromGitlabAPI')
            ->with($expected_credentials, 123);

        $this->webhook_creator
            ->expects(self::once())
            ->method('generateWebhookInGitlabProject')
            ->with($expected_credentials, $repository);

        $this->token_inserter
            ->expects(self::once())
            ->method('insertToken')
            ->with($repository, $token);

        $this->updater->update($patch, $user);
    }
}

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

namespace Tuleap\Gitlab\Repository\Webhook;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

class WebhookDeletorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var WebhookDeletor
     */
    private $deletor;
    /**
     * @var Credentials
     */
    private $credentials;

    protected function setUp(): void
    {
        $this->dao                   = Mockery::mock(WebhookDao::class);
        $this->gitlab_api_client     = Mockery::mock(ClientWrapper::class);
        $this->logger                = Mockery::mock(LoggerInterface::class);
        $this->credentials_retriever = Mockery::mock(CredentialsRetriever::class);

        $this->credentials = CredentialsTestBuilder::get()->build();

        $this->deletor = new WebhookDeletor(
            $this->dao,
            $this->gitlab_api_client,
            $this->logger
        );
    }

    public function testItDoesNotDeleteIfNoOldWebhook(): void
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

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('error')->never();

        $this->gitlab_api_client->shouldReceive('deleteUrl')->never();

        $this->dao->shouldReceive('deleteGitlabRepositoryWebhook')->never();

        $this->logger->shouldReceive('info')->never();

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $repository);
    }

    public function testItDoesNotDeleteIfNoOldWebhookId(): void
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

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn([]);

        $this->gitlab_api_client->shouldReceive('deleteUrl')->never();

        $this->dao->shouldReceive('deleteGitlabRepositoryWebhook')->never();

        $this->logger->shouldReceive('info')->never();

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $repository);
    }

    public function testItOnlyDeleteDBIfNoCredentials(): void
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

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn(['gitlab_webhook_id' => 6]);

        $this->logger->shouldReceive('error')->never();

        $this->gitlab_api_client->shouldReceive('deleteUrl')->never();

        $this->dao->shouldReceive('deleteGitlabRepositoryWebhook')->once();

        $this->logger->shouldReceive('info')->never();

        $this->deletor->deleteGitlabWebhookFromGitlabRepository(null, $repository);
    }

    public function testItRemovesOldWebhookFromServerAndDb(): void
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

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn(['gitlab_webhook_id' => 6]);

        $this->gitlab_api_client
            ->shouldReceive('deleteUrl')
            ->with(
                $this->credentials,
                '/projects/2/hooks/6'
            );

        $this->dao
            ->shouldReceive('deleteGitlabRepositoryWebhook')
            ->with(1)
            ->once();

        $this->dao
            ->shouldReceive('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->andReturnFalse();

        $this->logger->shouldReceive('info')->with("Deleting previous hook for the_full_url")->once();

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $repository);
    }

    public function testItRemovesOldWebhookFromServerAndDbAndInAnotherIntegrations(): void
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

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn(['gitlab_webhook_id' => 6]);

        $this->gitlab_api_client
            ->shouldReceive('deleteUrl')
            ->with(
                $this->credentials,
                '/projects/2/hooks/6'
            );

        $this->dao
            ->shouldReceive('deleteGitlabRepositoryWebhook')
            ->with(1)
            ->once();

        $this->dao
            ->shouldReceive('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('deleteAllGitlabRepositoryWebhookConfigurationUsingOldOne')
            ->with(6)
            ->once();

        $this->logger->shouldReceive('info')->with("Deleting previous hook for the_full_url")->once();

        $this->logger->shouldReceive('warning')
            ->with(
                "The webhook is used by another integrations (it may come from old integration). " .
                "It will be deleted on GitLab side and configuration must be regenerated for these integrations."
            )
            ->once();

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $repository);
    }

    public function testItThrowsExceptionIfWebhookCreationReturnsUnexpectedPayload(): void
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

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn(['gitlab_webhook_id' => 6]);

        $this->gitlab_api_client
            ->shouldReceive('deleteUrl')
            ->with(
                $this->credentials,
                '/projects/2/hooks/6'
            )
            ->andThrow(new GitlabRequestException(404, "Not found"))
            ->once();

        $this->dao
            ->shouldReceive('storeWebhook')
            ->never();

        $this->logger
            ->shouldReceive('info')
            ->with('Deleting previous hook for the_full_url')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Unable to delete the hook. Ignoring error: Error returned by the GitLab server: Not found')
            ->once();

        $this->dao
            ->shouldReceive('isIntegrationWebhookUsedByIntegrations')
            ->with(1)
            ->andReturnFalse();

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $repository);
    }
}

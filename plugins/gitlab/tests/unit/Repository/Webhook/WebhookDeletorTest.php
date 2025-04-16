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

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookDeletorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WebhookDao
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    private Credentials $credentials;
    private WebhookDeletor $deletor;

    protected function setUp(): void
    {
        $this->dao               = $this->createMock(WebhookDao::class);
        $this->gitlab_api_client = $this->createMock(ClientWrapper::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->credentials       = CredentialsTestBuilder::get()->build();

        $this->deletor = new WebhookDeletor(
            $this->dao,
            $this->gitlab_api_client,
            $this->logger
        );
    }

    public function testItDoesNotDeleteIfNoOldWebhook(): void
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

        $this->dao
            ->expects($this->once())
            ->method('getGitlabRepositoryWebhook')
            ->with(1)
            ->willReturn(null);

        $this->logger->expects($this->never())->method('error');

        $this->gitlab_api_client->expects($this->never())->method('deleteUrl');

        $this->dao->expects($this->never())->method('deleteGitlabRepositoryWebhook');

        $this->logger->expects($this->never())->method('info');

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $integration);
    }

    public function testItDoesNotDeleteIfNoOldWebhookId(): void
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

        $this->dao
            ->expects($this->once())
            ->method('getGitlabRepositoryWebhook')
            ->with(1)
            ->willReturn([]);

        $this->gitlab_api_client
            ->expects($this->never())
            ->method('deleteUrl');

        $this->dao
            ->expects($this->never())
            ->method('deleteGitlabRepositoryWebhook');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $integration);
    }

    public function testItOnlyDeleteDBIfNoCredentials(): void
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

        $this->dao
            ->expects($this->once())
            ->method('getGitlabRepositoryWebhook')
            ->with(1)
            ->willReturn(['gitlab_webhook_id' => 6]);

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->gitlab_api_client
            ->expects($this->never())
            ->method('deleteUrl');

        $this->dao
            ->expects($this->once())
            ->method('deleteGitlabRepositoryWebhook');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->deletor->deleteGitlabWebhookFromGitlabRepository(null, $integration);
    }

    public function testItRemovesOldWebhookFromServerAndDb(): void
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

        $this->dao
            ->expects($this->once())
            ->method('getGitlabRepositoryWebhook')
            ->with(1)
            ->willReturn(['gitlab_webhook_id' => 6]);

        $this->gitlab_api_client
            ->method('deleteUrl')
            ->with(
                $this->credentials,
                '/projects/2/hooks/6'
            );

        $this->dao
            ->expects($this->once())
            ->method('deleteGitlabRepositoryWebhook')
            ->with(1);

        $this->dao
            ->method('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Deleting previous hook for the_full_url');

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $integration);
    }

    public function testItRemovesOldWebhookFromServerAndDbAndInAnotherIntegrations(): void
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

        $this->dao
            ->expects($this->once())
            ->method('getGitlabRepositoryWebhook')
            ->with(1)
            ->willReturn(['gitlab_webhook_id' => 6]);

        $this->gitlab_api_client
            ->method('deleteUrl')
            ->with(
                $this->credentials,
                '/projects/2/hooks/6'
            );

        $this->dao
            ->expects($this->once())
            ->method('deleteGitlabRepositoryWebhook')
            ->with(1);

        $this->dao
            ->method('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->willReturn(true);

        $this->dao
            ->expects($this->once())
            ->method('deleteAllGitlabRepositoryWebhookConfigurationUsingOldOne')
            ->with(6);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Deleting previous hook for the_full_url');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'The webhook is used by another integrations (it may come from old integration). ' .
                'It will be deleted on GitLab side and configuration must be regenerated for these integrations.'
            );

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $integration);
    }

    public function testItThrowsExceptionIfWebhookCreationReturnsUnexpectedPayload(): void
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

        $this->dao
            ->expects($this->once())
            ->method('getGitlabRepositoryWebhook')
            ->with(1)
            ->willReturn(['gitlab_webhook_id' => 6]);

        $this->gitlab_api_client
            ->expects($this->once())
            ->method('deleteUrl')
            ->with(
                $this->credentials,
                '/projects/2/hooks/6'
            )
            ->willThrowException(new GitlabRequestException(404, 'Not found'));

        $this->dao
            ->expects($this->never())
            ->method('storeWebhook');

        $this->logger
            ->method('info')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        'Deleting previous hook for the_full_url',
                        'Unable to delete the hook. Ignoring error: Error returned by the GitLab server: Not found' => true,
                    };
                }
            );

        $this->dao
            ->method('isIntegrationWebhookUsedByIntegrations')
            ->with(1)
            ->willReturn(false);

        $this->deletor->deleteGitlabWebhookFromGitlabRepository($this->credentials, $integration);
    }
}

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

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private WebhookCreator $creator;
    private WebhookDao&MockObject $dao;
    private ClientWrapper&MockObject $gitlab_api_client;
    private TestLogger $logger;
    private WebhookDeletor $webhook_deletor;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao               = $this->createMock(WebhookDao::class);
        $this->gitlab_api_client = $this->createMock(ClientWrapper::class);
        $this->logger            = new TestLogger();
        $this->webhook_deletor   = new WebhookDeletor(
            $this->dao,
            $this->gitlab_api_client,
            $this->logger
        );

        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');

        $this->creator = new WebhookCreator(
            $this->dao,
            $this->webhook_deletor,
            $this->gitlab_api_client,
            $this->logger,
        );
    }

    public function testItGeneratesAWebhookForRepository(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

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
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                self::callback(
                    function (array $config): bool {
                        return count(array_keys($config)) === 6
                            && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/integration/1/webhook'
                            && is_string($config['token'])
                            && $config['push_events'] === true
                            && $config['merge_requests_events'] === true
                            && $config['tag_push_events'] === true
                            && $config['enable_ssl_verification'] === true;
                    }
                )
            )
            ->willReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->expects($this->once())
            ->method('storeWebhook')
            ->with(1, 7, self::anything());

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);

        self::assertTrue($this->logger->hasInfoThatContains('Creating new hook for the_full_url'));
    }

    public function testItGeneratesAWebhookForRepositoryAndRemoveTheOldOne(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

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
                $credentials,
                '/projects/2/hooks/6'
            );
        $this->dao
            ->expects($this->once())
            ->method('deleteGitlabRepositoryWebhook')
            ->with(1);

        $this->gitlab_api_client
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                self::callback(
                    function (array $config): bool {
                        return count(array_keys($config)) === 6
                            && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/integration/1/webhook'
                            && is_string($config['token'])
                            && $config['push_events'] === true
                            && $config['merge_requests_events'] === true
                            && $config['tag_push_events'] === true
                            && $config['enable_ssl_verification'] === true;
                    }
                )
            )
            ->willReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->expects($this->once())
            ->method('storeWebhook')
            ->with(1, 7, self::anything());

        $this->dao
            ->method('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->willReturn(false);

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);

        self::assertTrue($this->logger->hasInfoThatContains('Deleting previous hook for the_full_url'));
        self::assertTrue($this->logger->hasInfoThatContains('Creating new hook for the_full_url'));
    }

    public function testItGeneratesAWebhookForRepositoryAndDoesNotRemoveTheOldOneIfWebhookUsedByAnotherIntegration(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

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
                $credentials,
                '/projects/2/hooks/6'
            );
        $this->dao
            ->expects($this->once())
            ->method('deleteGitlabRepositoryWebhook')
            ->with(1);

        $this->gitlab_api_client
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                self::callback(
                    function (array $config): bool {
                        return count(array_keys($config)) === 6
                            && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/integration/1/webhook'
                            && is_string($config['token'])
                            && $config['push_events'] === true
                            && $config['merge_requests_events'] === true
                            && $config['tag_push_events'] === true
                            && $config['enable_ssl_verification'] === true;
                    }
                )
            )
            ->willReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->expects($this->once())
            ->method('storeWebhook')
            ->with(1, 7, self::anything());

        $this->dao
            ->method('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->willReturn(true);

        $this->dao
            ->method('deleteAllGitlabRepositoryWebhookConfigurationUsingOldOne')
            ->with(6);

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);

        self::assertTrue($this->logger->hasWarningThatContains('The webhook is used by another integrations (it may come from old integration). It will be deleted on GitLab side and configuration must be regenerated for these integrations.'));
        self::assertTrue($this->logger->hasInfoThatContains('Creating new hook for the_full_url'));
        self::assertTrue($this->logger->hasInfoThatContains('Deleting previous hook for the_full_url'));
    }

    public function testItDoesNotSaveAnythingIfGitlabDidNotCreateTheWebhook(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

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
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                self::callback(
                    function (array $config) {
                        return count(array_keys($config)) === 6
                            && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/integration/1/webhook'
                            && is_string($config['token'])
                            && $config['push_events'] === true
                            && $config['merge_requests_events'] === true
                            && $config['tag_push_events'] === true
                            && $config['enable_ssl_verification'] === true;
                    }
                )
            )
            ->willThrowException($this->createStub(GitlabRequestException::class));

        $this->dao
            ->expects($this->never())
            ->method('storeWebhook');

        $this->expectException(GitlabRequestException::class);

        try {
            $this->creator->generateWebhookInGitlabProject($credentials, $integration);
        } finally {
            $this->logger->hasInfo('Creating new hook for the_full_url');
        }
    }

    public function testItThrowsExceptionIfWebhookCreationReturnsUnexpectedPayload(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

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
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                self::callback(
                    function (array $config): bool {
                        return count(array_keys($config)) === 6
                            && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/integration/1/webhook'
                            && is_string($config['token'])
                            && $config['push_events'] === true
                            && $config['merge_requests_events'] === true
                            && $config['tag_push_events'] === true
                            && $config['enable_ssl_verification'] === true;
                    }
                )
            )
            ->willReturn([]);

        $this->dao
            ->expects($this->never())
            ->method('storeWebhook');

        $this->expectException(WebhookCreationException::class);

        try {
            $this->creator->generateWebhookInGitlabProject($credentials, $integration);
        } finally {
            self::assertTrue($this->logger->hasInfoThatContains('Creating new hook for the_full_url'));
            self::assertTrue($this->logger->hasErrorThatContains('Received response payload seems invalid'));
        }
    }
}

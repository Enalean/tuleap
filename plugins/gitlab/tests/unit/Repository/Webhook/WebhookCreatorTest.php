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
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\InstanceBaseURLBuilder;

class WebhookCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var WebhookCreator
     */
    private $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|KeyFactory
     */
    private $key_factory;
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookDeletor
     */
    private $webhook_deletor;

    protected function setUp(): void
    {
        $this->key_factory       = Mockery::mock(KeyFactory::class);
        $this->dao               = Mockery::mock(WebhookDao::class);
        $this->gitlab_api_client = Mockery::mock(ClientWrapper::class);
        $this->logger            = Mockery::mock(LoggerInterface::class);
        $this->webhook_deletor   = new WebhookDeletor(
            $this->dao,
            $this->gitlab_api_client,
            $this->logger
        );

        $instance_base_url = Mockery::mock(InstanceBaseURLBuilder::class, ['build' => 'https://tuleap.example.com']);

        $this->creator = new WebhookCreator(
            $this->key_factory,
            $this->dao,
            $this->webhook_deletor,
            $this->gitlab_api_client,
            $instance_base_url,
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
            Project::buildForTest(),
            false
        );

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn([]);

        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key
            ->shouldReceive('getRawKeyMaterial')
            ->andReturns(
                str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            );
        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->andReturn($encryption_key)
            ->once();

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                Mockery::on(
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
            ->once()
            ->andReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->shouldReceive('storeWebhook')
            ->with(1, 7, Mockery::type('string'))
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Creating new hook for the_full_url')
            ->once();

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);
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
                $credentials,
                '/projects/2/hooks/6'
            );
        $this->dao
            ->shouldReceive('deleteGitlabRepositoryWebhook')
            ->with(1)
            ->once();

        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key
            ->shouldReceive('getRawKeyMaterial')
            ->andReturns(
                str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            );
        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->andReturn($encryption_key)
            ->once();

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                Mockery::on(
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
            ->once()
            ->andReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->shouldReceive('storeWebhook')
            ->with(1, 7, Mockery::type('string'))
            ->once();

        $this->dao
            ->shouldReceive('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('info')
            ->with('Deleting previous hook for the_full_url')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Creating new hook for the_full_url')
            ->once();

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);
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
                $credentials,
                '/projects/2/hooks/6'
            );
        $this->dao
            ->shouldReceive('deleteGitlabRepositoryWebhook')
            ->with(1)
            ->once();

        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key
            ->shouldReceive('getRawKeyMaterial')
            ->andReturns(
                str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            );
        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->andReturn($encryption_key)
            ->once();

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                '/projects/2/hooks',
                Mockery::on(
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
            ->once()
            ->andReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->shouldReceive('storeWebhook')
            ->with(1, 7, Mockery::type('string'))
            ->once();

        $this->dao
            ->shouldReceive('isIntegrationWebhookUsedByIntegrations')
            ->with(6)
            ->andReturnTrue();

        $this->logger
            ->shouldReceive('warning')
            ->with(
                "The webhook is used by another integrations (it may come from old integration). It will be deleted on GitLab side and configuration must be regenerated for these integrations."
            )
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Creating new hook for the_full_url')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Deleting previous hook for the_full_url')
            ->once();

        $this->dao
            ->shouldReceive('deleteAllGitlabRepositoryWebhookConfigurationUsingOldOne')
            ->with(6)
            ->andReturnTrue();

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);
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
            Project::buildForTest(),
            false
        );

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn([]);

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                "/projects/2/hooks",
                Mockery::on(
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
            ->once()
            ->andThrow(Mockery::mock(GitlabRequestException::class));

        $this->dao
            ->shouldReceive('storeWebhook')
            ->never();

        $this->logger
            ->shouldReceive('info')
            ->with('Creating new hook for the_full_url')
            ->once();

        $this->expectException(GitlabRequestException::class);

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);
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
            Project::buildForTest(),
            false
        );

        $this->dao
            ->shouldReceive('getGitlabRepositoryWebhook')
            ->with(1)
            ->once()
            ->andReturn([]);

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                "/projects/2/hooks",
                Mockery::on(
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
            ->once()
            ->andReturn([]);

        $this->dao
            ->shouldReceive('storeWebhook')
            ->never();

        $this->logger
            ->shouldReceive('info')
            ->with('Creating new hook for the_full_url')
            ->once();

        $this->logger
            ->shouldReceive('error')
            ->with('Received response payload seems invalid')
            ->once();

        $this->expectException(WebhookCreationException::class);

        $this->creator->generateWebhookInGitlabProject($credentials, $integration);
    }
}

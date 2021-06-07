<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use DateTimeImmutable;
use LogicException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookData;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookData;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookActionProcessor;

final class WebhookActionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var WebhookActions
     */
    private $actions;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegrationDao
     */
    private $repository_integration_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var GitlabRepositoryIntegration
     */
    private $gitlab_repository_integration;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushWebhookActionProcessor
     */
    private $post_push_webhook_action_processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostMergeRequestWebhookActionProcessor
     */
    private $post_merge_request_webhook_action_processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TagPushWebhookActionProcessor
     */
    private $tag_push_webhook_action_processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository_integration_dao                  = Mockery::mock(GitlabRepositoryIntegrationDao::class);
        $this->post_push_webhook_action_processor          = Mockery::mock(PostPushWebhookActionProcessor::class);
        $this->post_merge_request_webhook_action_processor = Mockery::mock(PostMergeRequestWebhookActionProcessor::class);
        $this->tag_push_webhook_action_processor           = Mockery::mock(TagPushWebhookActionProcessor::class);
        $this->logger                                      = Mockery::mock(LoggerInterface::class);

        $this->actions = new WebhookActions(
            $this->repository_integration_dao,
            $this->post_push_webhook_action_processor,
            $this->post_merge_request_webhook_action_processor,
            $this->tag_push_webhook_action_processor,
            $this->logger
        );

        $this->gitlab_repository_integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );
    }

    public function testItDelegatesProcessingForPostPushWebhook(): void
    {
        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            "3cffabe5",
            "refs/heads/main",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01',
                    'commit TULEAP-123 01',
                    "master",
                    1608110510,
                    'john-snow@example.com',
                    'John Snow'
                )
            ]
        );

        $now = new DateTimeImmutable();

        $this->repository_integration_dao->shouldReceive('updateLastPushDateForIntegration')
            ->once()
            ->with(1, $now->getTimestamp());

        $this->logger
            ->shouldReceive('info')
            ->with('Last update date successfully updated for GitLab repository #1')
            ->once();
        $this->logger->shouldNotReceive('error');

        $this->post_push_webhook_action_processor
            ->shouldReceive('process')
            ->with($this->gitlab_repository_integration, $webhook_data, $now)
            ->once();

        $this->actions->performActions(
            $this->gitlab_repository_integration,
            $webhook_data,
            $now
        );
    }

    public function testItDelegatesProcessingForPostMergeRequestWebhook(): void
    {
        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            'TULEAP-123',
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $now = new DateTimeImmutable();

        $this->repository_integration_dao->shouldReceive('updateLastPushDateForIntegration')
            ->once()
            ->with(1, $now->getTimestamp());

        $this->logger
            ->shouldReceive('info')
            ->with('Last update date successfully updated for GitLab repository #1')
            ->once();
        $this->logger->shouldNotReceive('error');

        $this->post_push_webhook_action_processor
            ->shouldNotReceive('process');

        $this->post_merge_request_webhook_action_processor
            ->shouldReceive('process')
            ->with($this->gitlab_repository_integration, $merge_request_webhook_data)
            ->once();

        $this->actions->performActions(
            $this->gitlab_repository_integration,
            $merge_request_webhook_data,
            $now
        );
    }

    public function testItThrowsALogicExceptionIfWebhookTypeNotKnown(): void
    {
        $webhook_data = new class implements WebhookData {
            public function getEventName(): string
            {
                return 'WHATEVER';
            }

            public function getGitlabProjectId(): int
            {
                return 0;
            }

            public function getGitlabWebUrl(): string
            {
                return '';
            }

            public function getCommits(): array
            {
                return [];
            }
        };

        $this->repository_integration_dao
            ->shouldNotReceive('updateLastPushDateForIntegration');

        $this->logger
            ->shouldReceive('error')
            ->with('The provided webhook type WHATEVER is unknown')
            ->once();

        $this->expectException(LogicException::class);

        $this->actions->performActions(
            $this->gitlab_repository_integration,
            $webhook_data,
            new DateTimeImmutable(),
        );
    }
}

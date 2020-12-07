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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use DateTimeImmutable;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Reference;
use ReferenceManager;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferencesParser;

final class PostPushWebhookActionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostPushWebhookActionProcessor
     */
    private $processor;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;

    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->gitlab_repository_project_retriever = Mockery::mock(GitlabRepositoryProjectRetriever::class);
        $this->reference_manager                   = Mockery::mock(ReferenceManager::class);
        $this->event_manager                       = Mockery::mock(EventManager::class);

        $this->processor = new PostPushWebhookActionProcessor(
            new CommitTuleapReferencesParser(),
            $this->gitlab_repository_project_retriever,
            $this->reference_manager,
            $this->event_manager,
            $this->logger
        );
    }

    public function testItProcessesActionsForPostPushWebhook(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable()
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01'
                )
            ]
        );

        $this->gitlab_repository_project_retriever->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->once()
            ->with($gitlab_repository)
            ->andReturn([
                Project::buildForTest()
            ]);

        $this->reference_manager->shouldReceive('loadReferenceFromKeyword')
            ->once()
            ->with('art', 123)
            ->andReturn(
                new Reference(
                    0,
                    'key',
                    'desc',
                    'link',
                    'P',
                    'service_short_name',
                    'nature',
                    1,
                    100
                )
            );

        $this->event_manager->shouldReceive('processEvent')
            ->once()
            ->with(
                'get_artifact_reference_group_id',
                Mockery::on(function (array &$params) {
                    $params['group_id'] = 101;
                    return true;
                })
            );

        $this->logger
            ->shouldReceive('info')
            ->with("1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c")
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with("Reference to Tuleap artifact #123 found.")
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with("Tuleap artifact #123 found, cross-reference will be added for each project the GitLab repository is integrated in.")
            ->once();

        $this->reference_manager->shouldReceive('insertCrossReference')->once();

        $this->processor->process($gitlab_repository, $webhook_data);
    }

    public function testItDoesNothingIfArtifactDoesNotExist(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable()
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01'
                )
            ]
        );

        $this->reference_manager->shouldNotReceive('loadReferenceFromKeyword');

        $this->event_manager->shouldReceive('processEvent')
            ->once();

        $this->gitlab_repository_project_retriever->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->once()
            ->with($gitlab_repository)
            ->andReturn([
                Project::buildForTest()
            ]);

        $this->logger
            ->shouldReceive('info')
            ->with("1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c")
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with("Reference to Tuleap artifact #123 found.")
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("Tuleap artifact #123 not found, no cross-reference will be added.")
            ->once();

        $this->reference_manager->shouldNotReceive('insertCrossReference');

        $this->processor->process($gitlab_repository, $webhook_data);
    }

    public function testItDoesNothingIfArtReferenceNotFound(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable()
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01'
                )
            ]
        );

        $this->reference_manager->shouldReceive('loadReferenceFromKeyword')
            ->once()
            ->with('art', 123)
            ->andReturnNull();

        $this->event_manager->shouldReceive('processEvent')
            ->once()
            ->with(
                'get_artifact_reference_group_id',
                Mockery::on(function (array &$params) {
                    $params['group_id'] = 101;
                    return true;
                })
            );

        $this->gitlab_repository_project_retriever->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->once()
            ->with($gitlab_repository)
            ->andReturn([
                Project::buildForTest()
            ]);

        $this->logger
            ->shouldReceive('info')
            ->with("1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c")
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with("Reference to Tuleap artifact #123 found.")
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with("Tuleap artifact #123 found, cross-reference will be added for each project the GitLab repository is integrated in.")
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("No reference found with the keyword 'art', and this must not happen. If you read this, this is really bad.")
            ->once();

        $this->reference_manager->shouldNotReceive('insertCrossReference');

        $this->processor->process($gitlab_repository, $webhook_data);
    }
}

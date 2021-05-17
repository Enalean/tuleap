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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use DateTimeImmutable;
use Mockery;
use Project;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Gitlab\Artifact\ArtifactNotFoundException;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;
use UserNotExistException;

class PostPushWebhookCloseArtifactHandlerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PostPushWebhookCloseArtifactHandler
     */
    private $handler;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactRetriever
     */
    private $artifact_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectDao
     */
    private $repository_project_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushCommitArtifactUpdater
     */
    private $artifact_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_updater        = Mockery::mock(PostPushCommitArtifactUpdater::class);
        $this->artifact_retriever      = Mockery::mock(ArtifactRetriever::class);
        $this->user_manager            = Mockery::mock(UserManager::class);
        $this->semantic_status_factory = Mockery::mock(Tracker_Semantic_StatusFactory::class);
        $this->repository_project_dao  = Mockery::mock(GitlabRepositoryProjectDao::class);

        $this->handler = new PostPushWebhookCloseArtifactHandler(
            $this->artifact_updater,
            $this->artifact_retriever,
            $this->user_manager,
            $this->semantic_status_factory,
            $this->repository_project_dao,
            new NullLogger()
        );
    }

    public function testItAsksToAddACommentWhenNoSemanticDefined(): void
    {
        $reference    = new WebhookTuleapReference(123, "resolve");
        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            "master",
            1608110510,
            "john-snow@example.com",
            "John Snow"
        );

        $repository = new GitlabRepository(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tracker  = TrackerTestBuilder::aTracker()->withProject(\Project::buildForTest())->build();
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

        $this->repository_project_dao->shouldReceive('isArtifactClosureActionEnabledForRepositoryInProject')
            ->once()
            ->with(1, 101)
            ->andReturn(true);

        $user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $this->user_manager->shouldReceive('getUserById')
            ->once()
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->andReturn($user);

        $status_semantic = Mockery::mock(Tracker_Semantic_Status::class);
        $status_semantic->shouldReceive('getField')->andReturnNull();

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($status_semantic);

        $this->artifact_updater->shouldReceive('addTuleapArtifactCommentNoSemanticDefined')
            ->once()
            ->with(
                $artifact,
                $user,
                $webhook_data
            );

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $repository
        );
    }

    public function testItDoesNothingIfArtifactIsNotFound(): void
    {
        $reference    = new WebhookTuleapReference(123, "resolve");
        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            "master",
            1608110510,
            "john-snow@example.com",
            "John Snow"
        );

        $repository = new GitlabRepository(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andThrow(new ArtifactNotFoundException());

        $this->artifact_updater->shouldNotReceive('addTuleapArtifactCommentNoSemanticDefined');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $repository
        );
    }

    public function testItDoesNothingIfWorkflowUserIsNotFound(): void
    {
        $reference    = new WebhookTuleapReference(123, "resolve");
        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            "master",
            1608110510,
            "john-snow@example.com",
            "John Snow"
        );

        $repository = new GitlabRepository(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tracker  = TrackerTestBuilder::aTracker()->withProject(\Project::buildForTest())->build();
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

        $this->repository_project_dao->shouldReceive('isArtifactClosureActionEnabledForRepositoryInProject')
            ->once()
            ->with(1, 101)
            ->andReturn(true);

        $this->user_manager->shouldReceive('getUserById')
            ->once()
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->andReturnNull();

        $this->expectException(UserNotExistException::class);

        $this->artifact_updater->shouldNotReceive('addTuleapArtifactCommentNoSemanticDefined');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $repository
        );
    }

    public function testItAskToCommentArtifactAndChangeStatusIfStatusSemanticIsDefined(): void
    {
        $reference    = new WebhookTuleapReference(123, "resolve");
        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            "master",
            1608110510,
            "john-snow@example.com",
            "John Snow"
        );

        $repository = new GitlabRepository(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tracker  = TrackerTestBuilder::aTracker()->withProject(\Project::buildForTest())->build();
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

        $this->repository_project_dao->shouldReceive('isArtifactClosureActionEnabledForRepositoryInProject')
            ->once()
            ->with(1, 101)
            ->andReturn(true);

        $user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $this->user_manager->shouldReceive('getUserById')
            ->once()
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->andReturn($user);

        $status_semantic = Mockery::mock(Tracker_Semantic_Status::class);
        $status_semantic->shouldReceive('getField')->andReturn(
            Mockery::mock(Tracker_FormElement_Field_Selectbox::class)
        );

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($status_semantic);

        $this->artifact_updater->shouldReceive('closeTuleapArtifact')
            ->once()
            ->with(
                $artifact,
                $user,
                $webhook_data,
                $reference,
                $status_semantic->getField(),
                $repository
            );

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $repository
        );
    }

    public function testItDoesNothingIfNoCloseKeywordDefined(): void
    {
        $reference    = new WebhookTuleapReference(123);
        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            "master",
            1608110510,
            "john-snow@example.com",
            "John Snow"
        );

        $repository = new GitlabRepository(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->artifact_retriever->shouldNotReceive('retrieveArtifactById');
        $this->repository_project_dao->shouldNotReceive('isArtifactClosureActionEnabledForRepositoryInProject');
        $this->user_manager->shouldNotReceive('getUserById');
        $this->semantic_status_factory->shouldNotReceive('getByTracker');
        $this->artifact_updater->shouldNotReceive('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $repository
        );
    }

    public function testItDoesNothingIfRepositoryIsNotIntegratedInProjectOfArtifact(): void
    {
        $reference    = new WebhookTuleapReference(123, "resolve");
        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            "master",
            1608110510,
            "john-snow@example.com",
            "John Snow"
        );

        $repository = new GitlabRepository(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tracker  = TrackerTestBuilder::aTracker()->withProject(\Project::buildForTest())->build();
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

        $this->repository_project_dao->shouldReceive('isArtifactClosureActionEnabledForRepositoryInProject')
            ->once()
            ->with(1, 101)
            ->andReturn(false);

        $this->user_manager->shouldNotReceive('getUserById');
        $this->semantic_status_factory->shouldNotReceive('getByTracker');
        $this->artifact_updater->shouldNotReceive('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $repository
        );
    }
}

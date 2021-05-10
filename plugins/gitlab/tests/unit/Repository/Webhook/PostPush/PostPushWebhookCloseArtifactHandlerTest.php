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

use Mockery;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Gitlab\Artifact\ArtifactNotFoundException;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushCommitBotCommenter
     */
    private $commit_bot_commenter;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->commit_bot_commenter    = Mockery::mock(PostPushCommitBotCommenter::class);
        $this->artifact_retriever      = Mockery::mock(ArtifactRetriever::class);
        $this->user_manager            = Mockery::mock(UserManager::class);
        $this->semantic_status_factory = Mockery::mock(Tracker_Semantic_StatusFactory::class);

        $this->handler = new PostPushWebhookCloseArtifactHandler(
            $this->commit_bot_commenter,
            $this->artifact_retriever,
            $this->user_manager,
            $this->semantic_status_factory,
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

        $tracker  = Mockery::mock(Tracker::class);
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

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

        $this->commit_bot_commenter->shouldReceive('addTuleapArtifactComment')
            ->once()
            ->with(
                $artifact,
                $user,
                $webhook_data
            );

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data
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

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andThrow(new ArtifactNotFoundException());

        $this->commit_bot_commenter->shouldNotReceive('addTuleapArtifactComment');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data
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

        $tracker  = Mockery::mock(Tracker::class);
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

        $this->user_manager->shouldReceive('getUserById')
            ->once()
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->andReturnNull();

        $this->expectException(UserNotExistException::class);

        $this->commit_bot_commenter->shouldNotReceive('addTuleapArtifactComment');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data
        );
    }

    public function testItDoesNothingIfStatusSemanticIsDefined(): void
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

        $tracker  = Mockery::mock(Tracker::class);
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->artifact_retriever->shouldReceive('retrieveArtifactById')
            ->once()
            ->with($reference)
            ->andReturn($artifact);

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

        $this->commit_bot_commenter->shouldNotReceive('addTuleapArtifactComment');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data
        );
    }
}

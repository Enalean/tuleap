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
use Project;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\Artifact\ArtifactNotFoundException;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;
use UserNotExistException;

class PostPushWebhookCloseArtifactHandlerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostPushCommitArtifactUpdater
     */
    private $artifact_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactRetriever
     */
    private $artifact_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryProjectDao
     */
    private $repository_project_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabProjectBuilder
     */
    private $gitlab_project_builder;

    private PostPushWebhookCloseArtifactHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_updater        = $this->createMock(PostPushCommitArtifactUpdater::class);
        $this->artifact_retriever      = $this->createMock(ArtifactRetriever::class);
        $this->user_manager            = $this->createMock(UserManager::class);
        $this->semantic_status_factory = $this->createMock(Tracker_Semantic_StatusFactory::class);
        $this->repository_project_dao  = $this->createMock(GitlabRepositoryProjectDao::class);
        $this->credentials_retriever   = $this->createMock(CredentialsRetriever::class);
        $this->gitlab_project_builder  = $this->createMock(GitlabProjectBuilder::class);

        $this->handler = new PostPushWebhookCloseArtifactHandler(
            $this->artifact_updater,
            $this->artifact_retriever,
            $this->user_manager,
            $this->semantic_status_factory,
            $this->repository_project_dao,
            $this->credentials_retriever,
            $this->gitlab_project_builder,
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

        $integration = new GitlabRepositoryIntegration(
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
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willReturn($artifact);

        $this->repository_project_dao
            ->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(1, 101)
            ->willReturn(true);

        $user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $this->user_manager
            ->expects(self::once())
            ->method('getUserById')
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->willReturn($user);

        $status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        $status_semantic->method('getField')->willReturn(null);

        $this->semantic_status_factory
            ->expects(self::once())
            ->method('getByTracker')
            ->with($tracker)
            ->willReturn($status_semantic);

        $this->mockGitlabProjectDefaultBranch();

        $this->artifact_updater
            ->expects(self::once())
            ->method('addTuleapArtifactCommentNoSemanticDefined')
            ->with(
                $artifact,
                $user,
                $webhook_data
            );

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
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

        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willThrowException(new ArtifactNotFoundException());

        $this->artifact_updater->expects(self::never())->method('addTuleapArtifactCommentNoSemanticDefined');
        $this->artifact_updater->expects(self::never())->method('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
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

        $integration = new GitlabRepositoryIntegration(
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
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willReturn($artifact);

        $this->repository_project_dao
            ->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(1, 101)
            ->willReturn(true);

        $this->user_manager
            ->expects(self::once())
            ->method('getUserById')
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->willReturn(null);

        $this->expectException(UserNotExistException::class);

        $this->artifact_updater->expects(self::never())->method('addTuleapArtifactCommentNoSemanticDefined');
        $this->artifact_updater->expects(self::never())->method('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
        );
    }

    public function testItDoesNothingIfRepositoryDoesNotHaveCredential(): void
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

        $integration = new GitlabRepositoryIntegration(
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
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willReturn($artifact);

        $this->repository_project_dao
            ->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(1, 101)
            ->willReturn(true);

        $user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $this->user_manager
            ->expects(self::once())
            ->method('getUserById')
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->willReturn($user);

        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->willReturn(null);

        $this->gitlab_project_builder->expects(self::never())->method('getProjectFromGitlabAPI');

        $this->artifact_updater->expects(self::never())->method('addTuleapArtifactCommentNoSemanticDefined');
        $this->artifact_updater->expects(self::never())->method('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
        );
    }

    public function testItDoesNothingIfBranchIsNotDefault(): void
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

        $integration = new GitlabRepositoryIntegration(
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
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willReturn($artifact);

        $this->repository_project_dao
            ->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(1, 101)
            ->willReturn(true);

        $user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $this->user_manager
            ->expects(self::once())
            ->method('getUserById')
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->willReturn($user);

        $this->mockGitlabProjectAnotherDefaultBranch();

        $this->artifact_updater->expects(self::never())->method('addTuleapArtifactCommentNoSemanticDefined');
        $this->artifact_updater->expects(self::never())->method('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
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

        $integration = new GitlabRepositoryIntegration(
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
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willReturn($artifact);

        $this->repository_project_dao
            ->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(1, 101)
            ->willReturn(true);

        $user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $this->user_manager
            ->expects(self::once())
            ->method('getUserById')
            ->with(Tracker_Workflow_WorkflowUser::ID)
            ->willReturn($user);

        $status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        $status_semantic->method('getField')->willReturn(
            $this->createMock(Tracker_FormElement_Field_Selectbox::class)
        );

        $this->semantic_status_factory
            ->expects(self::once())
            ->method('getByTracker')
            ->with($tracker)
            ->willReturn($status_semantic);

        $this->mockGitlabProjectDefaultBranch();

        $this->artifact_updater
            ->expects(self::once())
            ->method('closeTuleapArtifact')
            ->with(
                $artifact,
                $user,
                $webhook_data,
                $reference,
                $status_semantic->getField(),
                $integration
            );

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
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

        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->artifact_retriever->expects(self::never())->method('retrieveArtifactById');
        $this->repository_project_dao->expects(self::never())->method('isArtifactClosureActionEnabledForRepositoryInProject');
        $this->user_manager->expects(self::never())->method('getUserById');
        $this->semantic_status_factory->expects(self::never())->method('getByTracker');
        $this->artifact_updater->expects(self::never())->method('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
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

        $integration = new GitlabRepositoryIntegration(
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
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_retriever
            ->expects(self::once())
            ->method('retrieveArtifactById')
            ->with($reference)
            ->willReturn($artifact);

        $this->repository_project_dao
            ->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(1, 101)
            ->willReturn(false);

        $this->user_manager->expects(self::never())->method('getUserById');
        $this->semantic_status_factory->expects(self::never())->method('getByTracker');
        $this->artifact_updater->expects(self::never())->method('closeTuleapArtifact');

        $this->handler->handleArtifactClosure(
            $reference,
            $webhook_data,
            $integration
        );
    }

    protected function mockGitlabProjectDefaultBranch(): void
    {
        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->willReturn($credentials);

        $this->gitlab_project_builder
            ->expects(self::once())
            ->method('getProjectFromGitlabAPI')
            ->with(
                $credentials,
                12
            )
            ->willReturn(
                new GitlabProject(
                    12,
                    "",
                    "https://example/MyRepo",
                    "MyRepo",
                    new DateTimeImmutable(),
                    "master"
                )
            );
    }

    protected function mockGitlabProjectAnotherDefaultBranch(): void
    {
        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->willReturn($credentials);

        $this->gitlab_project_builder
            ->expects(self::once())
            ->method('getProjectFromGitlabAPI')
            ->with(
                $credentials,
                12
            )
            ->willReturn(
                new GitlabProject(
                    12,
                    "",
                    "https://example/MyRepo",
                    "MyRepo",
                    new DateTimeImmutable(),
                    "main"
                )
            );
    }
}

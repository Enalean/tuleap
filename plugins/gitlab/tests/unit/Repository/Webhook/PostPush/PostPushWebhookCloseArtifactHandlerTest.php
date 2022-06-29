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
use Psr\Log\Test\TestLogger;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Semantic_Status;
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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateCommentOnlyChangesetStub;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use UserManager;
use UserNotExistException;

final class PostPushWebhookCloseArtifactHandlerTest extends TestCase
{
    private const POST_PUSH_LOG_PREFIX  = '|  |  |_ ';
    private const COMMITTER_EMAIL       = 'john-snow@example.com';
    private const COMMITTER_USERNAME    = 'jsnow';
    private const DONE_BIND_VALUE_ID    = 506;
    private const GITLAB_INTEGRATION_ID = 1;
    private const PROJECT_ID            = 101;
    private const GITLAB_REPOSITORY_ID  = 12;
    private const MASTER_BRANCH_NAME    = 'master';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactRetriever
     */
    private $artifact_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
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
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_Semantic_Status
     */
    private $status_semantic;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Artifact
     */
    private $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Credentials
     */
    private $credentials;
    private \PFUser $workflow_user;
    private WebhookTuleapReference $reference;
    private TestLogger $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&DoneValueRetriever
     */
    private $done_value_retriever;
    private \Project $project;
    private CreateCommentOnlyChangesetStub $comment_creator;

    protected function setUp(): void
    {
        $this->artifact_retriever     = $this->createMock(ArtifactRetriever::class);
        $this->user_manager           = $this->createMock(UserManager::class);
        $this->repository_project_dao = $this->createMock(GitlabRepositoryProjectDao::class);
        $this->credentials_retriever  = $this->createMock(CredentialsRetriever::class);
        $this->gitlab_project_builder = $this->createMock(GitlabProjectBuilder::class);
        $this->logger                 = new TestLogger();
        $this->status_semantic        = $this->createMock(Tracker_Semantic_Status::class);
        $this->credentials            = $this->createMock(Credentials::class);
        $this->reference              = new WebhookTuleapReference(123, 'resolve');
        $this->done_value_retriever   = $this->createStub(DoneValueRetriever::class);
        $this->comment_creator        = CreateCommentOnlyChangesetStub::withChangeset(
            ChangesetTestBuilder::aChangeset('7290')->build()
        );

        $this->user_manager->method('getUserByEmail')->willReturn(
            UserTestBuilder::aUser()->withUserName(self::COMMITTER_USERNAME)->build()
        );

        $this->project       = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->workflow_user = UserTestBuilder::anActiveUser()->withId(Tracker_Workflow_WorkflowUser::ID)->build();
        $tracker             = TrackerTestBuilder::aTracker()->withProject($this->project)->build();
        $this->artifact      = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($tracker);
        $this->artifact->method('isOpen')->willReturn(true);
    }

    private function handleArtifactClosure(): void
    {
        $status_semantic_factory = $this->createStub(\Tracker_Semantic_StatusFactory::class);
        $status_semantic_factory->method('getByTracker')->willReturn($this->status_semantic);

        $webhook_data = new PostPushCommitWebhookData(
            'feff4ced04b237abb8b4a50b4160099313152c3c',
            'A commit with references containing close artifact keyword',
            'A commit with reference: resolve TULEAP-123',
            self::MASTER_BRANCH_NAME,
            1608110510,
            self::COMMITTER_EMAIL,
            "John Snow"
        );

        $gitlab_integration = new GitlabRepositoryIntegration(
            self::GITLAB_INTEGRATION_ID,
            self::GITLAB_REPOSITORY_ID,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            $this->project,
            false
        );

        $handler = new PostPushWebhookCloseArtifactHandler(
            new PostPushCommitArtifactUpdater(
                $this->createStub(StatusValueRetriever::class),
                $this->done_value_retriever,
                $this->user_manager,
                $this->logger,
                $this->comment_creator,
            ),
            $this->artifact_retriever,
            $this->user_manager,
            $status_semantic_factory,
            $this->repository_project_dao,
            $this->credentials_retriever,
            $this->gitlab_project_builder,
            new PrefixedLogger($this->logger, self::POST_PUSH_LOG_PREFIX)
        );

        $handler->handleArtifactClosure($this->reference, $webhook_data, $gitlab_integration);
    }

    public function testItAskToCommentArtifactAndChangeStatusIfStatusSemanticIsDefined(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->mockArtifactClosureIsEnabled();
        $this->mockWorkflowUserIsFound();
        $this->mockGitLabRepositoryHasCredentials();
        $this->mockGitlabProjectDefaultBranch();
        $this->mockThereIsAStatusField();
        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')->willReturn(
            $this->getBindValue()
        );

        $this->artifact->method('createNewChangeset')
            ->with(self::anything(), self::anything(), $this->workflow_user)
            ->willReturn(ChangesetTestBuilder::aChangeset('515')->build());

        $this->handleArtifactClosure();
    }

    public function testItDoesNothingIfNoCloseKeywordDefined(): void
    {
        $this->reference = new WebhookTuleapReference(123);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->handleArtifactClosure();
    }

    public function testItDoesNothingIfReferencedArtifactIsNotFound(): void
    {
        $this->artifact_retriever->method('retrieveArtifactById')
            ->with($this->reference)
            ->willThrowException(new ArtifactNotFoundException());

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->handleArtifactClosure();

        $this->assertTrue($this->logger->hasError("|  |  |_ Artifact #123 not found"));
    }

    public function testItDoesNothingIfRepositoryIsNotIntegratedInProjectOfArtifact(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->repository_project_dao->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(self::GITLAB_INTEGRATION_ID, self::PROJECT_ID)
            ->willReturn(false);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->handleArtifactClosure();

        $this->assertTrue(
            $this->logger->hasWarning(
                "|  |  |_ Artifact #123 cannot be closed. " .
                "Either this artifact is not in a project where the GitLab repository is integrated in " .
                "or the artifact closure action is not enabled. " .
                "Skipping."
            )
        );
    }

    public function testItThrowsIfWorkflowUserIsNotFound(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->mockArtifactClosureIsEnabled();
        $this->user_manager->method('getUserById')->willReturn(null);

        $this->expectException(UserNotExistException::class);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->handleArtifactClosure();
    }

    public function testItDoesNothingIfRepositoryDoesNotHaveCredentials(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->mockArtifactClosureIsEnabled();
        $this->mockWorkflowUserIsFound();
        $this->credentials_retriever->expects(self::once())
            ->method('getCredentials')
            ->willReturn(null);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->handleArtifactClosure();

        $this->assertTrue(
            $this->logger->hasWarning(
                "|  |  |_ Artifact #123 cannot be closed because no token found for integration. Skipping."
            )
        );
    }

    public function testItDoesNothingIfBranchIsNotDefault(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->mockArtifactClosureIsEnabled();
        $this->mockWorkflowUserIsFound();
        $this->mockGitLabRepositoryHasCredentials();
        $this->gitlab_project_builder->expects(self::once())
            ->method('getProjectFromGitlabAPI')
            ->with(
                $this->credentials,
                self::GITLAB_REPOSITORY_ID
            )
            ->willReturn(
                new GitlabProject(
                    self::GITLAB_REPOSITORY_ID,
                    "",
                    "https://example/MyRepo",
                    "MyRepo",
                    new DateTimeImmutable(),
                    "main"
                )
            );

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->handleArtifactClosure();
    }

    public function testItAsksToAddACommentWhenNoSemanticDefined(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->mockArtifactClosureIsEnabled();
        $this->mockWorkflowUserIsFound();
        $this->mockGitLabRepositoryHasCredentials();
        $this->mockGitlabProjectDefaultBranch();
        $this->status_semantic->method('getField')->willReturn(null);

        $this->handleArtifactClosure();
    }

    public function testItLogsErrorIfNoValidValue(): void
    {
        $this->mockReferencedArtifactIsFound();
        $this->mockArtifactClosureIsEnabled();
        $this->mockWorkflowUserIsFound();
        $this->mockGitLabRepositoryHasCredentials();
        $this->mockGitlabProjectDefaultBranch();
        $this->mockThereIsAStatusField();
        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')->willThrowException(
            new NoPossibleValueException()
        );

        $this->handleArtifactClosure();

        $this->assertTrue(
            $this->logger->hasError(
                "|  |  |_ Artifact #123 cannot be closed. No possible value found regarding your configuration. Please check your transition and field dependencies."
            )
        );
    }

    private function getBindValue(): \Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::DONE_BIND_VALUE_ID,
            'Done',
            'irrelevant',
            3,
            false
        );
    }

    private function mockWorkflowUserIsFound(): void
    {
        $this->user_manager->method('getUserById')
            ->with(\Tracker_Workflow_WorkflowUser::ID)
            ->willReturn($this->workflow_user);
    }

    private function mockReferencedArtifactIsFound(): void
    {
        $this->artifact_retriever->method('retrieveArtifactById')
            ->with($this->reference)
            ->willReturn($this->artifact);
    }

    private function mockArtifactClosureIsEnabled(): void
    {
        $this->repository_project_dao->expects(self::once())
            ->method('isArtifactClosureActionEnabledForRepositoryInProject')
            ->with(self::GITLAB_INTEGRATION_ID, self::PROJECT_ID)
            ->willReturn(true);
    }

    private function mockGitLabRepositoryHasCredentials(): void
    {
        $this->credentials_retriever->method('getCredentials')->willReturn($this->credentials);
    }

    private function mockGitlabProjectDefaultBranch(): void
    {
        $this->gitlab_project_builder->expects(self::once())
            ->method('getProjectFromGitlabAPI')
            ->with(
                $this->credentials,
                self::GITLAB_REPOSITORY_ID
            )
            ->willReturn(
                new GitlabProject(
                    self::GITLAB_REPOSITORY_ID,
                    "",
                    "https://example/MyRepo",
                    "MyRepo",
                    new DateTimeImmutable(),
                    self::MASTER_BRANCH_NAME
                )
            );
    }

    private function mockThereIsAStatusField(): void
    {
        $field = $this->createStub(Tracker_FormElement_Field_Selectbox::class);
        $field->method('getId')->willReturn(945);
        $field->method('getFieldData')->willReturn(self::DONE_BIND_VALUE_ID);

        $this->status_semantic->method('getField')->willReturn($field);
    }
}

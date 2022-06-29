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
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_NoChangeException;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateCommentOnlyChangesetStub;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use UserManager;

final class PostPushCommitArtifactUpdaterTest extends TestCase
{
    private const COMMITTER_EMAIL     = 'mail@example.com';
    private const COMMIT_SHA1         = '99aa042c9c';
    private const COMMITTER_USERNAME  = 'asticotc';
    private const REPOSITORY_NAME     = 'MyRepo';
    private const STATUS_FIELD_ID     = 18;
    private const DONE_BIND_VALUE_ID  = 1234;
    private const DONE_LABEL          = 'Done';
    private const COMMITTER_FULL_NAME = "Coco L'Asticot";
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&StatusValueRetriever
     */
    private $status_value_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DoneValueRetriever
     */
    private $done_value_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Artifact
     */
    private Artifact $artifact;
    private Tracker_Workflow_WorkflowUser $workflow_user;
    private string $success_message;
    private string $no_semantic_defined_message;
    private CreateCommentOnlyChangesetStub $comment_creator;

    protected function setUp(): void
    {
        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);
        $this->done_value_retriever   = $this->createMock(DoneValueRetriever::class);
        $this->user_manager           = $this->createMock(UserManager::class);
        $this->comment_creator        = CreateCommentOnlyChangesetStub::withChangeset(
            ChangesetTestBuilder::aChangeset('5438')->build()
        );

        $this->workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                'user_id'     => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $this->artifact = $this->createMock(Artifact::class);

        $this->success_message             = sprintf(
            'solved by @%s with gitlab_commit #%s/%s',
            self::COMMITTER_USERNAME,
            self::REPOSITORY_NAME,
            self::COMMIT_SHA1
        );
        $this->no_semantic_defined_message = sprintf(
            '@%s attempts to close this artifact from GitLab but neither done nor status semantic defined.',
            self::COMMITTER_USERNAME
        );
    }

    private function addComment(): Ok|Err
    {
        $webhook_data = new PostPushCommitWebhookData(
            self::COMMIT_SHA1,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            1361860767,
            self::COMMITTER_EMAIL,
            self::COMMITTER_FULL_NAME
        );

        $updater = new PostPushCommitArtifactUpdater(
            $this->status_value_retriever,
            $this->done_value_retriever,
            $this->user_manager,
            new NullLogger(),
            $this->comment_creator
        );
        return $updater->addTuleapArtifactCommentNoSemanticDefined(
            $this->artifact,
            $this->workflow_user,
            $webhook_data
        );
    }

    public function testItDoesNotAddArtifactCommentWithoutStatusUpdatedIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $this->mockCommitterMatchingTuleapUser();
        $this->comment_creator = CreateCommentOnlyChangesetStub::withFault(
            Fault::fromMessage('Error during comment creation')
        );

        $result = $this->addComment();

        self::assertTrue(Result::isErr($result));
        $new_comment = $this->comment_creator->getNewComment();
        if (! $new_comment) {
            throw new \Exception('Expected to receive a new comment');
        }
        self::assertSame($this->no_semantic_defined_message, $new_comment->getBody());
        self::assertSame($this->workflow_user, $new_comment->getSubmitter());
        self::assertSame($this->artifact, $this->comment_creator->getArtifact());
    }

    public function testItCreatesANewCommentWithoutStatusUpdatedWithTheTuleapUsernameIfTheTuleapUserExists(): void
    {
        $this->mockCommitterMatchingTuleapUser();

        $result = $this->addComment();

        self::assertTrue(Result::isOk($result));
        $new_comment = $this->comment_creator->getNewComment();
        if (! $new_comment) {
            throw new \Exception('Expected to receive a new comment');
        }
        self::assertSame($this->no_semantic_defined_message, $new_comment->getBody());
        self::assertSame($this->workflow_user, $new_comment->getSubmitter());
        self::assertSame($this->artifact, $this->comment_creator->getArtifact());
    }

    public function testItCreatesANewCommentWithoutStatusUpdatedWithTheGitlabCommitterAuthorIfTheTuleapUserDoesNotExist(): void
    {
        $this->user_manager->method('getUserByEmail')->with(self::COMMITTER_EMAIL)->willReturn(null);

        $message = sprintf(
            '%s attempts to close this artifact from GitLab but neither done nor status semantic defined.',
            self::COMMITTER_FULL_NAME
        );

        $result = $this->addComment();

        self::assertTrue(Result::isOk($result));
        $new_comment = $this->comment_creator->getNewComment();
        if (! $new_comment) {
            throw new \Exception('Expected to receive a new comment');
        }
        self::assertSame($message, $new_comment->getBody());
        self::assertSame($this->workflow_user, $new_comment->getSubmitter());
        self::assertSame($this->artifact, $this->comment_creator->getArtifact());
    }

    private function closeTuleapArtifact(): void
    {
        $webhook_data = new PostPushCommitWebhookData(
            self::COMMIT_SHA1,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            1361860767,
            self::COMMITTER_EMAIL,
            self::COMMITTER_USERNAME
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $status_field
            ->method('getId')
            ->willReturn(self::STATUS_FIELD_ID);
        $status_field
            ->method('getFieldData')
            ->with(self::DONE_LABEL)
            ->willReturn(self::DONE_BIND_VALUE_ID);

        $this->mockCommitterMatchingTuleapUser();
        $reference   = new WebhookTuleapReference(12, 'resolves');
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            self::REPOSITORY_NAME,
            '',
            'https://example.com',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $updater = new PostPushCommitArtifactUpdater(
            $this->status_value_retriever,
            $this->done_value_retriever,
            $this->user_manager,
            new NullLogger(),
            $this->comment_creator
        );
        $updater->closeTuleapArtifact(
            $this->artifact,
            $this->workflow_user,
            $webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }

    public function testItDoesNotAddArtifactCommentAndUpdateStatusIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($this->artifact, $this->workflow_user)
            ->willReturn($this->getDoneValue());

        $this->artifact->method("createNewChangeset")
            ->with([self::STATUS_FIELD_ID => self::DONE_BIND_VALUE_ID], $this->success_message, $this->workflow_user)
            ->willThrowException(new Tracker_NoChangeException(1, 'xref'));

        $this->closeTuleapArtifact();
    }

    public function testItDoesNotAddArtifactCommentAndUpdateStatusIfNoPossibleValueAreFound(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($this->artifact, $this->workflow_user)
            ->willThrowException(new NoPossibleValueException());

        $this->artifact->expects(self::never())->method("createNewChangeset");

        $this->expectException(NoPossibleValueException::class);
        $this->closeTuleapArtifact();
    }

    public function testItDoesNotAddArtifactCommentAndUpdateStatusIfCommentIsNotCreated(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($this->artifact, $this->workflow_user)
            ->willReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, self::DONE_LABEL, "", 1, false));

        $this->artifact->method("createNewChangeset")
            ->with([self::STATUS_FIELD_ID => self::DONE_BIND_VALUE_ID], $this->success_message, $this->workflow_user)
            ->willReturn(null);

        $this->closeTuleapArtifact();
    }

    public function testItAddArtifactCommentWithoutStatusUpdatedIfNotCloseStatusSemanticDefined(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($this->artifact, $this->workflow_user)
            ->willThrowException(new SemanticDoneValueNotFoundException());

        $this->status_value_retriever
            ->expects(self::once())
            ->method("getFirstClosedValueUserCanRead")
            ->with($this->workflow_user, $this->artifact)
            ->willThrowException(new SemanticStatusClosedValueNotFoundException());

        $this->closeTuleapArtifact();
    }

    public function testItDoesNothingIfArtifactIsAlreadyClosed(): void
    {
        $this->artifact->method('isOpen')->willReturn(false);
        $this->artifact->method('getId')->willReturn(25);
        $this->artifact->expects(self::never())->method("createNewChangeset");

        $this->closeTuleapArtifact();
    }

    public function testItClosesArtifactWithDoneValue(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($this->artifact, $this->workflow_user)
            ->willReturn($this->getDoneValue());

        $this->artifact->method("createNewChangeset")
            ->with([self::STATUS_FIELD_ID => self::DONE_BIND_VALUE_ID], $this->success_message, $this->workflow_user)
            ->willReturn(ChangesetTestBuilder::aChangeset('7209')->build());

        $this->closeTuleapArtifact();
    }

    public function testItClosesArtifactWithFirstClosedStatusValue(): void
    {
        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($this->artifact, $this->workflow_user)
            ->willThrowException(new SemanticDoneValueNotFoundException());

        $this->status_value_retriever
            ->expects(self::once())
            ->method("getFirstClosedValueUserCanRead")
            ->with($this->workflow_user, $this->artifact)
            ->willReturn($this->getDoneValue());

        $this->artifact->method('isOpen')->willReturn(true);

        $this->artifact->method("createNewChangeset")
            ->with([self::STATUS_FIELD_ID => self::DONE_BIND_VALUE_ID], $this->success_message, $this->workflow_user)
            ->willReturn(ChangesetTestBuilder::aChangeset('7209')->build());

        $this->closeTuleapArtifact();
    }

    private function mockCommitterMatchingTuleapUser(): void
    {
        $committer = UserTestBuilder::aUser()
            ->withEmail(self::COMMITTER_EMAIL)
            ->withUserName(self::COMMITTER_USERNAME)
            ->build();
        $this->user_manager->method('getUserByEmail')->with(self::COMMITTER_EMAIL)->willReturn($committer);
    }

    private function getDoneValue(): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return new Tracker_FormElement_Field_List_Bind_StaticValue(14, self::DONE_LABEL, '', 1, false);
    }
}

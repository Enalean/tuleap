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

namespace Tuleap\Tracker\Artifact\Closure;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_NoChangeException;
use Tracker_Workflow_WorkflowUser;
use Tuleap\GlobalLanguageMock;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ReferenceStringStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BadSemanticCommentInCommonMarkFormatStub;
use Tuleap\Tracker\Test\Stub\CreateCommentOnlyChangesetStub;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactCloserTest extends TestCase
{
    use GlobalLanguageMock;

    private const string CLOSER_USERNAME      = '@asticotc';
    private const int    STATUS_FIELD_ID      = 18;
    private const int    DONE_BIND_VALUE_ID   = 1234;
    private const int    CLOSED_BIND_VALUE_ID = 3174;
    private const string DONE_LABEL           = 'Done';
    private const string ORIGIN_REFERENCE     = 'git #heelmaker/54022373';

    private MockObject&StatusValueRetriever $status_value_retriever;
    private MockObject&DoneValueRetriever $done_value_retriever;
    private Stub&Artifact $artifact;
    private Tracker_Workflow_WorkflowUser $workflow_user;
    private string $success_message;
    private string $no_semantic_defined_message;
    private CreateCommentOnlyChangesetStub $comment_creator;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveSemanticStatusFieldStub $status_retriever;
    private Tracker_FormElement_Field_List_Bind_StaticValue $done_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $closed_value;
    private bool $changeset_creator_was_called;

    #[\Override]
    protected function setUp(): void
    {
        $this->done_value   = ListStaticValueBuilder::aStaticValue(self::DONE_LABEL)
            ->withId(self::DONE_BIND_VALUE_ID)
            ->build();
        $this->closed_value = ListStaticValueBuilder::aStaticValue('Closed')
            ->withId(self::CLOSED_BIND_VALUE_ID)
            ->build();

        $tracker      = TrackerTestBuilder::aTracker()->build();
        $status_field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(self::STATUS_FIELD_ID)->inTracker($tracker)->build(),
        )->withBuildStaticValues([$this->closed_value, $this->done_value])
            ->build()
            ->getField();

        $this->status_retriever       = RetrieveSemanticStatusFieldStub::build()->withField($status_field);
        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);
        $this->done_value_retriever   = $this->createMock(DoneValueRetriever::class);

        $this->comment_creator = CreateCommentOnlyChangesetStub::withChangeset(
            ChangesetTestBuilder::aChangeset(5438)->build()
        );

        $this->changeset_creator_was_called = false;
        $this->changeset_creator            = CreateNewChangesetStub::withCallback(function (NewChangeset $new_changeset, PostCreationContext $context) {
            $this->changeset_creator_was_called = true;
            return null;
        });

        $this->workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                'user_id'     => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $this->artifact = $this->createStub(Artifact::class);
        $this->artifact->method('getId')->willReturn(25);
        $this->artifact->method('getTracker')->willReturn($tracker);

        $this->success_message             = sprintf(
            'Solved by %s with %s.',
            self::CLOSER_USERNAME,
            self::ORIGIN_REFERENCE
        );
        $this->no_semantic_defined_message = sprintf(
            '%s attempts to close this artifact but neither done nor status semantic defined.',
            self::CLOSER_USERNAME
        );
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    private function closeArtifact(): Ok|Err
    {
        $no_semantic_comment = BadSemanticCommentInCommonMarkFormatStub::fromString($this->no_semantic_defined_message);
        $closing_comment     = ArtifactClosingCommentInCommonMarkFormat::fromParts(
            self::CLOSER_USERNAME,
            ClosingKeyword::buildResolves(),
            TrackerTestBuilder::aTracker()->build(),
            ReferenceStringStub::fromString(self::ORIGIN_REFERENCE)
        );

        $updater = new ArtifactCloser(
            $this->status_retriever,
            $this->status_value_retriever,
            $this->done_value_retriever,
            new NullLogger(),
            $this->comment_creator,
            $this->changeset_creator,
        );
        return $updater->closeArtifact(
            $this->artifact,
            $this->workflow_user,
            $closing_comment,
            $no_semantic_comment,
        );
    }

    public function testItClosesArtifactWithDoneValue(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockDoneValueIsFound();

        $changeset_created       = false;
        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            function (NewChangeset $new_changeset) use (&$changeset_created) {
                $changeset_created = true;
                self::assertSame($this->artifact, $new_changeset->getArtifact());
                self::assertSame($this->workflow_user, $new_changeset->getSubmitter());
                self::assertSame($this->success_message, $new_changeset->getComment()->getBody());
                self::assertEqualsCanonicalizing(
                    [self::STATUS_FIELD_ID => self::DONE_BIND_VALUE_ID],
                    $new_changeset->getFieldsData()
                );
                return ChangesetTestBuilder::aChangeset(2452)->build();
            }
        );

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($changeset_created);
    }

    public function testItClosesArtifactWithFirstClosedStatusValue(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockNoDoneValue();
        $this->mockClosedValueIsFound();

        $changeset_created       = false;
        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            function (NewChangeset $new_changeset) use (&$changeset_created) {
                $changeset_created = true;
                self::assertSame($this->artifact, $new_changeset->getArtifact());
                self::assertSame($this->workflow_user, $new_changeset->getSubmitter());
                self::assertSame($this->success_message, $new_changeset->getComment()->getBody());
                self::assertEqualsCanonicalizing(
                    [self::STATUS_FIELD_ID => self::CLOSED_BIND_VALUE_ID],
                    $new_changeset->getFieldsData()
                );
                return ChangesetTestBuilder::aChangeset(2452)->build();
            }
        );

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($changeset_created);
    }

    public function testItReturnsErrIfArtifactIsAlreadyClosed(): void
    {
        $this->artifact->method('isOpen')->willReturn(false);

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactIsAlreadyClosedFault::class, $result->error);
        self::assertFalse($this->changeset_creator_was_called);
    }

    public function testItReturnsErrIfNoPossibleValueAreFound(): void
    {
        $this->mockArtifactIsOpen();

        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')
            ->with($this->artifact, $this->workflow_user)
            ->willThrowException(new NoPossibleValueException());

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertFalse($this->changeset_creator_was_called);
    }

    public function testItReturnsErrIfChangesetIsNotCreated(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockDoneValueIsFound();

        $was_called              = false;
        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            static function (NewChangeset $new_changeset, PostCreationContext $context) use (&$was_called) {
                $was_called = true;
                return null;
            }
        );

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertTrue($was_called);
    }

    public function testItReturnsErrIfAnErrorOccursDuringTheChangesetCreation(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockDoneValueIsFound();

        $was_called              = false;
        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            static function () use (&$was_called) {
                $was_called = true;
                throw new Tracker_NoChangeException(1, 'xref');
            }
        );

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertTrue($was_called);
    }

    public function testItAddsOnlyACommentIfStatusSemanticIsNotDefined(): void
    {
        $this->mockArtifactIsOpen();
        $this->status_retriever = RetrieveSemanticStatusFieldStub::build();

        $comment_was_created   = false;
        $this->comment_creator = CreateCommentOnlyChangesetStub::withCallback(function (NewComment $new_comment, Artifact $artifact) use (&$comment_was_created) {
            $comment_was_created = true;
            self::assertSame($this->no_semantic_defined_message, $new_comment->getBody());
            self::assertSame($this->workflow_user, $new_comment->getSubmitter());
            self::assertSame($this->artifact, $artifact);
            return Result::ok(ChangesetTestBuilder::aChangeset(5438)->build());
        });

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($comment_was_created);
    }

    public function testItAddsOnlyACommentIfClosedValueNotFound(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockNoDoneValue();
        $this->status_value_retriever->expects($this->once())
            ->method('getFirstClosedValueUserCanRead')
            ->with($this->workflow_user, $this->artifact)
            ->willThrowException(new SemanticStatusClosedValueNotFoundException());

        $comment_was_created   = false;
        $this->comment_creator = CreateCommentOnlyChangesetStub::withCallback(function (NewComment $new_comment, Artifact $artifact) use (&$comment_was_created) {
            $comment_was_created = true;
            self::assertSame($this->no_semantic_defined_message, $new_comment->getBody());
            self::assertSame($this->workflow_user, $new_comment->getSubmitter());
            self::assertSame($this->artifact, $artifact);
            return Result::ok(ChangesetTestBuilder::aChangeset(5438)->build());
        });

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($comment_was_created);
    }

    public function testItReturnsErrIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $this->mockArtifactIsOpen();
        $this->status_retriever = RetrieveSemanticStatusFieldStub::build();

        $was_called            = false;
        $this->comment_creator = CreateCommentOnlyChangesetStub::withCallback(
            static function (NewComment $new_comment, Artifact $artifact) use (&$was_called) {
                $was_called = true;
                return Result::err(Fault::fromMessage('Error during comment creation'));
            }
        );

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertTrue($was_called);
    }

    private function mockArtifactIsOpen(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);
    }

    private function mockDoneValueIsFound(): void
    {
        $this->done_value_retriever->expects($this->once())
            ->method('getFirstDoneValueUserCanRead')
            ->with($this->artifact, $this->workflow_user)
            ->willReturn($this->done_value);
    }

    private function mockNoDoneValue(): void
    {
        $this->done_value_retriever->expects($this->once())
            ->method('getFirstDoneValueUserCanRead')
            ->with($this->artifact, $this->workflow_user)
            ->willThrowException(new SemanticDoneValueNotFoundException());
    }

    private function mockClosedValueIsFound(): void
    {
        $this->status_value_retriever->expects($this->once())
            ->method('getFirstClosedValueUserCanRead')
            ->with($this->workflow_user, $this->artifact)
            ->willReturn($this->closed_value);
    }
}

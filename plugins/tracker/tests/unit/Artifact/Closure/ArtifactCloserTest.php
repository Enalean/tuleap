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

use Psr\Log\NullLogger;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_Workflow_WorkflowUser;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BadSemanticCommentInCommonMarkFormatStub;
use Tuleap\Test\Stubs\ReferenceStringStub;
use Tuleap\Tracker\Test\Stub\CreateCommentOnlyChangesetStub;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveStatusFieldStub;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactCloserTest extends TestCase
{
    private const CLOSER_USERNAME      = '@asticotc';
    private const STATUS_FIELD_ID      = 18;
    private const DONE_BIND_VALUE_ID   = 1234;
    private const CLOSED_BIND_VALUE_ID = 3174;
    private const DONE_LABEL           = 'Done';
    private const ORIGIN_REFERENCE     = 'git #heelmaker/54022373';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&StatusValueRetriever
     */
    private $status_value_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DoneValueRetriever
     */
    private $done_value_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;
    private Tracker_Workflow_WorkflowUser $workflow_user;
    private string $success_message;
    private string $no_semantic_defined_message;
    private CreateCommentOnlyChangesetStub $comment_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_FormElement_Field_List
     */
    private $status_field;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveStatusFieldStub $status_retriever;

    protected function setUp(): void
    {
        $this->status_field = $this->createStub(\Tracker_FormElement_Field_List::class);
        $this->status_field->method('getId')->willReturn(self::STATUS_FIELD_ID);

        $this->status_retriever       = RetrieveStatusFieldStub::withField($this->status_field);
        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);
        $this->done_value_retriever   = $this->createMock(DoneValueRetriever::class);
        $this->comment_creator        = CreateCommentOnlyChangesetStub::withChangeset(
            ChangesetTestBuilder::aChangeset(5438)->build()
        );
        $this->changeset_creator      = CreateNewChangesetStub::withReturnChangeset(
            ChangesetTestBuilder::aChangeset(2452)->build()
        );

        $this->workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                'user_id'     => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $this->artifact = $this->createStub(Artifact::class);
        $this->artifact->method('getId')->willReturn(25);
        $this->artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());

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

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        $new_changeset = $this->changeset_creator->getNewChangeset();
        if (! $new_changeset) {
            throw new \Exception('Expected to receive a new changeset');
        }
        self::assertSame($this->artifact, $new_changeset->getArtifact());
        self::assertSame($this->workflow_user, $new_changeset->getSubmitter());
        self::assertSame($this->success_message, $new_changeset->getComment()->getBody());
        self::assertEqualsCanonicalizing(
            [self::STATUS_FIELD_ID => self::DONE_BIND_VALUE_ID],
            $new_changeset->getFieldsData()
        );
    }

    public function testItClosesArtifactWithFirstClosedStatusValue(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockNoDoneValue();
        $this->mockClosedValueIsFound();

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        $new_changeset = $this->changeset_creator->getNewChangeset();
        if (! $new_changeset) {
            throw new \Exception('Expected to receive a new changeset');
        }
        self::assertSame($this->artifact, $new_changeset->getArtifact());
        self::assertSame($this->workflow_user, $new_changeset->getSubmitter());
        self::assertSame($this->success_message, $new_changeset->getComment()->getBody());
        self::assertEqualsCanonicalizing(
            [self::STATUS_FIELD_ID => self::CLOSED_BIND_VALUE_ID],
            $new_changeset->getFieldsData()
        );
    }

    public function testItReturnsErrIfArtifactIsAlreadyClosed(): void
    {
        $this->artifact->method('isOpen')->willReturn(false);

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactIsAlreadyClosedFault::class, $result->error);
        self::assertNull($this->changeset_creator->getNewChangeset());
    }

    public function testItReturnsErrIfNoPossibleValueAreFound(): void
    {
        $this->mockArtifactIsOpen();

        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')
            ->with($this->artifact, $this->workflow_user)
            ->willThrowException(new NoPossibleValueException());

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertNull($this->changeset_creator->getNewChangeset());
    }

    public function testItReturnsErrIfChangesetIsNotCreated(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockDoneValueIsFound();
        $this->changeset_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertNotNull($this->changeset_creator->getNewChangeset());
    }

    public function testItReturnsErrIfAnErrorOccursDuringTheChangesetCreation(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockDoneValueIsFound();
        $this->changeset_creator = CreateNewChangesetStub::withException(new \Tracker_NoChangeException(1, 'xref'));

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertNotNull($this->changeset_creator->getNewChangeset());
    }

    public function testItAddsOnlyACommentIfStatusSemanticIsNotDefined(): void
    {
        $this->mockArtifactIsOpen();
        $this->status_retriever = RetrieveStatusFieldStub::withNoField();

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        $new_comment = $this->comment_creator->getNewComment();
        if (! $new_comment) {
            throw new \Exception('Expected to receive a new comment');
        }
        self::assertSame($this->no_semantic_defined_message, $new_comment->getBody());
        self::assertSame($this->workflow_user, $new_comment->getSubmitter());
        self::assertSame($this->artifact, $this->comment_creator->getArtifact());
    }

    public function testItAddsOnlyACommentIfClosedValueNotFound(): void
    {
        $this->mockArtifactIsOpen();
        $this->mockNoDoneValue();
        $this->status_value_retriever->expects($this->once())
            ->method('getFirstClosedValueUserCanRead')
            ->with($this->workflow_user, $this->artifact)
            ->willThrowException(new SemanticStatusClosedValueNotFoundException());

        $result = $this->closeArtifact();

        self::assertTrue(Result::isOk($result));
        $new_comment = $this->comment_creator->getNewComment();
        if (! $new_comment) {
            throw new \Exception('Expected to receive a new comment');
        }
        self::assertSame($this->no_semantic_defined_message, $new_comment->getBody());
        self::assertSame($this->workflow_user, $new_comment->getSubmitter());
        self::assertSame($this->artifact, $this->comment_creator->getArtifact());
    }

    public function testItReturnsErrIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $this->mockArtifactIsOpen();
        $this->status_retriever = RetrieveStatusFieldStub::withNoField();
        $this->comment_creator  = CreateCommentOnlyChangesetStub::withFault(
            Fault::fromMessage('Error during comment creation')
        );

        $result = $this->closeArtifact();

        self::assertTrue(Result::isErr($result));
        self::assertNotNull($this->comment_creator->getNewComment());
    }

    private function mockArtifactIsOpen(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);
    }

    private function mockDoneValueIsFound(): void
    {
        $this->status_field->method('getFieldData')->willReturn(self::DONE_BIND_VALUE_ID);

        $this->done_value_retriever->expects($this->once())
            ->method('getFirstDoneValueUserCanRead')
            ->with($this->artifact, $this->workflow_user)
            ->willReturn($this->getDoneValue());
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
        $this->status_field->method('getFieldData')->willReturn(self::CLOSED_BIND_VALUE_ID);

        $this->status_value_retriever->expects($this->once())
            ->method('getFirstClosedValueUserCanRead')
            ->with($this->workflow_user, $this->artifact)
            ->willReturn($this->getDoneValue());
    }

    private function getDoneValue(): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return ListStaticValueBuilder::aStaticValue(self::DONE_LABEL)->withId(14)->build();
    }
}

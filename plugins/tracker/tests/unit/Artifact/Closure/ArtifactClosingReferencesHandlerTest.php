<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Closure;

use PHPUnit\Framework\MockObject\Stub;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\ReferenceInstance;
use Tuleap\Reference\TextWithPotentialReferences;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ExtractReferencesStub;
use Tuleap\Test\Stubs\ReferenceStringStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateCommentOnlyChangesetStub;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveStatusFieldStub;
use Tuleap\User\UserName;

final class ArtifactClosingReferencesHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const FIRST_ARTIFACT_ID  = 111;
    private const SECOND_ARTIFACT_ID = 576;

    private TestLogger $logger;
    private ExtractReferencesStub $reference_extractor;
    private \Project $project;
    private RetrieveArtifactStub $artifact_retriever;
    private RetrieveUserByIdStub $user_retriever;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveStatusFieldStub $status_retriever;
    /**
     * @var DoneValueRetriever&Stub
     */
    private $done_value_retriever;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(151)->build();

        $this->logger              = new TestLogger();
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->project)],
            [$this->getArtifactReferenceInstance('implements', 'story', self::SECOND_ARTIFACT_ID, $this->project)]
        );

        $this->artifact_retriever   = RetrieveArtifactStub::withArtifacts(
            $this->mockArtifact('bug', self::FIRST_ARTIFACT_ID),
            $this->mockArtifact('story', self::SECOND_ARTIFACT_ID),
        );
        $this->user_retriever       = RetrieveUserByIdStub::withUser(
            new \Tracker_Workflow_WorkflowUser([
                'user_id'     => \Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ])
        );
        $this->changeset_creator    = CreateNewChangesetStub::withReturnChangeset(
            ChangesetTestBuilder::aChangeset('9667')->build()
        );
        $this->status_retriever     = RetrieveStatusFieldStub::withNoField();
        $this->done_value_retriever = $this->createStub(DoneValueRetriever::class);
    }

    public function handlePotentialReferencesReceived(): void
    {
        $status_value_retriever = $this->createStub(StatusValueRetriever::class);

        $handler = new ArtifactClosingReferencesHandler(
            $this->logger,
            $this->reference_extractor,
            $this->artifact_retriever,
            $this->user_retriever,
            new ArtifactWasClosedCache(),
            new ArtifactCloser(
                $this->status_retriever,
                $status_value_retriever,
                $this->done_value_retriever,
                $this->logger,
                CreateCommentOnlyChangesetStub::withChangeset(ChangesetTestBuilder::aChangeset('4706')->build()),
                $this->changeset_creator
            )
        );
        $user    = UserTestBuilder::aUser()->withUserName('meisinger')->build();
        $handler->handlePotentialReferencesReceived(
            new PotentialReferencesReceived(
                [
                    new TextWithPotentialReferences(
                        sprintf('closes art #%d', self::FIRST_ARTIFACT_ID),
                        ReferenceStringStub::fromString('git #linkable/b9ead7cb'),
                        UserName::fromUser($user)
                    ),
                    new TextWithPotentialReferences(
                        sprintf('implements art #%d', self::SECOND_ARTIFACT_ID),
                        ReferenceStringStub::fromString('git #linkable/e43c62bb'),
                        UserName::fromUser($user)
                    ),
                ],
                $this->project,
            )
        );
    }

    public function testItClosesReferencedArtifacts(): void
    {
        $this->mockDoneValuesAreFound();

        $this->handlePotentialReferencesReceived();

        self::assertTrue($this->logger->hasDebugThatContains('Closed artifact #' . self::FIRST_ARTIFACT_ID));
        self::assertTrue($this->logger->hasDebugThatContains('Closed artifact #' . self::SECOND_ARTIFACT_ID));
    }

    public function testItThrowsIfWorkflowUserIsNotFound(): void
    {
        $this->user_retriever = RetrieveUserByIdStub::withNoUser();

        $this->expectException(\UserNotExistException::class);
        $this->handlePotentialReferencesReceived();
    }

    public function testItDoesNothingWhenNoReferenceIsFound(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withNoReference();

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsNonArtifactReferences(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getNonArtifactReferenceInstance('doc', 309)],
            [$this->getNonArtifactReferenceInstance('custom', 95)]
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsReferencesWhoseContextKeywordIsNotAClosingKeyword(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('not_closing', 'art', self::FIRST_ARTIFACT_ID, $this->project)],
            [$this->getArtifactReferenceInstance('not_closing', 'story', self::SECOND_ARTIFACT_ID, $this->project)]
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsReferencesToArtifactReferencesFromADifferentProjectThanTheEvent(): void
    {
        $other_project             = ProjectTestBuilder::aProject()->withId(113)->build();
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $other_project)],
            [$this->getArtifactReferenceInstance('implements', 'story', self::SECOND_ARTIFACT_ID, $other_project)]
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsArtifactsUserCannotSee(): void
    {
        $this->artifact_retriever = RetrieveArtifactStub::withNoArtifact();

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsArtifactsWithoutArtifactReferenceInATrackerFromADifferentProjectThanTheEvent(): void
    {
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts(
            $this->mockArtifactInAnotherProject('bug', self::FIRST_ARTIFACT_ID),
        );

        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->project)],
            [$this->getArtifactReferenceInstance('fix', 'art', self::SECOND_ARTIFACT_ID, $this->project)],
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsArtifactsInDeletedTrackers(): void
    {
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts(
            $this->mockArtifactInDeletedTracker('bug', self::FIRST_ARTIFACT_ID),
        );

        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->project)],
            [$this->getArtifactReferenceInstance('fix', 'art', self::SECOND_ARTIFACT_ID, $this->project)],
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItLogsErrorsAtArtifactClosure(): void
    {
        $this->mockDoneValuesAreFound();
        $this->changeset_creator = CreateNewChangesetStub::withException(new \Tracker_ChangesetNotCreatedException());

        $this->handlePotentialReferencesReceived();
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItSkipsArtifactsThatItHasAlreadyClosedBefore(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('close', 'art', self::FIRST_ARTIFACT_ID, $this->project)],
            [$this->getArtifactReferenceInstance('fix', 'art', self::FIRST_ARTIFACT_ID, $this->project)],
        );

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts(
            $this->mockArtifact('bug', self::FIRST_ARTIFACT_ID),
            $this->mockArtifact('bug', self::FIRST_ARTIFACT_ID),
        );

        $this->mockDoneValuesAreFound();

        $this->handlePotentialReferencesReceived();

        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItSkipsAfter50ReferencesAtOnceToLimitResourceUsage(): void
    {
        $reference_instances = [];
        $artifacts           = [];
        for ($i = 1; $i <= 51; $i++) {
            $reference_instances[] = $this->getArtifactReferenceInstance('close', 'art', $i, $this->project);
            $artifacts[]           = $this->mockArtifact('story', $i);
        }
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances($reference_instances, []);
        $this->artifact_retriever  = RetrieveArtifactStub::withArtifacts(...$artifacts);

        $done_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(7682, 'Closed', 'Irrelevant', 1, false);

        $status_field           = $this->getStatusField(718, $done_value);
        $this->status_retriever = RetrieveStatusFieldStub::withField($status_field);
        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')->willReturn($done_value);

        $this->handlePotentialReferencesReceived();

        self::assertSame(50, $this->changeset_creator->getCallsCount());
        self::assertTrue(
            $this->logger->hasInfoThatContains('Found more than 50 references, the rest will be skipped.')
        );
    }

    private function getArtifactReferenceInstance(
        string $context_word,
        string $keyword,
        int $artifact_id,
        \Project $project,
    ): ReferenceInstance {
        $tracker   = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $reference = new \Tracker_Reference($tracker, $keyword);
        return new ReferenceInstance(
            sprintf('%1$s %2$s#%3$d', $context_word, $keyword, $artifact_id),
            $reference,
            (string) self::FIRST_ARTIFACT_ID,
            $keyword,
            (int) $project->getID(),
            $context_word,
        );
    }

    private function getNonArtifactReferenceInstance(string $keyword, int $id): ReferenceInstance
    {
        $reference = new \Reference(
            95,
            $keyword,
            'Not an Artifact Reference',
            'irrelevant',
            'P',
            'irrelevant',
            'plugin_other_document',
            true,
            (int) $this->project->getID()
        );
        return new ReferenceInstance(
            $keyword . '#' . $id,
            $reference,
            (string) $id,
            $keyword,
            (int) $this->project->getID(),
            ''
        );
    }

    /**
     * @return \Tracker_FormElement_Field_Selectbox & \PHPUnit\Framework\MockObject\Stub
     */
    private function getStatusField(int $field_id, \Tracker_FormElement_Field_List_Bind_StaticValue $bind_value)
    {
        $field = $this->createStub(\Tracker_FormElement_Field_Selectbox::class);
        $field->method('getId')->willReturn($field_id);
        $field->method('getFieldData')->willReturn([$bind_value->getId()]);
        return $field;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub & Artifact
     */
    private function mockArtifact(string $tracker_shortname, int $artifact_id)
    {
        $tracker  = TrackerTestBuilder::aTracker()->withShortName($tracker_shortname)->withProject($this->project)->build();
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('isOpen')->willReturn(true);
        return $artifact;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub & Artifact
     */
    private function mockArtifactInDeletedTracker(string $tracker_shortname, int $artifact_id)
    {
        $tracker  = TrackerTestBuilder::aTracker()
            ->withShortName($tracker_shortname)
            ->withProject($this->project)
            ->withDeletionDate((new \DateTimeImmutable())->getTimestamp())
            ->build();
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('isOpen')->willReturn(true);
        return $artifact;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub & Artifact
     */
    private function mockArtifactInAnotherProject(string $tracker_shortname, int $artifact_id)
    {
        $tracker  = TrackerTestBuilder::aTracker()
            ->withShortName($tracker_shortname)
            ->withProject(ProjectTestBuilder::aProject()->withId(999)->build())
            ->build();
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('isOpen')->willReturn(true);
        return $artifact;
    }

    private function mockDoneValuesAreFound(): void
    {
        $first_done_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(402, 'Closed', 'Irrelevant', 1, false);

        $second_done_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(940, 'Done', 'Irrelevant', 1, false);

        $this->status_retriever = RetrieveStatusFieldStub::withSuccessiveFields(
            $this->getStatusField(564, $first_done_value),
            $this->getStatusField(618, $second_done_value),
        );
        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')->willReturnOnConsecutiveCalls(
            $first_done_value,
            $second_done_value
        );
    }
}

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

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\Stub;
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
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateCommentOnlyChangesetStub;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Tracker;
use Tuleap\User\UserName;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactClosingReferencesHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const int FIRST_ARTIFACT_ID  = 111;
    private const int SECOND_ARTIFACT_ID = 576;

    private TestLogger $logger;
    private ExtractReferencesStub $reference_extractor;
    private \Project $project;
    private RetrieveArtifactStub $artifact_retriever;
    private RetrieveUserByIdStub $user_retriever;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveSemanticStatusFieldStub $status_retriever;
    private DoneValueRetriever&Stub $done_value_retriever;
    private Tracker $bug_tracker;
    private Tracker $story_tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->project       = ProjectTestBuilder::aProject()->withId(151)->build();
        $this->bug_tracker   = TrackerTestBuilder::aTracker()->withShortName('bug')->withProject($this->project)->build();
        $this->story_tracker = TrackerTestBuilder::aTracker()->withShortName('story')->withProject($this->project)->build();

        $this->logger              = new TestLogger();
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->bug_tracker)],
            [$this->getArtifactReferenceInstance('implements', 'story', self::SECOND_ARTIFACT_ID, $this->story_tracker)]
        );

        $this->artifact_retriever   = RetrieveArtifactStub::withArtifacts(
            $this->mockArtifact(self::FIRST_ARTIFACT_ID),
            $this->mockArtifact(self::SECOND_ARTIFACT_ID),
        );
        $this->user_retriever       = RetrieveUserByIdStub::withUser(
            new \Tracker_Workflow_WorkflowUser([
                'user_id'     => \Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ])
        );
        $this->changeset_creator    = CreateNewChangesetStub::withReturnChangeset(
            ChangesetTestBuilder::aChangeset(9667)->build()
        );
        $this->status_retriever     = RetrieveSemanticStatusFieldStub::build();
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
                CreateCommentOnlyChangesetStub::withChangeset(ChangesetTestBuilder::aChangeset(4706)->build()),
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
                        sprintf('feat: art #%d', self::SECOND_ARTIFACT_ID),
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
        $this->setupDoneValuesAreFound();

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
            [$this->getArtifactReferenceInstance('not_closing', 'art', self::FIRST_ARTIFACT_ID, $this->bug_tracker)],
            [$this->getArtifactReferenceInstance('not_closing', 'story', self::SECOND_ARTIFACT_ID, $this->story_tracker)]
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsReferencesToArtifactReferencesFromADifferentProjectThanTheEvent(): void
    {
        $other_project          = ProjectTestBuilder::aProject()->withId(113)->build();
        $bug_in_other_project   = TrackerTestBuilder::aTracker()->withShortName('bug')->withProject($other_project)->build();
        $story_in_other_project = TrackerTestBuilder::aTracker()->withShortName('story')->withProject($other_project)->build();

        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $bug_in_other_project)],
            [$this->getArtifactReferenceInstance('implements', 'story', self::SECOND_ARTIFACT_ID, $story_in_other_project)]
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
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->bug_tracker)],
            [$this->getArtifactReferenceInstance('fix', 'art', self::SECOND_ARTIFACT_ID, $this->story_tracker)],
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
            [$this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->bug_tracker)],
            [$this->getArtifactReferenceInstance('fix', 'art', self::SECOND_ARTIFACT_ID, $this->story_tracker)],
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItLogsErrorsAtArtifactClosure(): void
    {
        $this->setupDoneValuesAreFound();
        $this->changeset_creator = CreateNewChangesetStub::withException(new \Tracker_ChangesetNotCreatedException());

        $this->handlePotentialReferencesReceived();
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItSkipsArtifactsThatItHasAlreadyClosedBefore(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances(
            [$this->getArtifactReferenceInstance('close', 'art', self::FIRST_ARTIFACT_ID, $this->bug_tracker)],
            [$this->getArtifactReferenceInstance('fix', 'art', self::FIRST_ARTIFACT_ID, $this->story_tracker)],
        );

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts(
            $this->mockArtifact(self::FIRST_ARTIFACT_ID),
            $this->mockArtifact(self::FIRST_ARTIFACT_ID),
        );

        $this->setupDoneValuesAreFound();

        $this->handlePotentialReferencesReceived();

        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItSkipsAfter50ReferencesAtOnceToLimitResourceUsage(): void
    {
        $reference_instances = [];
        $artifacts           = [];
        for ($i = 1; $i <= 51; $i++) {
            $reference_instances[] = $this->getArtifactReferenceInstance('close', 'art', $i, $this->bug_tracker);
            $artifacts[]           = $this->mockArtifact($i);
        }
        $this->reference_extractor = ExtractReferencesStub::withSuccessiveReferenceInstances($reference_instances, []);
        $this->artifact_retriever  = RetrieveArtifactStub::withArtifacts(...$artifacts);

        $done_value = ListStaticValueBuilder::aStaticValue('Closed')->build();

        $status_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(718)->inTracker($this->bug_tracker)->build()
        )->withBuildStaticValues([$done_value])
            ->build()
            ->getField();
        $this->status_retriever->withField($status_field);
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
        Tracker $tracker,
    ): ReferenceInstance {
        $reference = new \Tracker_Reference($tracker, $keyword);
        return new ReferenceInstance(
            sprintf('%1$s %2$s#%3$d', $context_word, $keyword, $artifact_id),
            $reference,
            (string) self::FIRST_ARTIFACT_ID,
            $keyword,
            (int) $tracker->getGroupId(),
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

    private function mockArtifact(int $artifact_id): Stub&Artifact
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($this->bug_tracker);
        $artifact->method('isOpen')->willReturn(true);
        return $artifact;
    }

    private function mockArtifactInDeletedTracker(string $tracker_shortname, int $artifact_id): Stub&Artifact
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

    private function mockArtifactInAnotherProject(string $tracker_shortname, int $artifact_id): Stub&Artifact
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

    private function setupDoneValuesAreFound(): void
    {
        $first_done_value  = ListStaticValueBuilder::aStaticValue('Closed')->build();
        $second_done_value = ListStaticValueBuilder::aStaticValue('Done')->build();

        $first_status_field  = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(564)->inTracker($this->bug_tracker)->build(),
        )->withBuildStaticValues([$first_done_value])
            ->build()
            ->getField();
        $second_status_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(618)->inTracker($this->story_tracker)->build(),
        )->withBuildStaticValues([$second_done_value])
            ->build()
            ->getField();

        $this->status_retriever->withField($first_status_field);
        $this->status_retriever->withField($second_status_field);
        $this->done_value_retriever->method('getFirstDoneValueUserCanRead')->willReturnOnConsecutiveCalls(
            $first_done_value,
            $second_done_value
        );
    }
}

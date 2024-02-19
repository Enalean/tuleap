<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\Link\ArtifactReverseLinksUpdater;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CheckArtifactRestUpdateConditionsStub;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveReverseLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;

final class PUTHandlerTest extends TestCase
{
    use GlobalResponseMock;

    private const ARTIFACT_LINK_FIELD_ID = 179;
    private CheckArtifactRestUpdateConditionsStub $check_artifact_rest_update_conditions;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveUsedArtifactLinkFieldsStub $link_field_retriever;
    private RetrieveViewableArtifactStub $artifact_retriever;
    private array $payload;

    protected function setUp(): void
    {
        $this->artifact_retriever                    = RetrieveViewableArtifactStub::withNoArtifact();
        $this->link_field_retriever                  = RetrieveUsedArtifactLinkFieldsStub::withNoField();
        $this->check_artifact_rest_update_conditions = CheckArtifactRestUpdateConditionsStub::allowArtifactUpdate();
        $this->changeset_creator                     = CreateNewChangesetStub::withReturnChangeset(
            ChangesetTestBuilder::aChangeset('289')->build()
        );

        $this->payload = [];
    }

    /**
     * @throws RestException
     */
    private function handle(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withId(26)->build();
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $all_fields_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)
                ->inTracker($tracker)
                ->build()
        );

        $put_handler = new PUTHandler(
            new FieldsDataBuilder(
                $all_fields_retriever,
                new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withoutLinks()),
                new NewArtifactLinkInitialChangesetValueBuilder()
            ),
            new ArtifactReverseLinksUpdater(
                RetrieveReverseLinksStub::withoutLinks(),
                new ReverseLinksToNewChangesetsConverter(
                    $this->link_field_retriever,
                    $this->artifact_retriever
                ),
                $this->changeset_creator,
            ),
            $this->check_artifact_rest_update_conditions,
        );
        $put_handler->handle($this->payload, $artifact, $user, null);
    }

    public static function provideExceptions(): iterable
    {
        yield 'Field is invalid' => [new \Tracker_FormElement_InvalidFieldException(), 400];
        yield 'Field value is invalid' => [new \Tracker_FormElement_InvalidFieldValueException(), 400];
        yield 'Artifact links cannot be removed' => [new FieldValidationException([]), 400];
        yield 'Tracker exception' => [new \Tracker_Exception(), 500];
        $other_artifact = ArtifactTestBuilder::anArtifact(83)->build();
        yield 'Attachment is already linked' => [
            new \Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException(12, $other_artifact),
            500,
        ];
        yield 'Attachment is not found' => [new \Tracker_Artifact_Attachment_FileNotFoundException(), 404];
    }

    /**
     * @dataProvider provideExceptions
     */
    public function testItMapsExceptionsToRestExceptions(\Throwable $throwable, int $expected_status_code): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withException($throwable);

        $this->expectException(RestException::class);
        $this->expectExceptionCode($expected_status_code);
        $this->handle();
    }

    public function testItIgnoresNoChange(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withException(new \Tracker_NoChangeException(1, 'art #1'));

        $this->handle();
        $this->expectNotToPerformAssertions();
    }

    public function testItThrows500WhenThereIsAnErrorFeedback(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withException(new \Tracker_Exception());
        $GLOBALS['Response']->method('feedbackHasErrors')->willReturn(true);
        $GLOBALS['Response']->method('getRawFeedback')->willReturn('Aaaah');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);
        $this->handle();
    }

    public function testItUpdatesArtifactLikeBeforeWhenForwardDirectionIsProvidedInAllLinkKey(): void
    {
        $this->payload = [
            ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withAllLinks(LinkWithDirectionRepresentationBuilder::aForwardLink(64)->build())
                ->build(),
        ];

        $this->handle();
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItDoesNotMakesTheReverseOfAnArtifactIfTheParentKeyWasGiven(): void
    {
        $this->payload = [
            ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withParent(12)
                ->build(),
        ];

        $this->handle();
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItDoesNotMakesTheReverseOfAnArtifactIfTheLinksKeyIsGiven(): void
    {
        $this->payload = [
            ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withLinks(['id' => 12, 'type' => ''])
                ->build(),
        ];

        $this->handle();
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItLinksTheArtifactWithForwardAndReverseLink(): void
    {
        $reverse_artifact_id        = 34;
        $this->artifact_retriever   = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact($reverse_artifact_id)->build(),
        );
        $this->link_field_retriever = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(234)->build(),
        );

        $this->payload = [
            ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withAllLinks(
                    LinkWithDirectionRepresentationBuilder::aReverseLink($reverse_artifact_id)->build(),
                    LinkWithDirectionRepresentationBuilder::aForwardLink(15)->build()
                )
                ->build(),
        ];

        $this->handle();
        self::assertSame(2, $this->changeset_creator->getCallsCount());
    }

    public function testItThrowsARestExceptionWhenTheArtifactCannotBeUpdated(): void
    {
        $this->check_artifact_rest_update_conditions = CheckArtifactRestUpdateConditionsStub::disallowArtifactUpdate();

        $this->expectException(RestException::class);
        $this->handle();
    }
}

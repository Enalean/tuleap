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
use Tracker_NoChangeException;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\CheckArtifactRestUpdateConditionsStub;
use Tuleap\Tracker\Test\Stub\HandleUpdateArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveReverseLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class PUTHandlerTest extends TestCase
{
    use GlobalResponseMock;

    private HandleUpdateArtifactStub $artifact_updater;
    private RetrieveUsedFieldsStub $field_retriever;
    private CheckArtifactRestUpdateConditionsStub $check_artifact_rest_update_conditions;

    protected function setUp(): void
    {
        $this->artifact_updater                      = HandleUpdateArtifactStub::build();
        $this->field_retriever                       = RetrieveUsedFieldsStub::withNoFields();
        $this->check_artifact_rest_update_conditions = CheckArtifactRestUpdateConditionsStub::allowArtifactUpdate();
    }

    /**
     * @throws RestException
     */
    private function handle(array $values): void
    {
        $artifact    = ArtifactTestBuilder::anArtifact(1)->build();
        $user        = UserTestBuilder::buildWithDefaults();
        $put_handler = new PUTHandler(
            new FieldsDataBuilder(
                $this->field_retriever,
                new NewArtifactLinkChangesetValueBuilder(
                    RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([]))
                ),
                new NewArtifactLinkInitialChangesetValueBuilder()
            ),
            RetrieveReverseLinksStub::withLinks(new CollectionOfReverseLinks([])),
            $this->artifact_updater,
            new DBTransactionExecutorPassthrough(),
            $this->check_artifact_rest_update_conditions,
        );
        $put_handler->handle($values, $artifact, $user, null);
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
        $this->artifact_updater = HandleUpdateArtifactStub::withException($throwable);
        $this->expectException(RestException::class);
        $this->expectExceptionCode($expected_status_code);
        $this->handle([]);
        self::assertSame(0, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(0, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
    }

    public function testItDoesNothingWhenNoChange(): void
    {
        $this->artifact_updater = HandleUpdateArtifactStub::withException(new Tracker_NoChangeException(1, 'art #1'));
        $this->handle([]);
        self::assertSame(0, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(0, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
    }

    public function testItThrows500WhenThereIsAnErrorFeedback(): void
    {
        $this->artifact_updater = HandleUpdateArtifactStub::withException(new \Tracker_Exception());
        $GLOBALS['Response']->method('feedbackHasErrors')->willReturn(true);
        $GLOBALS['Response']->method('getRawFeedback')->willReturn('Aaaah');
        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);
        $this->handle([]);
        self::assertSame(0, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(0, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
    }

    public function testItUpdatesArtifactLikeBeforeWhenAllLinkKeyIsNotProvidedOrForwardDirectionIsProvidedInAllLinkKey(): void
    {
        $this->artifact_updater = HandleUpdateArtifactStub::build();
        $this->handle([]);
        self::assertSame(0, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(0, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
    }

    public function testItDoesNotMakesTheReverseOfAnArtifactIfTheParentKeyWasGiven(): void
    {
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(1)
                                    ->withTrackerId(20)
                                    ->build()
        );


        $parent = ['id' => 12];

        $value           = new ArtifactValuesRepresentation();
        $value->parent   = $parent;
        $value->field_id = 1;

        $values[] = $value;

        $this->handle($values);
        self::assertSame(0, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
        self::assertSame(0, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(1, $this->artifact_updater->getUpdateForwardArtifactMethodCallCount());
    }

    public function testItDoesNotMakesTheReverseOfAnArtifactIfTheLinksKeyIsGiven(): void
    {
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(1)
                                    ->withTrackerId(20)
                                    ->build()
        );


        $links = ['id' => 12, 'type' => ''];

        $value           = new ArtifactValuesRepresentation();
        $value->links    = [$links];
        $value->field_id = 1;

        $values[] = $value;

        $this->handle($values);
        self::assertSame(0, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
        self::assertSame(0, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(1, $this->artifact_updater->getUpdateForwardArtifactMethodCallCount());
    }

    public function testItLinksTheArtifactWithForwardAndReverseLink(): void
    {
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(1)
                                    ->withTrackerId(20)
                                    ->build()
        );

        $links_reverse            = new LinkWithDirectionRepresentation();
        $links_reverse->id        = 12;
        $links_reverse->type      = "";
        $links_reverse->direction = "reverse";

        $links_forward            = new LinkWithDirectionRepresentation();
        $links_forward->id        = 15;
        $links_forward->type      = "";
        $links_forward->direction = "forward";

        $value            = new ArtifactValuesRepresentation();
        $value->all_links = [$links_reverse, $links_forward];
        $value->field_id  = 1;

        $values[] = $value;

        $this->handle($values);
        self::assertSame(1, $this->artifact_updater->getUnlinkReverseArtifactMethodCallCount());
        self::assertSame(1, $this->artifact_updater->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
        self::assertSame(1, $this->artifact_updater->getUpdateForwardArtifactMethodCallCount());
    }

    public function testItThrowsARestExceptionWhenTheArtifactCannotBeUpdated(): void
    {
        $this->check_artifact_rest_update_conditions = CheckArtifactRestUpdateConditionsStub::disallowArtifactUpdate();

        $this->expectException(RestException::class);
        $this->handle([]);
    }
}

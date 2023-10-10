<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use Tracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\Tracker\Semantic\ArtifactCannotBeCreatedReasonsGetter;
use Tuleap\Tracker\Semantic\CollectionOfCreationSemanticToCheck;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BuildCompleteTrackerRESTRepresentationStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\Semantic\GetTitleSemanticStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class TrackerRepresentationBuilderTest extends TestCase
{
    private const PROJECT_ID        = 205;
    private const FIRST_TRACKER_ID  = 1;
    private const SECOND_TRACKER_ID = 2;

    private CollectionOfCreationSemanticToCheck $semantics_to_check;
    /**
     * @var Tracker[]
     */
    private array $project_trackers;

    private int $offset = 0;
    private int $limit  = 50;

    protected function setUp(): void
    {
        $project                =   ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withPublicName("Sibérie")->build();
        $this->project_trackers = [
            TrackerTestBuilder::aTracker()->withProject($project)->withId(self::FIRST_TRACKER_ID)->build(),
            TrackerTestBuilder::aTracker()->withProject($project)->withId(self::SECOND_TRACKER_ID)->build(),
        ];

        $this->semantics_to_check =  CollectionOfCreationSemanticToCheck::fromREST([])->value;
    }

    public function testItThrowsExceptionWhenThereAreSemanticsToCheckAndTheFullTrackerRepresentationIsSelected(): void
    {
        $this->semantics_to_check =  CollectionOfCreationSemanticToCheck::fromREST(["title"])->value;
        $this->expectException(RestException::class);
        $this->buildTrackerRepresentations(CompleteTrackerRepresentation::FULL_REPRESENTATION);
    }

    public function testItReturnsTheMinimalRepresentation(): void
    {
        $representation = $this->buildTrackerRepresentations(MinimalTrackerRepresentation::MINIMAL_REPRESENTATION);
        self::assertCount(2, $representation);
        self::assertInstanceOf(MinimalTrackerRepresentation::class, $representation[0]);
    }

    public function testItReturnsTheFullRepresentation(): void
    {
        $representation = $this->buildTrackerRepresentations(CompleteTrackerRepresentation::FULL_REPRESENTATION);

        self::assertCount(2, $representation);
        self::assertInstanceOf(CompleteTrackerRepresentation::class, $representation[0]);
    }

    /**
     * @dataProvider getPaginatedRepresentations
     */
    public function testItReturnsAPaginatedCollectionOfTheProjectTrackers(int $offset, int $expected_tracker_id): void
    {
        $this->limit  = 1;
        $this->offset = $offset;

        $tracker_representations = $this->buildTrackerRepresentations(CompleteTrackerRepresentation::FULL_REPRESENTATION);

        self::assertCount($this->limit, $tracker_representations);
        self::assertSame($expected_tracker_id, $tracker_representations[0]->id);
    }

    private function getPaginatedRepresentations(): array
    {
        return [
            [0, self::FIRST_TRACKER_ID],
            [1, self::SECOND_TRACKER_ID],
        ];
    }

    /**
     * @return TrackerRepresentation[]
     */
    private function buildTrackerRepresentations(string $tracker_representation): array
    {
        $user = UserTestBuilder::aUser()->build();

        $representation_build = new TrackerRepresentationBuilder(
            BuildCompleteTrackerRESTRepresentationStub::build(),
            new ArtifactCannotBeCreatedReasonsGetter(
                VerifySubmissionPermissionStub::withSubmitPermission(),
                RetrieveUsedFieldsStub::withNoFields(),
                GetTitleSemanticStub::withoutTextField()
            )
        );

        return $representation_build->buildTrackerRepresentations(
            $user,
            $this->project_trackers,
            $tracker_representation,
            $this->limit,
            $this->offset,
            $this->semantics_to_check
        );
    }
}

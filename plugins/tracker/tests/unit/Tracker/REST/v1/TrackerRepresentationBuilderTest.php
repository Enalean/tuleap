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
use Tuleap\Tracker\Test\Stub\RetrieveTrackersByGroupIdAndUserCanViewStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class TrackerRepresentationBuilderTest extends TestCase
{
    private const PROJECT_ID = 205;

    private RetrieveTrackersByGroupIdAndUserCanViewStub $tracker_retriever;
    private \Project $project;
    private CollectionOfCreationSemanticToCheck $semantics_to_check;

    protected function setUp(): void
    {
        $this->project =   ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withPublicName("Sibérie")->build();
        $tracker       = TrackerTestBuilder::aTracker()->withProject(
            $this->project
        )->build();

        $this->tracker_retriever  = RetrieveTrackersByGroupIdAndUserCanViewStub::withTrackers($tracker);
        $this->semantics_to_check =  CollectionOfCreationSemanticToCheck::fromREST([])->value;
    }

    /**
     * @return TrackerRepresentation[]
     */
    private function buildTrackerRepresentations(string $tracker_representation, bool $filter_on_tracker_administration_permission = false): array
    {
        $user = UserTestBuilder::aUser()->build();

        $representation_build = new TrackerRepresentationBuilder(
            $this->tracker_retriever,
            BuildCompleteTrackerRESTRepresentationStub::defaultRepresentation(),
            new ArtifactCannotBeCreatedReasonsGetter(VerifySubmissionPermissionStub::withSubmitPermission())
        );

        return $representation_build->buildTrackerRepresentations($user, $this->project, $tracker_representation, 50, 0, $filter_on_tracker_administration_permission, $this->semantics_to_check);
    }

    public function testItThrowsExceptionWhenThereAreSemanticsToCheckAndTheFullTrackerRepresentationIsSelected(): void
    {
        $this->semantics_to_check =  CollectionOfCreationSemanticToCheck::fromREST(["title"])->value;
        self::expectException(RestException::class);
        $this->buildTrackerRepresentations(CompleteTrackerRepresentation::FULL_REPRESENTATION);
    }

    public function testItReturnsTheMinimalRepresentation(): void
    {
        $representation = $this->buildTrackerRepresentations(MinimalTrackerRepresentation::MINIMAL_REPRESENTATION);
        self::assertSame(1, count($representation));
        self::assertInstanceOf(MinimalTrackerRepresentation::class, $representation[0]);
    }

    public function testItReturnsTheFullRepresentation(): void
    {
        $representation = $this->buildTrackerRepresentations(CompleteTrackerRepresentation::FULL_REPRESENTATION);

        self::assertSame(1, count($representation));
        self::assertInstanceOf(CompleteTrackerRepresentation::class, $representation[0]);
    }

    public function testItReturnsTheCompleteRepresentationOfTheUserTrackerAdmin(): void
    {
        $tracker_admin = $this->createMock(Tracker::class);
        $tracker_admin->method('userIsAdmin')->willReturn(true);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->willReturn(false);

        $this->tracker_retriever = RetrieveTrackersByGroupIdAndUserCanViewStub::withTrackers($tracker_admin, $tracker);

        $representation = $this->buildTrackerRepresentations(CompleteTrackerRepresentation::FULL_REPRESENTATION, true);

        self::assertSame(1, count($representation));
        self::assertInstanceOf(CompleteTrackerRepresentation::class, $representation[0]);
    }
}

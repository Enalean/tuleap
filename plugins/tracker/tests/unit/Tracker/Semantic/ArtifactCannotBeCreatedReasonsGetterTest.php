<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic;

use Tracker_Semantic_Title;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class ArtifactCannotBeCreatedReasonsGetterTest extends TestCase
{
    private VerifySubmissionPermissionStub $can_submit_artifact_verifier;


    protected function setUp(): void
    {
        $this->can_submit_artifact_verifier = VerifySubmissionPermissionStub::withSubmitPermission();
    }

    private function getReasons(CollectionOfCreationSemanticToCheck $semantics_to_check): CollectionOfCannotCreateArtifactReason
    {
        $user    = UserTestBuilder::buildSiteAdministrator();
        $tracker = TrackerTestBuilder::aTracker()->withId(3000)->build();

        $artifact_creation_from_semantic_checker = new ArtifactCannotBeCreatedReasonsGetter(
            $this->can_submit_artifact_verifier
        );

        return $artifact_creation_from_semantic_checker->getCannotCreateArtifactReasons($semantics_to_check, $tracker, $user);
    }

    public function testItReturnsEmptyArrayOfReasonIfThereIsNoSemanticsGiven(): void
    {
        $semantics_to_check = CollectionOfCreationSemanticToCheck::fromREST([])->value;

        $cannot_create_reasons = $this->getReasons($semantics_to_check);

        self::assertEmpty($cannot_create_reasons->getReasons());
    }

    public function testItFillsTheReasonCollectionWhenTheUserCannotCreateArtifact(): void
    {
        $this->can_submit_artifact_verifier = VerifySubmissionPermissionStub::withoutSubmitPermission();

        $semantics_to_check = CollectionOfCreationSemanticToCheck::fromREST([Tracker_Semantic_Title::NAME])->value;

        $cannot_create_reasons = $this->getReasons($semantics_to_check);

        self::assertNotEmpty($cannot_create_reasons->getReasons());
        self::assertSame(1, count($cannot_create_reasons->getReasons()));
        self::assertNotEmpty($cannot_create_reasons->getReasons()[0]->reason);
    }
}

<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace unit\Tracker\Reference;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Reference\CheckCrossReferenceValidityEvent;
use Tuleap\Reference\CrossReference;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Reference\CrossReferenceValidator;

final class CrossReferenceValidatorTest extends TestCase
{
    private CrossReferenceValidator $cross_ref_validator;
    private Stub|\Tracker_ArtifactFactory $artifact_factory;

    public function setUp(): void
    {
        $this->artifact_factory    = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->cross_ref_validator = new CrossReferenceValidator($this->artifact_factory);
    }

    public function testItExtractInvalidCrossReferences(): void
    {
        $valid_cross_ref                       = new CrossReference(
            1,
            108,
            "plugin_tracker_artifact",
            "rel",
            2,
            108,
            "plugin_tracker_artifact",
            "rev",
            102
        );
        $not_artifact_cross_reference          = new CrossReference(
            11,
            108,
            "banana",
            "rel",
            2,
            108,
            "apple",
            "rev",
            102
        );
        $artifact_not_existing_cross_reference = new CrossReference(
            3,
            108,
            "plugin_tracker_artifact",
            "rel",
            2,
            108,
            "plugin_tracker_artifact",
            "rev",
            102
        );
        $user_cant_view_cross_reference        = new CrossReference(
            4,
            108,
            "plugin_tracker_artifact",
            "rel",
            2,
            108,
            "plugin_tracker_artifact",
            "rev",
            102
        );
        $invalid_target_cross_reference        = new CrossReference(
            1,
            108,
            "plugin_tracker_artifact",
            "rel",
            13,
            108,
            "plugin_tracker_artifact",
            "rev",
            102
        );

        $artifact_valid_1          = $this->createStub(Artifact::class);
        $artifact_valid_2          = $this->createStub(Artifact::class);
        $artifact_4_user_cant_view = $this->createStub(Artifact::class);

        $artifact_valid_1->method("getId")->willReturn(1);
        $artifact_valid_2->method("getId")->willReturn(2);
        $artifact_4_user_cant_view->method("getId")->willReturn(4);

        $this->artifact_factory->method("getArtifactById")->willReturnCallback(
            fn (int $artifact_id): ?Artifact => match ($artifact_id) {
                1 => $artifact_valid_1,
                2 => $artifact_valid_2,
                3, 13 => null,
                4 => $artifact_4_user_cant_view,
            }
        );

        $artifact_valid_1->method('userCanView')->willReturn(true);
        $artifact_valid_2->method('userCanView')->willReturn(true);
        $artifact_4_user_cant_view->method('userCanView')->willReturn(false);


        $cross_reference = [
            $valid_cross_ref,
            $not_artifact_cross_reference,
            $artifact_not_existing_cross_reference,
            $user_cant_view_cross_reference,
            $invalid_target_cross_reference,
        ];

        $event = new CheckCrossReferenceValidityEvent($cross_reference, UserTestBuilder::aUser()->build());

        $this->cross_ref_validator->removeInvalidCrossReferences($event);

        self::assertEquals([$valid_cross_ref, $not_artifact_cross_reference], $event->getCrossReferences());
    }
}

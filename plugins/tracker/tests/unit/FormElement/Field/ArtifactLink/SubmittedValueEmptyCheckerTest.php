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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmittedValueEmptyCheckerTest extends TestCase
{
    private SubmittedValueEmptyChecker $empty_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empty_checker = new SubmittedValueEmptyChecker();
    }

    public function testValueIsNotEmptyIfSubmittingParent(): void
    {
        $submitted_value = [
            'parent' => [
                '1004',
            ],
        ];

        self::assertFalse(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->createMock(ArtifactLinkField::class),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    public function testValueIsNotEmptyIfSubmittingNewLinks(): void
    {
        $submitted_value = [
            'new_values' => '1004,1005',
            'type'       => '',
        ];

        self::assertFalse(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->buildArtifactLinkFieldWithoutData(),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    public function testValueIsNotEmptyIfThereIsAtLeastOneReverseLink(): void
    {
        $submitted_value = [];

        self::assertFalse(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->buildArtifactLinkFieldWithReverseLinks(),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    public function testValueIsNotEmptyIfRemovingSomeLinks(): void
    {
        $submitted_value = [
            'removed_values' => [1004],
        ];

        self::assertFalse(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->buildArtifactLinkFieldWithLinks(),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    public function testValueIsEmptyIfNoDataAtAllForField(): void
    {
        $submitted_value = [
            'new_values' => '',
            'type' => '',
            'parent' => [],
        ];

        self::assertTrue(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->buildArtifactLinkFieldWithoutData(),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    public function testValueIsEmptyIfNoDataAtAllForFieldSubmittedFromTheArtifactView(): void
    {
        $submitted_value = [
            'new_values' => '',
            'type' => '',
            'parent' => [ 0 => ''],
        ];

        self::assertTrue(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->buildArtifactLinkFieldWithoutData(),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    public function testValueIsEmptyIfRemovingAllLinks(): void
    {
        $submitted_value = [
            'removed_values' => [1004, 1005],
        ];

        self::assertTrue(
            $this->empty_checker->isSubmittedValueEmpty(
                $submitted_value,
                $this->buildArtifactLinkFieldWithLinks(),
                ArtifactTestBuilder::anArtifact(1)->build(),
            )
        );
    }

    private function buildArtifactLinkFieldWithReverseLinks(): ArtifactLinkField
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('getReverseLinks')->willReturn([
            new Tracker_ArtifactLinkInfo(1, 'key', 101, 1, 1, null),
        ]);

        return $field;
    }

    private function buildArtifactLinkFieldWithLinks(): ArtifactLinkField
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('getReverseLinks')->willReturn([]);

        $last_changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $last_changeset_value->method('getArtifactIds')->willReturn([
            1004,
            1005,
        ]);
        $field->method('getLastChangesetValue')->willReturn($last_changeset_value);

        return $field;
    }

    private function buildArtifactLinkFieldWithoutData(): ArtifactLinkField
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('getReverseLinks')->willReturn([]);

        $last_changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $last_changeset_value->method('getArtifactIds')->willReturn([]);
        $field->method('getLastChangesetValue')->willReturn($last_changeset_value);

        return $field;
    }
}

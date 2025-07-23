<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\ArtifactLink;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class SubmittedValueConvertorTest extends TestCase
{
    private SubmittedValueConvertor $convertor;
    private ArtifactLinkChangesetValue $previous_changesetvalue;

    #[\Override]
    protected function setUp(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $changesets_123 = ChangesetTestBuilder::aChangeset(1231)->build();
        $changesets_124 = ChangesetTestBuilder::aChangeset(1241)->build();
        $changesets_201 = ChangesetTestBuilder::aChangeset(2011)->build();

        $art_123 = ArtifactTestBuilder::anArtifact(123)->inTracker($tracker)->withChangesets($changesets_123)->build();
        $art_124 = ArtifactTestBuilder::anArtifact(124)->inTracker($tracker)->withChangesets($changesets_124)->build();
        $art_201 = ArtifactTestBuilder::anArtifact(201)->inTracker($tracker)->withChangesets($changesets_201)->build();

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $this->previous_changesetvalue = ChangesetValueArtifactLinkTestBuilder::aValue(1, $changesets_123, ArtifactLinkFieldBuilder::anArtifactLinkField(654)->build())
            ->withForwardLinks([201 => Tracker_ArtifactLinkInfo::buildFromArtifact($art_201, '_is_child')])->build();

        $artifact_factory->method('getArtifactById')->willReturnCallback(static fn(int $id) => match ($id) {
            123 => $art_123,
            124 => $art_124,
            201 => $art_201,
        });

        $this->convertor = new SubmittedValueConvertor($artifact_factory);
    }

    public function testItChangesTheTypeOfAnExistingLink(): void
    {
        $submitted_value = [
            'new_values' => '',
            'types'      => [
                '201' => 'fixed_in',
            ],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );

        self::assertEquals('fixed_in', $updated_submitted_value['list_of_artifactlinkinfo'][201]->getType());
    }

    public function testItChangesTheTypeToNullOfAnExistingLink(): void
    {
        $submitted_value = [
            'new_values' => '',
            'types'      => [
                '201' => '',
            ],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );

        self::assertEquals(null, $updated_submitted_value['list_of_artifactlinkinfo'][201]->getType());
    }

    public function testItDoesNotMutateTheExistingArtifactLinkInfo(): void
    {
        $submitted_value = [
            'new_values' => '',
            'types'      => [
                '201' => '_is_child',
            ],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );

        $existing_list_of_artifactlinkinfo = $this->previous_changesetvalue->getValue();

        self::assertEquals(
            $existing_list_of_artifactlinkinfo[201],
            $updated_submitted_value['list_of_artifactlinkinfo'][201]
        );
    }

    public function testItConvertsWhenThereIsNoType(): void
    {
        $submitted_value = ['new_values' => '123, 124'];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );
        self::assertEquals(null, $updated_submitted_value['list_of_artifactlinkinfo']['123']->getType());
        self::assertEquals(null, $updated_submitted_value['list_of_artifactlinkinfo']['124']->getType());
    }

    public function testItConvertsWhenThereIsOnlyOneType(): void
    {
        $submitted_value = [
            'new_values' => '123, 124',
            'types'      => ['123' => '_is_child', '124' => '_is_child'],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );
        self::assertEquals('_is_child', $updated_submitted_value['list_of_artifactlinkinfo']['123']->getType());
        self::assertEquals('_is_child', $updated_submitted_value['list_of_artifactlinkinfo']['124']->getType());
    }

    public function testItConvertsWhenEachArtifactLinkHasItsOwnType(): void
    {
        $submitted_value = [
            'new_values' => '123, 124',
            'types'      => ['123' => '_is_child', '124' => '_is_foo'],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );
        self::assertEquals('_is_child', $updated_submitted_value['list_of_artifactlinkinfo']['123']->getType());
        self::assertEquals('_is_foo', $updated_submitted_value['list_of_artifactlinkinfo']['124']->getType());
    }
}

<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field_Text;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Description\TrackerSemanticDescription;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactDescriptionProviderTest extends TestCase
{
    private TrackerSemanticDescription&MockObject $semantic_description;
    private ArtifactDescriptionProvider $provider;

    protected function setUp(): void
    {
        $this->semantic_description = $this->createMock(TrackerSemanticDescription::class);
        $this->provider             = new ArtifactDescriptionProvider($this->semantic_description);
    }

    public function testGetDescriptionReturnNullIfNoFieldInSemantic(): void
    {
        $this->semantic_description->expects($this->once())->method('getField')->willReturn(null);

        self::assertEquals('', $this->provider->getDescription(ArtifactTestBuilder::anArtifact(1)->build()));
    }

    public function testGetDescriptionReturnNullIfUserCannotReadTheField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(false);

        self::assertEquals('', $this->provider->getDescription(ArtifactTestBuilder::anArtifact(1)->build()));
    }

    public function testGetDescriptionReturnNullIfThereIsNoLastChangeset(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->expects($this->once())->method('getLastChangeset')->willReturn(null);

        self::assertEquals('', $this->provider->getDescription($artifact));
    }

    public function testGetDescriptionReturnNullIfNoValueForField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1);

        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->withChangesets($changeset)->build();
        $changeset->setFieldValue($field, null);

        self::assertEquals('', $this->provider->getDescription($artifact));
    }

    public function testGetDescriptionReturnTheDescriptionAsPlainText(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1);

        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->withChangesets($changeset)->build();

        $changeset->setFieldValue(
            $field,
            ChangesetValueTextTestBuilder::aValue(1, $changeset, $field)
                ->withValue('The description', \Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT)
                ->build()
        );

        self::assertEquals('The description', $this->provider->getDescription($artifact));
    }

    public function testGetPostProcessedDescriptionReturnEmptyStringIfNoFieldInSemantic(): void
    {
        $this->semantic_description->expects($this->once())->method('getField')->willReturn(null);

        self::assertEquals('', $this->provider->getPostProcessedDescription(ArtifactTestBuilder::anArtifact(1)->build()));
    }

    public function testGetPostProcessedDescriptionReturnEmptyStringIfUserCannotReadTheField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(false);

        self::assertEquals('', $this->provider->getPostProcessedDescription(ArtifactTestBuilder::anArtifact(1)->build()));
    }

    public function testGetPostProcessedDescriptionReturnEmptyStringIfThereIsNoLastChangeset(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->expects($this->once())->method('getLastChangeset')->willReturn(null);

        self::assertEquals('', $this->provider->getPostProcessedDescription($artifact));
    }

    public function testGetPostProcessedDescriptionReturnEmptyStringIfNoValueForField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1);

        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->withChangesets($changeset)->build();
        $changeset->setFieldValue($field, null);

        self::assertEquals('', $this->provider->getPostProcessedDescription($artifact));
    }

    public function testGetPostProcessedDescriptionReturnTheDescriptionAsFormatHTML(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();

        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->withChangesets($changeset)->inTracker($tracker)->build();

        $field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->expects($this->once())->method('getField')->willReturn($field);

        $field->expects($this->once())->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1);
        $field->method('getTracker')->willReturn($tracker);

        $description = "<p>Description:</p>\n\n<ul>\n\t<li>Element 1</li>\n\t<li>Element 2</li>\n\t<li>Element 3 puce</li>\n</ul>\n";
        $changeset->setFieldValue(
            $field,
            ChangesetValueTextTestBuilder::aValue(1, $changeset, $field)
                ->withValue($description, Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT)
                ->build()
        );

        self::assertEquals($description, $this->provider->getPostProcessedDescription($artifact));
    }
}

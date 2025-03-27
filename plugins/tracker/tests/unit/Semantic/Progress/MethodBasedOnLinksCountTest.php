<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MethodBasedOnLinksCountTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_FormElement_Field_ArtifactLink&MockObject $links_field;
    private MethodBasedOnLinksCount $method;
    private SemanticProgressDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->dao         = $this->createMock(SemanticProgressDao::class);
        $this->links_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->links_field->method('getId')->willReturn(1003);

        $this->method = new MethodBasedOnLinksCount(
            $this->dao,
            '_is_child'
        );
    }

    public function testItReturnsTrueIfFieldIsUsed(): void
    {
        $this->assertTrue($this->method->isFieldUsedInComputation($this->links_field));
    }

    public function testItReturnsFalseIfFieldIsNotUsed(): void
    {
        $random_field = DateFieldBuilder::aDateField(1001)->build();

        $this->assertFalse($this->method->isFieldUsedInComputation($random_field));
    }

    public function testItDoesComputesTheProgressWhenItHasOpenAndClosedLinkedArtifacts(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(113)->build();

        $last_artifact_changeset = $this->createMock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $last_artifact_changeset->method('getValue')->willReturn(
            [
                '141' => $this->buildArtifactLinkInfo(141, '_is_child', $tracker, false), // 1 out of 4 children is closed
                '142' => $this->buildArtifactLinkInfo(142, 'is_subtask', $tracker, true),
                '143' => $this->buildArtifactLinkInfo(143, 'covered_by', $tracker, true),
                '144' => $this->buildArtifactLinkInfo(144, '_is_child', $tracker, true),
                '145' => $this->buildArtifactLinkInfo(145, '_is_child', $tracker, true),
                '146' => $this->buildArtifactLinkInfo(146, '_is_child', $tracker, true),
            ],
        );

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getAnArtifactLinkField')->willReturn($this->links_field);

        $this->links_field->expects($this->once())->method('getLastChangesetValue')
            ->with($artifact)
            ->willReturn($last_artifact_changeset);

        $progression_result = $this->method->computeProgression(
            $artifact,
            UserTestBuilder::buildWithDefaults()
        );

        $this->assertEquals(0.25, $progression_result->getValue());
    }

    public function testItDoesNotComputeTheProgressWhenThereIsNoLinkField(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getAnArtifactLinkField')->willReturn(null);

        $progression_result = $this->method->computeProgression(
            $artifact,
            UserTestBuilder::buildWithDefaults()
        );

        $this->assertEquals(null, $progression_result->getValue());
    }

    /**
     * @testWith [true, 0]
     *           [false, 1]
     */
    public function testItConsidersAnArtifactWithoutArtifactLinkValueAsHavingNoLinks(bool $is_artifact_open, float $expected_progress_value): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('isOpen')->willReturn($is_artifact_open);
        $artifact->method('getAnArtifactLinkField')->willReturn($this->links_field);

        $this->links_field->expects($this->once())->method('getLastChangesetValue')
            ->with($artifact)
            ->willReturn(null);

        $progression_result = $this->method->computeProgression(
            $artifact,
            UserTestBuilder::buildWithDefaults()
        );

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
    }

    /**
     * @testWith [true, 0]
     *           [false, 1]
     */
    public function testItComputesWhenItHasNoLinksOfGivenType(bool $is_artifact_open, float $expected_progress_value): void
    {
        $last_artifact_changeset = $this->createMock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $last_artifact_changeset->method('getValue')->willReturn([]);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('isOpen')->willReturn($is_artifact_open);
        $artifact->method('getAnArtifactLinkField')->willReturn($this->links_field);

        $this->links_field->expects($this->once())->method('getLastChangesetValue')
            ->with($artifact)
            ->willReturn($last_artifact_changeset);

        $progression_result = $this->method->computeProgression(
            $artifact,
            UserTestBuilder::buildWithDefaults()
        );

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
    }

    private function buildArtifactLinkInfo(int $artifact_id, string $type, \Tracker $tracker, bool $is_artifact_open): \Tracker_ArtifactLinkInfo
    {
        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)
            ->inTracker($tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset($artifact_id * 1000)->build())
            ->isOpen($is_artifact_open)
            ->build();

        return \Tracker_ArtifactLinkInfo::buildFromArtifact($artifact, $type);
    }

    public function testItIsConfigured(): void
    {
        $this->assertTrue($this->method->isConfiguredAndValid());
    }

    public function testItExportsToREST(): void
    {
        self::assertEquals(
            new SemanticProgressBasedOnLinksCountRepresentation('_is_child'),
            $this->method->exportToREST(UserTestBuilder::buildWithDefaults())
        );
    }

    public function testItExportsItsConfigurationToXml(): void
    {
        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><semantics/>';
        $root     = new \SimpleXMLElement($xml_data);

        $this->method->exportToXMl($root, []);

        $this->assertCount(1, $root->children());
        $this->assertEquals('progress', (string) $root->semantic['type']);
        $this->assertEquals('_is_child', (string) $root->semantic->artifact_link_type['shortname']);
    }

    public function testSavesItsConfiguration(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(113)->build();

        $this->dao->expects($this->once())->method('save')->with(113, null, null, '_is_child')->willReturn(true);

        $this->assertTrue($this->method->saveSemanticForTracker($tracker));
    }

    public function testItDeletesItsConfiguration(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(113)->build();

        $this->dao->expects($this->once())->method('delete')->with(113)->willReturn(true);

        $this->assertTrue(
            $this->method->deleteSemanticForTracker($tracker)
        );
    }
}

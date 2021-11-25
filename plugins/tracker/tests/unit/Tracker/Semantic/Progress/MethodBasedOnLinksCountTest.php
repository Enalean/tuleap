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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Artifact\Artifact;

class MethodBasedOnLinksCountTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_ArtifactLink
     */
    private $links_field;
    /**
     * @var MethodBasedOnLinksCount
     */
    private $method;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticProgressDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao         = \Mockery::mock(SemanticProgressDao::class);
        $this->links_field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class, ['getId' => 1003]);
        $this->method      = new MethodBasedOnLinksCount(
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
        $random_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->assertFalse($this->method->isFieldUsedInComputation($random_field));
    }

    public function testItDoesComputesTheProgressWhenItHasOpenAndClosedLinkedArtifacts(): void
    {
        $tracker = \Mockery::mock(\Tracker::class, [
            'getItemName' => 'stories',
            'getGroupId' => 104,
            'getId' => 113
        ]);

        $last_artifact_changeset = \Mockery::mock(
            \Tracker_Artifact_ChangesetValue_ArtifactLink::class,
            ['getValue' => [
                '141' => $this->buildArtifactLinkInfo(141, "_is_child", $tracker, false), // 1 out of 4 children is closed
                '142' => $this->buildArtifactLinkInfo(142, "is_subtask", $tracker, true),
                '143' => $this->buildArtifactLinkInfo(143, "covered_by", $tracker, true),
                '144' => $this->buildArtifactLinkInfo(144, "_is_child", $tracker, true),
                '145' => $this->buildArtifactLinkInfo(145, "_is_child", $tracker, true),
                '146' => $this->buildArtifactLinkInfo(146, "_is_child", $tracker, true)
            ]]
        );

        $artifact = \Mockery::mock(
            Artifact::class,
            [
                'getAnArtifactLinkField' => $this->links_field
            ]
        );
        $this->links_field->shouldReceive('getLastChangesetValue')
            ->once()
            ->with($artifact)
            ->andReturn($last_artifact_changeset);

        $progression_result = $this->method->computeProgression(
            $artifact,
            \Mockery::mock(\PFUser::class)
        );

        $this->assertEquals(0.25, $progression_result->getValue());
    }

    public function testItDoesNotComputeTheProgressWhenThereIsNoLinkField(): void
    {
        $artifact = \Mockery::mock(
            Artifact::class,
            [
                'getAnArtifactLinkField' => null
            ]
        );

        $progression_result = $this->method->computeProgression(
            $artifact,
            \Mockery::mock(\PFUser::class)
        );

        $this->assertEquals(null, $progression_result->getValue());
    }

    /**
     * @testWith [true, 0]
     *           [false, 1]
     */
    public function testItConsidersAnArtifactWithoutArtifactLinkValueAsHavingNoLinks(bool $is_artifact_open, float $expected_progress_value): void
    {
        $artifact = \Mockery::mock(Artifact::class, [
            'isOpen' => $is_artifact_open,
            'getAnArtifactLinkField' => $this->links_field
        ]);

        $this->links_field->shouldReceive('getLastChangesetValue')
            ->once()
            ->with($artifact)
            ->andReturnNull();

        $progression_result = $this->method->computeProgression(
            $artifact,
            \Mockery::mock(\PFUser::class)
        );

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
    }

    /**
     * @testWith [true, 0]
     *           [false, 1]
     */
    public function testItComputesWhenItHasNoLinksOfGivenType(bool $is_artifact_open, float $expected_progress_value): void
    {
        $last_artifact_changeset = \Mockery::mock(
            \Tracker_Artifact_ChangesetValue_ArtifactLink::class,
            ['getValue' => []]
        );

        $artifact = \Mockery::mock(Artifact::class, [
            'isOpen' => $is_artifact_open,
            'getAnArtifactLinkField' => $this->links_field
        ]);

        $this->links_field->shouldReceive('getLastChangesetValue')
            ->once()
            ->with($artifact)
            ->andReturn($last_artifact_changeset);

        $progression_result = $this->method->computeProgression(
            $artifact,
            \Mockery::mock(\PFUser::class)
        );

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
    }

    private function buildArtifactLinkInfo(int $artifact_id, string $type, \Tracker $tracker, bool $is_artifact_open): \Tracker_ArtifactLinkInfo
    {
        $artifact = \Mockery::mock(
            Artifact::class,
            [
                'getId'            => $artifact_id,
                'getTracker'       => $tracker,
                'getLastChangeset' => \Mockery::mock(\Tracker_Artifact_Changeset::class, ['getId' => 12451]),
                'isOpen'           => $is_artifact_open
            ]
        );

        return \Tracker_ArtifactLinkInfo::buildFromArtifact($artifact, $type);
    }

    public function testItIsConfigured(): void
    {
        $this->assertTrue($this->method->isConfigured());
    }

    public function testItExportsToREST(): void
    {
        self::assertEquals(
            new SemanticProgressBasedOnLinksCountRepresentation('_is_child'),
            $this->method->exportToREST(\Mockery::mock(\PFUser::class))
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
        $tracker = \Mockery::mock(\Tracker::class, ['getId' => 113]);

        $this->dao->shouldReceive('save')->with(113, null, null, '_is_child')->once()->andReturn(true);

        $this->assertTrue($this->method->saveSemanticForTracker($tracker));
    }

    public function testItDeletesItsConfiguration(): void
    {
        $tracker = \Mockery::mock(\Tracker::class, ['getId' => 113]);

        $this->dao->shouldReceive('delete')->with(113)->once()->andReturn(true);

        $this->assertTrue(
            $this->method->deleteSemanticForTracker($tracker)
        );
    }
}

<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *  This file is a part of Tuleap.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactLinkInfo;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GetFieldDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\Mock&\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField
     */
    private $field;
    /**
     * @var int
     */
    private $last_changset_id;
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->field = \Mockery::mock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->last_changset_id = 1234;
        $this->artifact         = new Artifact(147, 65, 102, 1, null);
        $last_changeset         = new Tracker_Artifact_Changeset($this->last_changset_id, $this->artifact, '', '', '');
        $this->artifact->setChangesets([$last_changeset]);
    }

    public function testGetValuesFromArtifactChangesetWhenThereIsAnArtifact(): void
    {
        $this->field->shouldReceive('getChangesetValues')->with(\Mockery::any(), $this->last_changset_id)->once()->andReturn([]);

        $this->field->getFieldData('55', $this->artifact);
    }

    public function testDoesntFetchValuesWhenNoArtifactGiven(): void
    {
        $this->field->shouldReceive('getChangesetValues')->with(\Mockery::any(), $this->last_changset_id)->never();

        $this->field->getFieldData('55');
    }

    public function testOnlyAddsNewValuesWhenNoArtifactGiven(): void
    {
        $this->assertEquals(
            ['new_values' => '55', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55')
        );
    }

    public function testOnlyAddsNewValuesWhenEmptyArtifactGivenAtCSVArtifactCreation(): void
    {
        $artifact_without_changeset = new Artifact(148, 65, 102, 1, null);
        $artifact_without_changeset->setChangesets([]);

        $this->assertEquals(
            ['new_values' => '55,56', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55, 56', $artifact_without_changeset)
        );
    }

    public function testAddsOneValue(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn([]);
        $this->assertEquals(
            ['new_values' => '55', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55', $this->artifact)
        );
    }

    public function testAddsTwoNewValues(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn([]);
        $this->assertEquals(
            ['new_values' => '55,66', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55, 66', $this->artifact)
        );
    }

    public function testIgnoresAddOfArtifactThatAreAlreadyLinked(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            ]
        );

        $this->assertEquals(
            ['new_values' => '66', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55, 66', $this->artifact),
        );
    }

    public function testRemovesAllExistingArtifactLinks(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            ]
        );

        $this->assertEquals(
            [
                'new_values'   => '',
                'removed_values' => [55 => ['55'], 66 => ['66']],
                'types'        => [],
            ],
            $this->field->getFieldData('', $this->artifact),
        );
    }

    public function testRemovesFirstArtifactLink(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            ]
        );

        $this->assertEquals(
            ['new_values' => '', 'removed_values' => [55 => ['55']], 'types' => []],
            $this->field->getFieldData('66,77', $this->artifact),
        );
    }

    public function testRemovesMiddleArtifactLink(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            ]
        );

        $this->assertEquals(
            ['new_values' => '', 'removed_values' => [66 => ['66']], 'types' => []],
            $this->field->getFieldData('55,77', $this->artifact)
        );
    }

    public function testRemovesLastArtifactLink(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            ]
        );

        $this->assertEquals(
            ['new_values' => '', 'removed_values' => [77 => ['77']], 'types' => []],
            $this->field->getFieldData('55,66', $this->artifact)
        );
    }

    public function testAddsAndRemovesInOneCall(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            ]
        );

        $this->assertEquals(
            ['new_values' => '88', 'removed_values' => [77 => ['77']], 'types' => []],
            $this->field->getFieldData('55,66,88', $this->artifact),
        );
    }
}

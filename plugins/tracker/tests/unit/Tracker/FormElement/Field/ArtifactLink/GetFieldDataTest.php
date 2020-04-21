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
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactLinkInfo;

final class GetFieldDataTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\Mock&\Tracker_FormElement_Field_ArtifactLink
     */
    private $field;
    /**
     * @var int
     */
    private $last_changset_id;
    /**
     * @var \Tracker_Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->last_changset_id = 1234;
        $this->artifact         = new Tracker_Artifact(147, 65, 102, 1, null);
        $last_changeset         = new Tracker_Artifact_Changeset($this->last_changset_id, $this->artifact, '', '', '');
        $this->artifact->setChangesets([$last_changeset]);
    }

    public function testGetValuesFromArtifactChangesetWhenThereIsAnArtifact(): void
    {
        $this->field->shouldReceive('getChangesetValues')->with($this->last_changset_id)->once()->andReturn([]);

        $this->field->getFieldData('55', $this->artifact);
    }

    public function testDoesntFetchValuesWhenNoArtifactGiven(): void
    {
        $this->field->shouldReceive('getChangesetValues')->with($this->last_changset_id)->never();

        $this->field->getFieldData('55');
    }

    public function testOnlyAddsNewValuesWhenNoArtifactGiven(): void
    {
        $this->assertEquals(
            ['new_values' => '55', 'removed_values' => [], 'natures' => []],
            $this->field->getFieldData('55')
        );
    }

    public function testOnlyAddsNewValuesWhenEmptyArtifactGivenAtCSVArtifactCreation(): void
    {
        $artifact_without_changeset = new Tracker_Artifact(148, 65, 102, 1, null);
        $artifact_without_changeset->setChangesets([]);

        $this->assertEquals(
            ['new_values' => '55,56', 'removed_values' => [], 'natures' => []],
            $this->field->getFieldData('55, 56', $artifact_without_changeset)
        );
    }

    public function testAddsOneValue(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn([]);
        $this->assertEquals(
            ['new_values' => '55', 'removed_values' => [], 'natures' => []],
            $this->field->getFieldData('55', $this->artifact)
        );
    }

    public function testAddsTwoNewValues(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn([]);
        $this->assertEquals(
            ['new_values' => '55,66', 'removed_values' => [], 'natures' => []],
            $this->field->getFieldData('55, 66', $this->artifact)
        );
    }

    public function testAddsTwoNewValuesWithNatures(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn([]);

        $new_values = [
            'links' => [
                ['id' => '55', 'type' => '_is_child'],
                ['id' => '66', 'type' => 'custom'],
                ['id' => '77', 'type' => '']
            ]
        ];

        $this->assertEquals(
            [
                'new_values' => '55,66,77',
                'removed_values' => [],
                'natures' => [
                    '55' => '_is_child',
                    '66' => 'custom',
                    '77' => '',
                ]
            ],
            $this->field->getFieldDataFromRESTValue($new_values, $this->artifact)
        );
    }

    public function testIgnoresAddOfArtifactThatAreAlreadyLinked(): void
    {
        $this->field->shouldReceive('getChangesetValues')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', '')
            ]
        );

        $this->assertEquals(
            ['new_values' => '66', 'removed_values' => [], 'natures' => []],
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
                'natures'        => []
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
            ['new_values' => '', 'removed_values' => [55 => ['55']], 'natures' => []],
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
            ['new_values' => '', 'removed_values' => [66 => ['66']], 'natures' => []],
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
            ['new_values' => '', 'removed_values' => [77 => ['77']], 'natures' => []],
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
            ['new_values' => '88', 'removed_values' => [77 => ['77']], 'natures' => []],
            $this->field->getFieldData('55,66,88', $this->artifact),
        );
    }
}

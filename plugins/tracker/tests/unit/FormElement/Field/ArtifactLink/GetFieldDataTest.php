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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactLinkInfo;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

#[DisableReturnValueGenerationForTestDoubles]
final class GetFieldDataTest extends TestCase
{
    use GlobalLanguageMock;

    private const LAST_CHANGESET_ID = 1234;

    private ArtifactLinkField&MockObject $field;
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->field = $this->createPartialMock(ArtifactLinkField::class, ['getChangesetValues']);

        $this->artifact = new Artifact(147, 65, 102, 1, false);
        $last_changeset = new Tracker_Artifact_Changeset(self::LAST_CHANGESET_ID, $this->artifact, '', '', '');
        $this->artifact->setChangesets([$last_changeset]);
    }

    public function testGetValuesFromArtifactChangesetWhenThereIsAnArtifact(): void
    {
        $this->field->expects($this->once())->method('getChangesetValues')->with(self::anything(), self::LAST_CHANGESET_ID)->willReturn([]);

        $this->field->getFieldData('55', $this->artifact);
    }

    public function testDoesntFetchValuesWhenNoArtifactGiven(): void
    {
        $this->field->expects($this->never())->method('getChangesetValues')->with(self::anything(), self::LAST_CHANGESET_ID);

        $this->field->getFieldData('55');
    }

    public function testOnlyAddsNewValuesWhenNoArtifactGiven(): void
    {
        self::assertEquals(
            ['new_values' => '55', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55')
        );
    }

    public function testOnlyAddsNewValuesWhenEmptyArtifactGivenAtCSVArtifactCreation(): void
    {
        $artifact_without_changeset = new Artifact(148, 65, 102, 1, false);
        $artifact_without_changeset->setChangesets([]);

        self::assertEquals(
            ['new_values' => '55,56', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55, 56', $artifact_without_changeset)
        );
    }

    public function testAddsOneValue(): void
    {
        $this->field->method('getChangesetValues')->willReturn([]);
        self::assertEquals(
            ['new_values' => '55', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55', $this->artifact)
        );
    }

    public function testAddsTwoNewValues(): void
    {
        $this->field->method('getChangesetValues')->willReturn([]);
        self::assertEquals(
            ['new_values' => '55,66', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55, 66', $this->artifact)
        );
    }

    public function testIgnoresAddOfArtifactThatAreAlreadyLinked(): void
    {
        $this->field->method('getChangesetValues')->willReturn([
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
        ]);

        self::assertEquals(
            ['new_values' => '66', 'removed_values' => [], 'types' => []],
            $this->field->getFieldData('55, 66', $this->artifact),
        );
    }

    public function testRemovesAllExistingArtifactLinks(): void
    {
        $this->field->method('getChangesetValues')->willReturn([
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
        ]);

        self::assertEquals(
            [
                'new_values'     => '',
                'removed_values' => [55 => ['55'], 66 => ['66']],
                'types'          => [],
            ],
            $this->field->getFieldData('', $this->artifact),
        );
    }

    public function testRemovesFirstArtifactLink(): void
    {
        $this->field->method('getChangesetValues')->willReturn([
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ]);

        self::assertEquals(
            ['new_values' => '', 'removed_values' => [55 => ['55']], 'types' => []],
            $this->field->getFieldData('66,77', $this->artifact),
        );
    }

    public function testRemovesMiddleArtifactLink(): void
    {
        $this->field->method('getChangesetValues')->willReturn([
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ]);

        self::assertEquals(
            ['new_values' => '', 'removed_values' => [66 => ['66']], 'types' => []],
            $this->field->getFieldData('55,77', $this->artifact)
        );
    }

    public function testRemovesLastArtifactLink(): void
    {
        $this->field->method('getChangesetValues')->willReturn([
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ]);

        self::assertEquals(
            ['new_values' => '', 'removed_values' => [77 => ['77']], 'types' => []],
            $this->field->getFieldData('55,66', $this->artifact)
        );
    }

    public function testAddsAndRemovesInOneCall(): void
    {
        $this->field->method('getChangesetValues')->willReturn([
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ]);

        self::assertEquals(
            ['new_values' => '88', 'removed_values' => [77 => ['77']], 'types' => []],
            $this->field->getFieldData('55,66,88', $this->artifact),
        );
    }
}

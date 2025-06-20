<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkUpdaterDataFormaterTest extends TestCase
{
    private Tracker&MockObject $tracker;
    private ArtifactLinkField $artifact_link_field;

    protected function setUp(): void
    {
        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(15);
        $this->artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(66)->inTracker($this->tracker)->build();
    }

    public function testItAddNewValuesInNewValuesEntries(): void
    {
        $this->tracker->expects($this->once())->method('isProjectAllowedToUseType')->willReturn(false);

        $formater        = new ArtifactLinkUpdaterDataFormater();
        $formatted_value = $formater->formatFieldData($this->artifact_link_field, [100, 101], [], '');

        $expected[$this->artifact_link_field->getId()] = [
            'new_values'     => '100,101',
            'removed_values' => [],
        ];
        self::assertEquals($expected, $formatted_value);
    }

    public function testItRemoveOldValues(): void
    {
        $this->tracker->expects($this->once())->method('isProjectAllowedToUseType')->willReturn(false);

        $formater        = new ArtifactLinkUpdaterDataFormater();
        $formatted_value = $formater->formatFieldData($this->artifact_link_field, [], [200, 201], '');

        $expected[$this->artifact_link_field->getId()] = [
            'new_values'     => '',
            'removed_values' => [200 => true, 201 => true],
        ];
        self::assertEquals($expected, $formatted_value);
    }

    public function testItRemovesTypeOfArtifactLinkWhenItsNoLongerAvailableInProject(): void
    {
        $this->tracker->expects($this->once())->method('isProjectAllowedToUseType')->willReturn(false);

        $formater        = new ArtifactLinkUpdaterDataFormater();
        $formatted_value = $formater->formatFieldData($this->artifact_link_field, [100], [], 'legacy_type');

        $expected[$this->artifact_link_field->getId()] = [
            'new_values'     => '100',
            'removed_values' => [],
        ];
        self::assertEquals($expected, $formatted_value);
    }

    public function testItPreserveType(): void
    {
        $this->tracker->expects($this->once())->method('isProjectAllowedToUseType')->willReturn(true);

        $formater        = new ArtifactLinkUpdaterDataFormater();
        $formatted_value = $formater->formatFieldData($this->artifact_link_field, [100], [], 'used_type');

        $expected[$this->artifact_link_field->getId()]               = [
            'new_values'     => '100',
            'removed_values' => [],
        ];
        $expected[$this->artifact_link_field->getId()]['types'][100] = 'used_type';

        self::assertEquals($expected, $formatted_value);
    }
}

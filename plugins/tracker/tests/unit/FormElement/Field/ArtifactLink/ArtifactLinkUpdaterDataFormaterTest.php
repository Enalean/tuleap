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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkUpdaterDataFormaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_ArtifactLink
     */
    private $artifact_link_field;

    protected function setUp(): void
    {
        $this->tracker             = \Mockery::mock(Tracker::class);
        $this->artifact_link_field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->artifact_link_field->shouldReceive('getId')->andReturn(66);
        $this->artifact_link_field->shouldReceive('getTracker')->andReturn($this->tracker);
    }

    public function testItAddNewValuesInNewValuesEntries(): void
    {
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->once()->andReturnFalse();

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
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->once()->andReturnFalse();

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
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->once()->andReturnFalse();

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
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->once()->andReturnTrue();

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

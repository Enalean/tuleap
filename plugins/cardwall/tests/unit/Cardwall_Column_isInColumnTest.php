<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Cardwall_Column;
use Cardwall_FieldProviders_IProvideFieldGivenAnArtifact;
use Cardwall_OnTop_Config;
use Cardwall_OnTop_Config_TrackerMappingFactory;
use Cardwall_OnTop_Dao;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset_Null;
use Tracker_FormElement_Field_MultiSelectbox;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_Column_isInColumnTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Artifact $artifact;
    private Tracker_FormElement_Field_MultiSelectbox&MockObject $field;
    private Cardwall_FieldProviders_IProvideFieldGivenAnArtifact&MockObject $field_provider;
    private Cardwall_OnTop_Config $config;

    protected function setUp(): void
    {
        $tracker        = TrackerTestBuilder::aTracker()->withId(33)->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(452)
            ->inTracker($tracker)
            ->withChangesets(new Tracker_Artifact_Changeset_Null())
            ->build();

        $this->field          = $this->createMock(Tracker_FormElement_Field_MultiSelectbox::class);
        $this->field_provider = $this->createMock(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class);
        $this->field_provider->method('getField')->with($tracker)->willReturn($this->field);
        $dao                     = $this->createMock(Cardwall_OnTop_Dao::class);
        $column_factory          = $this->createMock(ColumnFactory::class);
        $tracker_mapping_factory = $this->createMock(Cardwall_OnTop_Config_TrackerMappingFactory::class);

        $column_factory->method('getDashboardColumns')->with($tracker)->willReturn(new ColumnCollection());
        $tracker_mapping_factory->method('getMappings');

        $this->config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
    }

    public function testItIsInTheCellIfTheLabelMatches(): void
    {
        $this->field->method('getFirstValueFor')->with($this->artifact->getLastChangeset())->willReturn('ongoing');
        $column = $this->newCardwallColumn(0, 'ongoing');
        $this->assertIn($column);
    }

    public function testItIsNotInTheCellIfTheLabelDoesntMatch(): void
    {
        $this->field->method('getFirstValueFor')->with($this->artifact->getLastChangeset())->willReturn('ongoing');
        $column = $this->newCardwallColumn(0, 'done');
        $this->assertNotIn($column);
    }

    public function testItIsInTheCellIfItHasNoStatusAndTheColumnHasId100(): void
    {
        $null_status = null;
        $this->field->method('getFirstValueFor')->with($this->artifact->getLastChangeset())->willReturn($null_status);
        $column = $this->newCardwallColumn(100, 'done');
        $this->assertIn($column);
    }

    public function testItIsNotInTheCellIfItHasNoStatus(): void
    {
        $null_status = null;
        $this->field->method('getFirstValueFor')->with($this->artifact->getLastChangeset())->willReturn($null_status);
        $column = $this->newCardwallColumn(123, 'done');
        $this->assertNotIn($column);
    }

    public function testItIsNotInTheCellIfHasANonMatchingLabelTheColumnIdIs100(): void
    {
        $this->field->method('getFirstValueFor')->with($this->artifact->getLastChangeset())->willReturn('ongoing');
        $column = $this->newCardwallColumn(100, 'done');
        $this->assertNotIn($column);
    }

    private function assertIn($column): void
    {
        self::assertTrue($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    private function assertNotIn($column): void
    {
        self::assertFalse($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    private function newCardwallColumn($id, $label): Cardwall_Column
    {
        return new Cardwall_Column($id, $label, 0);
    }
}

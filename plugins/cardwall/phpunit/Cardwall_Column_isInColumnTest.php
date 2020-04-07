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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_Column_isInColumnTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_MultiSelectbox
     */
    private $field;
    /**
     * @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $field_provider;
    /**
     * @var Cardwall_OnTop_Config
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(33);
        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        $changset = new Tracker_Artifact_Changeset_Null();
        $this->artifact->shouldReceive('getTracker')->andReturns($tracker);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($changset);

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class);
        $this->field_provider = Mockery::mock(\Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class);
        $this->field_provider->shouldReceive('getField')->with($tracker)->andReturn($this->field);
        $dao = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $column_factory = \Mockery::spy(\Cardwall_OnTop_Config_ColumnFactory::class);
        $tracker_mapping_factory = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMappingFactory::class);

        $column_factory->shouldReceive('getDashboardColumns')->with($tracker)->andReturn(new Cardwall_OnTop_Config_ColumnCollection());

        $this->config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
    }

    public function testItIsInTheCellIfTheLabelMatches(): void
    {
        $this->field->shouldReceive('getFirstValueFor')->with($this->artifact->getLastChangeset())->andReturns('ongoing');
        $column   = $this->newCardwall_Column(0, 'ongoing');
        $this->assertIn($column);
    }

    public function testItIsNotInTheCellIfTheLabelDoesntMatch(): void
    {
        $this->field->shouldReceive('getFirstValueFor')->with($this->artifact->getLastChangeset())->andReturns('ongoing');
        $column   = $this->newCardwall_Column(0, 'done');
        $this->assertNotIn($column);
    }

    public function testItIsInTheCellIfItHasNoStatusAndTheColumnHasId100(): void
    {
        $null_status = null;
        $this->field->shouldReceive('getFirstValueFor')->with($this->artifact->getLastChangeset())->andReturns($null_status);
        $column   = $this->newCardwall_Column(100, 'done');
        $this->assertIn($column);
    }

    public function testItIsNotInTheCellIfItHasNoStatus(): void
    {
        $null_status = null;
        $this->field->shouldReceive('getFirstValueFor')->with($this->artifact->getLastChangeset())->andReturns($null_status);
        $column   = $this->newCardwall_Column(123, 'done');
        $this->assertNotIn($column);
    }

    public function testItIsNotInTheCellIfHasANonMatchingLabelTheColumnIdIs100(): void
    {
        $this->field->shouldReceive('getFirstValueFor')->with($this->artifact->getLastChangeset())->andReturns('ongoing');
        $column   = $this->newCardwall_Column(100, 'done');
        $this->assertNotIn($column);
    }

    private function assertIn($column)
    {
         $this->assertTrue($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    private function assertNotIn($column)
    {
         $this->assertFalse($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    public function newCardwall_Column($id, $label)
    {
        $header_color = 0;
        return new Cardwall_Column($id, $label, $header_color);
    }
}

class Cardwall_Column_canContainStatusTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $column;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 100;
        $label = $header_color = 'whatever';
        $this->column = new Cardwall_Column($id, $label, $header_color);
    }

    public function testItReturnsTrueOnNoneColumnIfStatusIsNone(): void
    {
        $this->assertTrue($this->column->canContainStatus('None'));
    }
    public function testItReturnsTrueOnNoneColumnIfStatusIsNull(): void
    {
        $this->assertTrue($this->column->canContainStatus(null));
    }
}

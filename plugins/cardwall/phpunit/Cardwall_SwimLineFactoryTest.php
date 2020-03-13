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
final class Cardwall_SwimLineFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Cardwall_OnTop_Config|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $config;
    /**
     * @var Cardwall_SwimlineFactory
     */
    private $factory;

    protected function setUp() : void
    {
        parent::setUp();
        $this->config     = \Mockery::mock(\Cardwall_OnTop_Config::class);
        $this->factory    = new Cardwall_SwimlineFactory($this->config, \Mockery::spy(\Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class));
    }

    public function testItReturnsAnEmptyArrayIfThereAreNoColumnsAndNoPresenters() : void
    {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        $presenters = array();
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $this->assertSame(array(), $swimlines);
    }

    public function testItReturnsAnEmptyArrayIfThereAreNoColumnsButSomePresenters() : void
    {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        $presenters = array(\Mockery::spy(\Cardwall_CardInCellPresenter::class));
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $this->assertSame(array(), $swimlines);
    }

    public function testItReturnsANestedArrayOfPresenterPresentersIfThereAreColumnsButNoPresenters() : void
    {
        $mocked_column = \Mockery::spy(\Cardwall_Column::class);
        $mocked_column->shouldReceive('getId')->andReturns(44);
        $mocked_column->shouldReceive('isAutostacked')->andReturns(true);

        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection(array($mocked_column));
        $presenters = array();
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $expected   = array(
                          array('column_id' => 44, 'column_stacked' => true, 'cardincell_presenters' => array()));
        $this->assertSame($expected, $swimlines);
    }

    public function testItAsksTheColumnIfItGoesInThere() : void
    {
        $artifact1 = Mockery::mock(Tracker_Artifact::class);
        $artifact2 = Mockery::mock(Tracker_Artifact::class);
        $label = $bgcolor = null;
        $column1   = new Cardwall_Column(55, $label, $bgcolor);
        $column2   = new Cardwall_Column(100, $label, $bgcolor);
        $columns   = new Cardwall_OnTop_Config_ColumnCollection(array($column1, $column2));
        $cardincell_presenter1 = \Mockery::spy(\Cardwall_CardInCellPresenter::class)->shouldReceive('getArtifact')->andReturns($artifact1)->getMock();
        $cardincell_presenter2 = \Mockery::spy(\Cardwall_CardInCellPresenter::class)->shouldReceive('getArtifact')->andReturns($artifact2)->getMock();

        $this->config->shouldReceive('isInColumn')->with($artifact1, \Mockery::any(), $column1)->andReturns(true);
        $this->config->shouldReceive('isInColumn')->with($artifact1, \Mockery::any(), $column2)->andReturns(false);
        $this->config->shouldReceive('isInColumn')->with($artifact2, \Mockery::any(), $column1)->andReturns(false);
        $this->config->shouldReceive('isInColumn')->with($artifact2, \Mockery::any(), $column2)->andReturns(true);

        $swimlines = $this->factory->getCells($columns, array($cardincell_presenter1, $cardincell_presenter2));
        $expected  = array(
                        array('column_id' => 55, 'column_stacked' => true, 'cardincell_presenters' => array($cardincell_presenter1)),
                        array('column_id' => 100, 'column_stacked' => true, 'cardincell_presenters' => array($cardincell_presenter2)));
        $this->assertSame($expected, $swimlines);
    }

    public function testItIgnoresPresentersIfThereIsNoMatchingColumn() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $column = new Cardwall_Column(55, null, null);
        $columns  = new Cardwall_OnTop_Config_ColumnCollection();
        $columns[] = $column;
        $cardincell_presenter = \Mockery::spy(\Cardwall_CardInCellPresenter::class)->shouldReceive('getArtifact')->andReturns($artifact)->getMock();

        $this->config->shouldReceive('isInColumn')->with($artifact, \Mockery::any(), $column)->andReturns(false);

        $swimlines = $this->factory->getCells($columns, array($cardincell_presenter));
        $expected  = array(
                        array('column_id' => 55, 'column_stacked' => true, 'cardincell_presenters' => array()));
        $this->assertSame($expected, $swimlines);
    }
}

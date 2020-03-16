<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class CardInCellPresenterFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $tracker        = \Mockery::spy(\Tracker::class);
        $this->field_id = 77777;
        $this->field    = Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class)
            ->shouldReceive("getId")
            ->andReturns($this->field_id)
            ->getMock();

        $this->artifact = Mockery::spy(Tracker_Artifact::class)
            ->shouldReceive('getTracker')
            ->andReturn($tracker)
            ->getMock();

        $this->card_presenter = Mockery::spy(\Cardwall_CardPresenter::class)
            ->shouldReceive('getArtifact')
            ->andReturns($this->artifact)
            ->getMock();

        $this->field_provider = Mockery::spy(\Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class)
            ->shouldReceive('getField')
            ->with($tracker)
            ->andReturns($this->field)
            ->getMock();
    }

    public function testItHasACardInCellPresenterWithASemanticStatusFieldId(): void
    {
        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, new Cardwall_MappingCollection());
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        $this->assertEquals(
            $cell_presenter,
            new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id)
        );
    }

    public function testItHasACardInCellPresenterWithSwimLineId(): void
    {
        $swimline_id = 112;
        $this->card_presenter->shouldReceive('getSwimlineId')->andReturns($swimline_id);

        $mapping_collection = new Cardwall_MappingCollection();

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, $mapping_collection);
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        $this->assertEquals(
            $cell_presenter,
            new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id, $swimline_id)
        );
    }

    public function testItHasACardInCellPresenterWithSwimLineValueCollection(): void
    {
        $swimline_id = 112;
        $this->card_presenter->shouldReceive('getSwimlineId')->andReturns($swimline_id);

        $mapping_collection = Mockery::mock(\Cardwall_MappingCollection::class)
            ->shouldReceive('getSwimLineValues')
            ->with($this->field_id)
            ->andReturns(array(123, 456))
            ->getMock();

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, $mapping_collection);
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        $this->assertEquals(
            $cell_presenter,
            new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id, $swimline_id, array(123, 456))
        );
    }
}

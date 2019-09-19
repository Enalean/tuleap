<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

class CardInCellPresenterFactoryTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $tracker        = \Mockery::spy(\Tracker::class);
        $this->field_id = 77777;
        $this->field    = mockery_stub(\Tracker_FormElement_Field_MultiselectBox::class)->getId()->returns($this->field_id);
        $this->artifact = aMockArtifact()->withTracker($tracker)->build();

        $this->card_presenter       = mockery_stub(\Cardwall_CardPresenter::class)->getArtifact()->returns($this->artifact);

        $this->field_provider = mockery_stub(\Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class)->getField($tracker)->returns($this->field);
    }

    public function itHasACardInCellPresenterWithASemanticStatusFieldId()
    {
        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, new Cardwall_MappingCollection());
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        $this->assertEqual(
            $cell_presenter,
            new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id)
        );
    }

    public function itHasACardInCellPresenterWithSwimLineId()
    {
        $swimline_id = 112;
        stub($this->card_presenter)->getSwimlineId()->returns($swimline_id);

        $mapping_collection = new Cardwall_MappingCollection();

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, $mapping_collection);
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        $this->assertEqual(
            $cell_presenter,
            new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id, $swimline_id)
        );
    }

    public function itHasACardInCellPresenterWithSwimLineValueCollection()
    {
        $swimline_id = 112;
        stub($this->card_presenter)->getSwimlineId()->returns($swimline_id);

        $mapping_collection = mockery_stub(\Cardwall_MappingCollection::class)->getSwimLineValues($this->field_id)->returns(array(123, 456));

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, $mapping_collection);
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        $this->assertEqual(
            $cell_presenter,
            new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id, $swimline_id, array(123, 456))
        );
    }
}

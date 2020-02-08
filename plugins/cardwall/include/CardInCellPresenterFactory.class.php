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

class Cardwall_CardInCellPresenterFactory
{

    /**
     * @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
     */
    private $field_provider;

    /**
     * @var Cardwall_MappingCollection
     */
    private $mappings;


    public function __construct(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider, Cardwall_MappingCollection $mappings)
    {
        $this->field_provider = $field_provider;
        $this->mappings       = $mappings;
    }

    /**
     * Instanciate a new Cardwall_CardInCellPresenter
     *
     *
     * @return Cardwall_CardInCellPresenter
     */
    public function getCardInCellPresenter(Cardwall_CardPresenter $card_presenter)
    {
        $card_field_id    = $this->getFieldId($card_presenter);
        $swim_line_values = $this->mappings->getSwimLineValues($card_field_id);
        return new Cardwall_CardInCellPresenter($card_presenter, $card_field_id, $card_presenter->getSwimlineId(), $swim_line_values);
    }

    private function getFieldId(Cardwall_CardPresenter $card_presenter)
    {
        $artifact = $card_presenter->getArtifact();
        $field    = $this->field_provider->getField($artifact->getTracker());
        return $field ? $field->getId() : 0;
    }
}

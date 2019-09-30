<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

/**
 * This class is a wrapper on top of card in cell presenter to be used when
 * a card is rendered out of the board context.
 *
 * In other words it's what you need when you only want to have one single card
 */
class Cardwall_SingleCard
{

    /** @var Cardwall_CardInCellPresenter */
    private $card_in_cell_presenter;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Cardwall_CardFields */
    private $card_fields;

    /** @var Cardwall_UserPreferences_UserPreferencesDisplayUser */
    private $display_preferences;

    /** @var int */
    private $column_id;

    /** @var Cardwall_OnTop_Config_TrackerMapping | null */
    private $mapping;

    /** @var array */
    private $fields_cache = null;

    public function __construct(
        Cardwall_CardInCellPresenter $card_in_cell_presenter,
        Cardwall_CardFields $card_fields,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        $column_id,
        ?Cardwall_OnTop_Config_TrackerMapping $mapping
    ) {
        $this->card_in_cell_presenter = $card_in_cell_presenter;
        $this->artifact               = $card_in_cell_presenter->getArtifact();
        $this->card_fields            = $card_fields;
        $this->display_preferences    = $display_preferences;
        $this->column_id              = $column_id;
        $this->mapping                = $mapping;
    }

    /**
     * @return Cardwall_CardInCellPresenter
     */
    public function getCardInCellPresenter()
    {
        return $this->card_in_cell_presenter;
    }

    public function getColumnId()
    {
        return $this->column_id;
    }

    public function getFields()
    {
        return $this->card_fields->getFields($this->artifact);
    }

    /**
     * Return a field on the card based on the card
     *
     * @param int $id
     *
     * @return Tracker_FormElement_Field
     */
    public function getFieldById($id)
    {
        if ($this->fields_cache === null) {
            foreach ($this->getFields() as $field) {
                $this->fields_cache[$field->getId()] = $field;
            }
        }
        if (isset($this->fields_cache[$id])) {
            return $this->fields_cache[$id];
        }
        throw new Cardwall_FieldNotOnCardException($id);
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    public function getFieldJsonValue(PFUser $user, Tracker_FormElement_Field $field)
    {
        return $field->getJsonValue($user, $this->artifact->getLastChangeset());
    }

    public function getFieldHTMLValue(PFUser $user, Tracker_FormElement_Field $field)
    {
        return $field->fetchCardValue($this->artifact, $this->display_preferences);
    }

    public function getMapping()
    {
        return $this->mapping;
    }
}

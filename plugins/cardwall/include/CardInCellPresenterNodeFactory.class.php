<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * Builds instances of CardInCellPresenterNode
 */
class Cardwall_CardInCellPresenterNodeFactory {

    /** @var Cardwall_CardInCellPresenterFactory */
    private $card_in_cell_presenter_factory;

    /** @var Cardwall_CardFields */
    private $card_fields;

    /** @var Cardwall_UserPreferences_UserPreferencesDisplayUser */
    private $display_preferences;

    /** @var PFUser */
    private $user;

    function __construct(
        Cardwall_CardInCellPresenterFactory $card_in_cell_presenter_factory,
        Cardwall_CardFields $card_fields,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        PFUser $user
    ) {
        $this->card_in_cell_presenter_factory = $card_in_cell_presenter_factory;
        $this->card_fields                    = $card_fields;
        $this->display_preferences            = $display_preferences;
        $this->user                           = $user;
    }

    /**
     * @return Cardwall_CardInCellPresenterNode
     */
    public function getCardInCellPresenterNode(Tracker_Artifact $artifact, $swimline_id = 0) {
        $presenter = $this->card_in_cell_presenter_factory->getCardInCellPresenter(
            $this->getCardPresenter($artifact, $swimline_id)
        );

        return new Cardwall_CardInCellPresenterNode($presenter);
    }

    private function getCardPresenter(Tracker_Artifact $artifact, $swimline_id) {
        $color = $artifact->getCardAccentColor($this->user);

        return new Cardwall_CardPresenter(
            $artifact,
            $this->card_fields,
            $color,
            $this->display_preferences,
            $swimline_id,
            $artifact->getParent($this->user)
        );
    }
}

?>

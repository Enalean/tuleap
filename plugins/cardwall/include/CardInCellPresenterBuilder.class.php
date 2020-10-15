<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Tuleap\Cardwall\AccentColor\AccentColorBuilder;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * Builds instances of CardInCellPresenterNode
 */
class Cardwall_CardInCellPresenterBuilder
{
    /** @var Cardwall_CardInCellPresenterFactory */
    private $card_in_cell_presenter_factory;

    /** @var Cardwall_CardFields */
    private $card_fields;

    /** @var Cardwall_UserPreferences_UserPreferencesDisplayUser */
    private $display_preferences;

    /** @var PFUser */
    private $user;

    /** @var BackgroundColorBuilder */
    private $background_color_builder;

    /** @var AccentColorBuilder */
    private $accent_color_builder;

    public function __construct(
        Cardwall_CardInCellPresenterFactory $card_in_cell_presenter_factory,
        Cardwall_CardFields $card_fields,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        PFUser $user,
        BackgroundColorBuilder $background_color_builder,
        AccentColorBuilder $accent_color_builder
    ) {
        $this->card_in_cell_presenter_factory = $card_in_cell_presenter_factory;
        $this->card_fields                    = $card_fields;
        $this->display_preferences            = $display_preferences;
        $this->user                           = $user;
        $this->background_color_builder       = $background_color_builder;
        $this->accent_color_builder           = $accent_color_builder;
    }

    /**
     * @return Cardwall_CardInCellPresenter
     */
    public function getCardInCellPresenter(Artifact $artifact, $swimline_id = 0)
    {
        return $this->card_in_cell_presenter_factory->getCardInCellPresenter(
            $this->getCardPresenter($artifact, $swimline_id)
        );
    }

    /**
     * @return Cardwall_CardInCellPresenter[]
     */
    public function getCardInCellPresenters(array $artifacts, $swimline_id = 0)
    {
        $presenters = [];

        foreach ($artifacts as $artifact) {
            $presenters[] = $this->getCardInCellPresenter($artifact, $swimline_id);
        }

        return $presenters;
    }

    private function getCardPresenter(Artifact $artifact, $swimline_id)
    {
        $color                = $this->accent_color_builder->build($artifact, $this->user);
        $card_fields_semantic = Cardwall_Semantic_CardFields::load($artifact->getTracker());
        $background_color     = $this->background_color_builder->build($card_fields_semantic, $artifact, $this->user);

        return new Cardwall_CardPresenter(
            $this->user,
            $artifact,
            $this->card_fields,
            $color,
            $this->display_preferences,
            $swimline_id,
            $artifact->getAllowedChildrenTypesForUser($this->user),
            $background_color,
            $artifact->getParent($this->user)
        );
    }
}

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


require_once 'common/mvc2/PluginController.class.php';

class Cardwall_CardController extends MVC2_PluginController {

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Cardwall_CardFields */
    private $card_fields;

    /** @var Cardwall_UserPreferences_UserPreferencesDisplayUser */
    private $display_preferences;

    /** @var Cardwall_CardInCellPresenterFactory */
    private $presenter_factory;

    /** @var Cardwall_OnTop_Config_ColumnCollection */
    private $columns;

    /** @var Cardwall_OnTop_Config */
    private $config;

    /** @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact */
    private $field_provider;

    public function __construct(
        Codendi_Request $request,
        Tracker_Artifact $artifact,
        Cardwall_CardFields $card_fields,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        Cardwall_OnTop_Config $config,
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_CardInCellPresenterFactory $presenter_factory,
        Cardwall_OnTop_Config_ColumnCollection $columns
    ) {
        parent::__construct('cardwall', $request);
        $this->artifact            = $artifact;
        $this->card_fields         = $card_fields;
        $this->display_preferences = $display_preferences;
        $this->config              = $config;
        $this->field_provider      = $field_provider;
        $this->presenter_factory   = $presenter_factory;
        $this->columns             = $columns;
    }

    public function getCard() {
        $card_in_cell_presenter = $this->getCardInCellPresenter($this->artifact);
        $card_presenter = $card_in_cell_presenter->getCardPresenter();

        $json_format = array(
            $this->artifact->getId() => array(
                'title'        => $card_presenter->getTitle(),
                'xref'         => $card_presenter->getXRef(),
                'edit_url'     => $card_presenter->getEditUrl(),
                'accent_color' => $card_presenter->getAccentColor(),
                'swimline_id'  => $card_presenter->getSwimlineId(),
                'column_id'    => $this->getColumnId(),
                'drop_into'    => $card_in_cell_presenter->getDropIntoClasses(),
                'fields'       => array(),
                'html_fields'  => array(),
            ),
        );
        foreach ($this->card_fields->getFields($this->artifact) as $field) {
            $this->addJsonFieldValues($json_format[$this->artifact->getId()], $field);
            $this->addHTMLFieldValues($json_format[$this->artifact->getId()], $field);
        }

        $GLOBALS['Response']->sendJSON($json_format);
    }

    private function addJsonFieldValues(&$json_format, $field) {
        $json_format['fields'][$field->getName()] = $field->getJsonValue($this->request->getCurrentUser(), $this->artifact->getLastChangeset());
    }

    private function addHTMLFieldValues(&$json_format, $field) {
        $json_format['html_fields'][$field->getName()] = $field->fetchCardValue($this->artifact, $this->display_preferences);
    }

    /**
     * @return Cardwall_CardPresenter
     */
    protected function getCardPresenter() {
        $user            = $this->request->getCurrentUser();
        $parent_artifact = $this->artifact->getParent($user);
        $swimline_id     = 0;
        if ($parent_artifact) {
            $swimline_id = $parent_artifact->getId();
        }
        return new Cardwall_CardPresenter(
            $this->artifact,
            $this->card_fields,
            $this->artifact->getCardAccentColor($user),
            $this->display_preferences,
            $this->artifact->getAllowedChildrenTypesForUser($user),
            $swimline_id,
            $parent_artifact
        );
    }

    /**
     * @return Cardwall_CardInCellPresenter
     */
    protected function getCardInCellPresenter() {
        return $this->presenter_factory->getCardInCellPresenter($this->getCardPresenter($this->artifact));
    }

    private function getColumnId() {
        foreach ($this->columns as $column) {
            if ($this->config->isInColumn($this->artifact, $this->field_provider, $column)) {
                return $column->getId();
            }
        }
        return -1;
    }
}

?>

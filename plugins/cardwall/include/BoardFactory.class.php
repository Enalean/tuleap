<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * Builds Board given artifacts (for swimlines/cards) and a field (for columns)
 */
class Cardwall_BoardFactory {

    /**
     * @return Cardwall_Board
     */
    public function getBoard(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_retriever, 
                             Cardwall_OnTop_Config_ColumnCollection               $columns, 
                             TreeNode                                             $forests_of_artifacts, 
                             Cardwall_OnTop_Config                                $config,
                             PFUser                                               $user,
                             Cardwall_DisplayPreferences                          $display_preferences) {
        $acc_field_provider = new Cardwall_FieldsExtractor($field_retriever);
        $status_fields      = $acc_field_provider->extractAndIndexFieldsOf($forests_of_artifacts);
        
        $mapping_collection = $config->getCardwallMappings($status_fields, $columns);
        $forests_of_cardincell_presenters = $this->transformIntoForestOfCardInCellPresenters($forests_of_artifacts, $field_retriever, $mapping_collection, $user, $display_preferences);
        $swimlines                        = $this->getSwimlines($columns, $forests_of_cardincell_presenters, $config, $field_retriever);

        return new Cardwall_Board($swimlines, $columns, $mapping_collection);
        
    }

    private function transformIntoForestOfCardInCellPresenters($forests_of_artifacts, $field_retriever, $mapping_collection, PFUser $user, Cardwall_DisplayPreferences $display_preferences) {
        
        $card_presenter_mapper      = new TreeNodeMapper(new Cardwall_CreateCardPresenterCallback(new Tracker_CardFields(), $user, $display_preferences));
        $forests_of_card_presenters = $card_presenter_mapper->map($forests_of_artifacts);

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($field_retriever, $mapping_collection);
        $column_id_mapper               = new TreeNodeMapper(new Cardwall_CardInCellPresenterCallback($card_in_cell_presenter_factory));
        return $column_id_mapper->map($forests_of_card_presenters);
    }

    private function getSwimlines(Cardwall_OnTop_Config_ColumnCollection $columns, TreeNode $forests_of_cardincell_presenters, $config, $field_provider) {
        $swimline_factory = new Cardwall_SwimlineFactory($config, $field_provider);
        return $swimline_factory->getSwimlines($columns, $forests_of_cardincell_presenters->getChildren());
    }

}
?>

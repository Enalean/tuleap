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
    public function getBoard(
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_OnTop_Config_ColumnCollection $columns,
        TreeNode $forests_of_artifacts,
        Cardwall_OnTop_Config $config,
        PFUser $user,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        $mapping_collection
    ) {
        $swimlines = $this->getSwimlines($columns, $forests_of_artifacts, $config, $field_provider);

        return new Cardwall_Board($swimlines, $columns, $mapping_collection);
    }

    private function getSwimlines(Cardwall_OnTop_Config_ColumnCollection $columns, TreeNode $forests_of_cardincell_presenters, $config, $field_provider) {
        $swimline_factory = new Cardwall_SwimlineFactory($config, $field_provider);
        return $swimline_factory->getSwimlines($columns, $forests_of_cardincell_presenters->getChildren());
    }

}
?>

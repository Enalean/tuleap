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

class Cardwall_UserPreferences_UserPreferencesAutostackFactory
{

    public function setAutostack(Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_UserPreferences_UserPreferencesAutostack $autostack_preferences)
    {
        $cardwall_has_preferences = false;
        foreach ($columns as $column) {
            $cardwall_has_preferences = $cardwall_has_preferences || $autostack_preferences->columnHasPreference($column);
            $autostack_preferences->setColumnPreference($column);
        }

        if (! $cardwall_has_preferences) {
            $this->forceAutoStackOnDone($columns, $autostack_preferences);
        }
    }

    private function forceAutoStackOnDone(Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_UserPreferences_UserPreferencesAutostack $autostack_preferences)
    {
        foreach ($columns as $column) {
            if (strcasecmp($column->label, 'done') == 0) {
                $autostack_preferences->forceColumnAutoStacked($column);
            }
        }
    }
}

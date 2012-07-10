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

require_once 'ColumnDefinition.class.php';

class Cardwall_View_Admin_FreestyleColumnDefinition extends Cardwall_View_Admin_ColumnDefinition {

    protected function fetchSpeech() {
        $html = '';
        if (! $this->config->getColumns()) {
            $html .= '<p>'. 'There is no semantic status defined for this tracker. Therefore you must configure yourself the columns used for cardwall.' .'</p>';
        }
        return $html;
    }

    protected function fetchColumnHeader(Cardwall_OnTop_Config_Column $column) {
        return '<input type="text" name="column['. $column->id .'][label]" value="'. $this->purify($column->label) .'" />';
    }

    protected function fetchAdditionalColumnHeader() {
        return '<label>'. 'New column:'. '<br /><input type="text" name="new_column" value="" placeholder="'. 'Eg: On Going' .'" /></label>';
    }
}
?>

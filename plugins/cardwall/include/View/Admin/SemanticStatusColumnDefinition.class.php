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

class Cardwall_View_Admin_SemanticStatusColumnDefinition extends Cardwall_View_Admin_ColumnDefinition {

    protected function fetchSpeech() {
        $field    = $this->config->getTracker()->getStatusField();

        $html  = '';
        $html .= '<p>'. 'The column used for the cardwall will be bound to the current status field ('. $this->purify($field->getLabel()) .') of this tracker.' .'</p>';

        $html .= '<p>'. 'TODO: Maybe you wanna choose your own set of columns?' .'</p>';
        $html .= '<p>'. 'TODO: Or else we may have to disable the edition of the column labels' .'</p>';

        return $html;
    }

    protected function fetchColumnsHeader(array $columns) {
        $html = '';
        foreach ($columns as $column) {
            $html .= '<th style="">';
            $html .= $this->purify($column->label);
            $html .= '</th>';
        }
        $html .= '<td>';
        $html .= '</td>';
        return $html;
    }
}
?>

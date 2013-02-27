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


class Cardwall_OnTop_Config_View_FreestyleColumnDefinition extends Cardwall_OnTop_Config_View_ColumnDefinition {

    protected function fetchSpeech() {
        if (! count($this->config->getDashboardColumns())) {
            return $this->translate('plugin_cardwall', 'on_top_semantic_freestyle_column_definition_speech_no_column');
        } else {
            return $this->translate('plugin_cardwall', 'on_top_semantic_freestyle_column_definition_speech_with_columns');
        }
    }

    protected function fetchColumnHeader(Cardwall_Column $column) {
        return '<input type="text" name="column['. $column->id .'][label]" value="'. $this->purify($column->label) .'" />';
    }

    protected function fetchAdditionalColumnHeader() {
        $suggestion = $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_column_placeholder_suggestion', $this->getPlaceholderSuggestion());
        return '<label>'. $this->translate('plugin_cardwall', 'on_top_new_column') . '<br /><input type="text" name="new_column" value="" placeholder="'. $suggestion  .'" /></label>';
    }

    /**
     * @return string
     */
    private function getPlaceholderSuggestion() {
        $placeholders = explode('|', $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_column_placeholders'));
        foreach ($this->config->getDashboardColumns() as $column) {
            array_walk($placeholders, array($this, 'removeUsedColumns'), $column->getLabel());
        }
        $suggestion = array_shift(array_filter($placeholders));
        return $suggestion ? $suggestion : $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_column_placeholder_default');
    }

    private function removeUsedColumns(&$placeholder, $key, $column_label) {
        if (! levenshtein(soundex($column_label), soundex($placeholder))) {
            $placeholder = '';
        }
    }
}
?>

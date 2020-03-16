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
 * Display the admin of the Cardwall
 */
class Cardwall_OnTop_Config_View_Admin
{

    public function displayAdminOnTop(Cardwall_OnTop_Config $config)
    {
        return $this->generateAdminForm($config);
    }

    private function generateAdminForm($config)
    {
        $column_definition_view = new Cardwall_OnTop_Config_View_ColumnDefinition($config);
        $checked                = $config->isEnabled() ? 'checked="checked"' : '';

        $html  = '<p>';
        $html .= '<input type="hidden" name="cardwall_on_top" value="0" />';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="cardwall_on_top" value="1" id="cardwall_on_top" ' . $checked . '/> ';
        $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_label');
        $html .= '</label>';
        $html .= '</p>';
        $html .= '<input type="hidden" name="update_cardwall" value="1" />';

        if ($checked) {
            $html .= '<blockquote>';
            $html .= $column_definition_view->fetchColumnDefinition();
            $html .= '</blockquote>';
        }

        return $html;
    }

    public function visitColumnFreestyleCollection($collection, Cardwall_OnTop_Config $config)
    {
        return new Cardwall_OnTop_Config_View_ColumnDefinition($config);
    }
}

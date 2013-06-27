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


class Cardwall_View_Admin_Form extends Cardwall_View {

    /** @var Cardwall_View_Admin_ColumnDefinition */
    private $subview;
    
    public function __construct(Cardwall_OnTop_Config_View_ColumnDefinition $column_definition_view) {
        parent::__construct();
        $this->subview = $column_definition_view;
    }
    
    private function urlForAdminUpdate($tracker_id) {
        return TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&amp;func=admin-cardwall-update';
    }

    public function displayAdminForm($token_html, $checked, $freestyle_checked, $tracker_id) {
        echo $this->generateAdminForm($token_html, $checked, $freestyle_checked, $tracker_id);
    }

    private function generateAdminForm($token_html, $checked, $freestyle_checked, $tracker_id) {
        $update_url = $this->urlForAdminUpdate($tracker_id);

        $html  = '';
        $html .= '<form action="'.$update_url .'" METHOD="POST">';
        $html .= $token_html;
        $html .= '<p>';
        $html .= '<input type="hidden" name="cardwall_on_top" value="0" />';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="cardwall_on_top" value="1" id="cardwall_on_top" '. $checked .'/> ';
        $html .= $this->translate('plugin_cardwall', 'on_top_label');
        $html .= '</label>';
        $html .= '</p>';
        if ($checked) {
            $html .= '<input type="hidden" name="use_freestyle_columns" value="0" />';
            $html .= '<blockquote>';
            $html .= '<label class="checkbox">';
            $html .= '<input type="checkbox" name="use_freestyle_columns" value="1" '.$freestyle_checked.'/> ';
            $html .= $this->translate('plugin_cardwall', 'on_top_use_freestyle');
            $html .= '</label>';
            $html .= $this->subview->fetchColumnDefinition();
            $html .= '</blockquote>';
        }
        $html .= '<input type="submit" value="'. $this->translate('global', 'btn_submit') .'" />';
        $html .= '</form>';
        return $html;
    }
}
?>

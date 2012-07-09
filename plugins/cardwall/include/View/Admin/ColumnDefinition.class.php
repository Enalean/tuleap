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

require_once CARDWALL_BASE_DIR .'/View.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/MappingFieldValueCollection.class.php';

abstract class Cardwall_View_Admin_ColumnDefinition extends Cardwall_View {

    /**
     * @var array of Cardwall_OnTop_Config
     */
    protected $config;

    public function __construct(Cardwall_OnTop_Config $config) {
        parent::__construct();
        $this->config = $config;
    }

    public abstract function fetchColumnDefinition();

    protected function fetchMappings() {
        $html  = '';
        $html .= '<table class="cardwall_admin_ontop_mappings"><thead><tr valign="bottom">';
        $html .= '<td></td>';
        foreach ($this->config->getColumns() as $column) {
            $html .= '<td>';
            $html .= '<input type="text" name="column['. $column->id .'][label]" value="'. $this->purify($column->label) .'" />';
            $html .= '</td>';
        }
        $html .= '<td>';
        $html .= '<label>'. 'New column:'. '<br /><input type="text" name="new_column" value="" placeholder="'. 'Eg: On Going' .'" /></label>';
        $html .= '</td>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        $row_number = 0;
        foreach ($this->config->getMappings() as $mapping) {
            $html .= $mapping->accept($this, $row_number++);
            //$html .= $view->listExistingMappings($row_number, $mapping);
        }

        $html .= '</tbody></table>';

        return $html;
    }

    public function visitTrackerMapping($mapping, $row_number) {
        $mapping_tracker= $mapping->getTracker();
        $used_sb_fields = $mapping->getAvailableFields();
        $field          = $mapping->getField();
        $mapping_values = $mapping->getValueMappings();

        $html  = '<tr class="'. html_get_alt_row_color($row_number + 1) .'" valign="top">';
        $html .= '<td class="not-freestyle">';
        $html .= $this->purify($mapping_tracker->getName()) .'<br />';
        $disabled = $field ? 'disabled="disabled"' : '';
        $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][field]" '. $disabled .'>';
        if (!$field) {
            $html .= '<option value="">'. $this->translate('global', 'please_choose_dashed') .'</option>';
        }
        foreach ($used_sb_fields as $sb_field) {
            $selected = $field == $sb_field ? 'selected="selected"' : '';
            $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $this->purify($sb_field->getLabel()) .'</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        foreach ($this->config->getColumns() as $column) {
            $html .= '<td>';
            $value = $mapping->getSelectedValueLabel($column);
            $html .= '</td>';
        }
        $html .= '<td>';
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
        return;
    }

    public function visitTrackerMappingFreestyle($mapping, $row_number) {
        $mapping_tracker= $mapping->getTracker();
        $used_sb_fields = $mapping->getAvailableFields();
        $field          = $mapping->getField();
        $mapping_values = $mapping->getValueMappings();

        $html  = '<tr class="'. html_get_alt_row_color($row_number + 1) .'" valign="top">';
        $html .= '<td>';
        $html .= $this->purify($mapping_tracker->getName()) .'<br />';
        $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][field]">';
        if (!$field) {
            $html .= '<option value="">'. $this->translate('global', 'please_choose_dashed') .'</option>';
        }
        foreach ($used_sb_fields as $sb_field) {
            $selected = $field == $sb_field ? 'selected="selected"' : '';
            $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $this->purify($sb_field->getLabel()) .'</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        foreach ($this->config->getColumns() as $column) {
            $column_id = $column->id;
            $html .= '<td>';
            if ($field) {
                $field_values = $field->getVisibleValuesPlusNoneIfAny();
                if ($field_values) {
                    $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][values]['. $column_id .'][]" multiple="multiple" size="'. count($field_values) .'">';
                    foreach ($field_values as $value) {
                        $selected = '';
                        // if ($mapping_values->isSelected($value, $column)) {
                        //     $selected = 'selected="selected"';
                        // }
                        if (isset($mapping_values[$value->getId()]) && $mapping_values[$value->getId()]->getColumnId() == $column_id) {
                            $selected = 'selected="selected"';
                        }
                        $html .= '<option value="'. $value->getId() .'" '. $selected .'>'. $value->getLabel() .'</option>';
                    }
                    $html .= '</select>';
                } else {
                    $html .= '<em>'. "There isn't any value" .'</em>';
                }
            }
            $html .= '</td>';
        }
        $html .= '<td>';
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }
}
?>

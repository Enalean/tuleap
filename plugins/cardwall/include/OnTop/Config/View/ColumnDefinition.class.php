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

abstract class Cardwall_OnTop_Config_View_ColumnDefinition extends Cardwall_View {

    /**
     * @var array of Cardwall_OnTop_Config
     */
    protected $config;

    public function __construct(Cardwall_OnTop_Config $config) {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function fetchColumnDefinition() {
        $html  = '';
        $html .= $this->fetchSpeech();
        $html .= $this->fetchMappings();
        return $html;
    }

    /**
     * @return string
     */
    protected abstract function fetchSpeech();

    /**
     * @return string
     */
    protected abstract function fetchColumnHeader(Cardwall_Column $column);

    /**
     * @return string
     */
    protected abstract function fetchAdditionalColumnHeader();

    private function fetchMappings() {
        $html  = '';
        $html .= '<table class="cardwall_admin_ontop_mappings"><thead><tr valign="bottom">';
        $html .= '<td></td>';
        foreach ($this->config->getDashboardColumns() as $column) {
            $html .= '<th style="background-color: '. $column->bgcolor .'; color: '. $column->fgcolor .';">';
            $html .= $this->fetchColumnHeader($column);
            $html .= '</th>';
        }
        $html .= '<td>';
        $html .= $this->fetchAdditionalColumnHeader();
        $html .= '</td>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        $row_number = 0;
        foreach ($this->config->getMappings() as $mapping) {
            $html .= '<tr class="'. html_get_alt_row_color(++$row_number) .'" valign="top">';
            $html .= $mapping->accept($this);
            $html .= '<td>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    public function visitTrackerMappingNoField($mapping) {
        $mapping_tracker= $mapping->getTracker();
        $used_sb_fields = $mapping->getAvailableFields();

        $html  = '';
        $html .= '<td>';
        $html .= '<a href="/plugins/tracker/?tracker='. $mapping_tracker->getId() .'&func=admin">'. $this->purify($mapping_tracker->getName()) .'</a><br />';
        $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][field]" disabled="disabled">';
        $html .= '<option value="">'. $this->translate('global', 'please_choose_dashed') .'</option>';
        foreach ($used_sb_fields as $sb_field) {
            $html .= '<option value="'. (int)$sb_field->getId() .'">'. $this->purify($sb_field->getLabel()) .'</option>';
        }
        $html .= '</select>';
        $html .= $this->fetchCustomizationSwitch($mapping_tracker);
        $html .= '</td>';
        foreach ($this->config->getDashboardColumns() as $column) {
            $html .= '<td>';
            $html .= '&nbsp;';
            $html .= '</td>';
        }
        return $html;
    }

    public function visitTrackerMappingStatus($mapping) {
        $mapping_tracker= $mapping->getTracker();
        $used_sb_fields = $mapping->getAvailableFields();
        $field          = $mapping->getField();
        $mapping_values = $mapping->getValueMappings();

        $html  = '';
        $html .= '<td class="not-freestyle">';
        $html .= '<a href="/plugins/tracker/?tracker='. $mapping_tracker->getId() .'&func=admin">'. $this->purify($mapping_tracker->getName()) .'</a><br />';
        $disabled = $field ? 'disabled="disabled"' : '';
        $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][field]" '. $disabled .'>';
        foreach ($used_sb_fields as $sb_field) {
            $selected = $field == $sb_field ? 'selected="selected"' : '';
            $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $this->purify($sb_field->getLabel()) .'</option>';
        }
        $html .= '</select>';
        $html .= $this->fetchCustomizationSwitch($mapping_tracker);
        $html .= '</td>';
        foreach ($this->config->getDashboardColumns() as $column) {
            $html .= '<td>';
            $html .= $mapping->getSelectedValueLabel($column, '<em>'.$this->translate('plugin_cardwall', 'on_top_no_matching_for_column').'</em>');
            $html .= '</td>';
        }
        return $html;
        return;
    }

    public function visitTrackerMappingFreestyle($mapping) {
        $mapping_tracker= $mapping->getTracker();
        $used_sb_fields = $mapping->getAvailableFields();
        $field          = $mapping->getField();
        $mapping_values = $mapping->getValueMappings();

        $html  = '';
        $html .= '<td>';
        $html .= '<a href="/plugins/tracker/?tracker='. $mapping_tracker->getId() .'&func=admin">'. $this->purify($mapping_tracker->getName()) .'</a><br />';
        $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][field]">';
        $html .= '<option value="">'. $this->translate('global', 'please_choose_dashed') .'</option>';
        foreach ($used_sb_fields as $sb_field) {
            $selected = $field == $sb_field ? 'selected="selected"' : '';
            $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $this->purify($sb_field->getLabel()) .'</option>';
        }
        $html .= '</select>';
        $html .= $this->fetchCustomizationSwitch($mapping_tracker, true);
        $html .= '</td>';
        foreach ($this->config->getDashboardColumns() as $column) {
            $html .= '<td>';
            $html .= $this->editValues($mapping_tracker, $column, $mapping_values, $field);
            $html .= '</td>';
        }
        return $html;
    }

    private function fetchCustomizationSwitch(Tracker $mapping_tracker, $customized=false) {
        $html     = '';
        $selected = '';
        if ($customized) {
            $selected = 'checked="checked"';
        }
        $name = 'custom_mapping['.(int)$mapping_tracker->getId() .']';
        $html .= '<p>';
        $html .= '<input type="hidden" name="'. $name .'" value="0" />';
        $html .= '<label><input type="checkbox" name="'. $name .'" '.$selected.' value="1" /> '.$this->translate('plugin_cardwall', 'on_top_custom_mapping').'</label>';
        $html .= '</p>';
        return $html;
    }

    private function editValues($mapping_tracker, $column, $mapping_values, $field) {
        $column_id = $column->id;
        $field_values = $field->getVisibleValuesPlusNoneIfAny();
        $html = '';
        if ($field_values) {
            $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][values]['. $column_id .'][]" multiple="multiple" size="'. count($field_values) .'">';
            foreach ($field_values as $value) {
                $selected = '';
                
                //TODO rather use the TrackerMapping and ask it some question, cannot rely on the fact that this array is indexed
                // on value->getId(), for instance in the case of status mappings it ain't!
                if (isset($mapping_values[$value->getId()]) && $mapping_values[$value->getId()]->getColumnId() == $column_id) {
                    $selected = 'selected="selected"';
                }
                $html .= '<option value="'. $value->getId() .'" '. $selected .'>'. $value->getLabel() .'</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<em>'. $this->translate('plugin_cardwall', 'on_top_no_values') .'</em>';
        }

        return $html;

    }
}
?>

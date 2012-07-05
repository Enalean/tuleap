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
class Cardwall_AdminView {

    public function displayAdminOnTop(Tracker $tracker,
                                       Tracker_IDisplayTrackerLayout $layout,
                                       TrackerFactory $tracker_factory,
                                       Tracker_FormElementFactory $element_factory,
                                       CSRFSynchronizerToken $token,
                                       $ontop_dao,
                                       $column_dao,
                                       $mappings_dao) {
        $tracker->displayAdminItemHeader($layout, 'plugin_cardwall');
        $tracker_id = $tracker->getId();
        $checked    = $ontop_dao->isEnabled($tracker_id) ? 'checked="checked"' : '';
        $token_html = $token->fetchHTMLInput();
        
        $html  = '';
        $html .= '<form action="'. TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&amp;func=admin-cardwall-update' .'" METHOD="POST">';
        $html .= $token_html;
        $html .= '<p>';
        $html .= '<input type="hidden" name="cardwall_on_top" value="0" />';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="cardwall_on_top" value="1" id="cardwall_on_top" '. $checked .'/> ';
        $html .= $this->translate('plugin_cardwall', 'on_top_label');
        $html .= '</label>';
        $html .= '</p>';
        if ($checked) {
            $html .= '<blockquote>';
            $html .= $this->fetchColumnDefinition($tracker, $tracker_factory, $element_factory, $column_dao, $mappings_dao);
            $html .= '</blockquote>';
        }
        $html .= '<input type="submit" value="'. $this->translate('global', 'btn_submit') .'" />';
        $html .= '</form>';
        echo $html;
        $tracker->displayFooter($layout);
    }

    private function fetchColumnDefinition(Tracker $tracker, TrackerFactory $tracker_factory, Tracker_FormElementFactory $element_factory,
                                           $column_dao,
                                           $mappings_dao) {
        $hp       = Codendi_HTMLPurifier::instance();
        $html     = '';
        $trackers = $tracker_factory->getTrackersByGroupId($tracker->getGroupId());
        $trackers = array_diff($trackers, array($tracker));
        $field    = $tracker->getStatusField();
        if ($field) {
            $html .= '<p>'. 'The column used for the cardwall will be bound to the current status field ('. $hp->Purify($field->getLabel()) .') of this tracker.' .'</p>';
            $html .= 'TODO: display such columns';
            $html .= '<p>'. 'Maybe you wanna choose your own set of columns?' .'</p>';
        } else {
            $columns_raws = $column_dao->searchColumnsByTrackerId($tracker->getId());
            if (!count($columns_raws)) {
                $html .= '<p>'. 'There is no semantic status defined for this tracker. Therefore you must configure yourself the columns used for cardwall.' .'</p>';
            }
            $html .= '<table><thead><tr valign="bottom">';
            $html .= '<td></td>';
            foreach ($columns_raws as $raw) {
                $html .= '<td>';
                $html .= '<input type="text" name="column['. (int)$raw['id'] .'][label]" value="'. $hp->purify($raw['label']) .'" />';
                $html .= '</td>';
            }
            $html .= '<td>';
            $html .= '<label>'. 'New column:'. '<br /><input type="text" name="new_column" value="" placeholder="'. 'Eg: On Going' .'" /></label>';
            $html .= '</td>';
            $html .= '<td>'. $this->translate('global', 'btn_delete') .'</td>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            $mapping_fields = $mappings_dao->searchMappingFields($tracker->getId());
            foreach ($mapping_fields as $i => $row) {
                $mapping_tracker = $tracker_factory->getTrackerById($row['tracker_id']);
                $trackers = array_diff($trackers, array($mapping_tracker));
                $html .= '<tr class="'. html_get_alt_row_color($i + 1) .'" valign="top">';
                $html .= '<td>';
                $html .= $hp->purify($mapping_tracker->getName()) .'<br />';
                $field = $element_factory->getFieldById($row['field_id']);
                $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .']">';
                if (!$field) {
                    $html .= '<option>'. $this->translate('global', 'please_choose_dashed') .'</option>';
                }
                foreach ($element_factory->getUsedSbFields($mapping_tracker) as $sb_field) {
                    $selected = $field == $sb_field ? 'selected="selected"' : '';
                    $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $hp->purify($sb_field->getLabel()) .'</option>';
                }
                $html .= '</select>';
                $html .= '</td>';
                foreach ($columns_raws as $raw) {
                    $html .= '<td>';
                    $html .= '</td>';
                }
                $html .= '<td>';
                $html .= '</td>';
                $html .= '<td>';
                $html .= '<input type="checkbox" name="delete_mapping[]" value="'. (int)$mapping_tracker->getId() .'" />';
                $html .= '</td>';
                $html .= '</tr>';
            }
            if (count($columns_raws) && count($trackers)) {
                $colspan = count($columns_raws) + 2;
                $html .= '<tr>';
                $html .= '<td colspan="'. $colspan .'">';
                $html .= '<p>Wanna add a custom mapping for one of your trackers? (If no custom mapping, then duck typing on value labels will be used)</p>';
                $html .= '<select name="add_mapping_on">';
                $html .= '<option>'. $this->translate('global', 'please_choose_dashed') .'</option>';
                foreach ($trackers as $new_tracker) {
                    $html .= '<option value="'. $new_tracker->getId() .'">'. $hp->purify($new_tracker->getName()) .'</option>';
                }
                $html .= '</select>';
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        return $html;
    }

    private function translate($page, $category, $args = "") {
        $GLOBALS['Language']->getText($page, $category, $args);
    }

}

?>

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
require_once AGILEDASHBOARD_BASE_DIR .'/AgileDashboard/Pane.class.php';

class Cardwall_Pane implements AgileDashboard_Pane {

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
    }

    public function getIdentifier() {
        return 'cardwall';
    }

    public function getTitle() {
        return 'Card Wall';
    }

    public function getContent() {
        $tracker = $this->milestone->getPlanning()->getBacklogTracker();
        $field   = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        if (!$field) {
            return 'Y u no configure the status semantic of ur tracker?';
        }
        
        $html  = '';
        $html .= '<table>';

        // {{{ Copy&paste from Cardwall_Renderer
        $hp = Codendi_HTMLPurifier::instance();
        $nb_columns        = 1;
        $column_sql_select = '';
        $column_sql_from   = '';
        $values            = array(1);
        // ...
        $values = $field->getAllValues();
        foreach ($values as $key => $value) {
            if ($value->isHidden()) {
                unset($values[$key]);
            }
        }
        $nb_columns = count($values);
        if ($nb_columns) {
            $column_sql_select  = ", IFNULL(CVL.bindvalue_id, 100) AS col";
            $column_sql_from = "LEFT JOIN (
                           tracker_changeset_value AS CV2
                           INNER JOIN tracker_changeset_value_list AS CVL ON (CVL.changeset_value_id = CV2.id)
                           ) ON (A.last_changeset_id = CV2.changeset_id AND CV2.field_id = {$field->getId()}) ";
           if (!$field->isRequired()) {
               $none = new Tracker_FormElement_Field_List_Bind_StaticValue(100, $GLOBALS['Language']->getText('global','none'), '', 0, false);
               $values = array_merge(array($none), $values);
               $nb_columns++;
           }
        } else {
            $html .= '<div class="alert-message block-message warning">';
            $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'warn_no_values', $hp->purify($field->getLabel()));
            $html .= '</div>';
        }
        // }}}

        // {{{ Copy&paste from Cardwall_Renderer
        $html .= '<table width="100%" border="1" bordercolor="#ccc" cellspacing="2" cellpadding="10">';
        // ...
        $html .= '<colgroup>';
        foreach ($values as $key => $value) {
            $html .= '<col id="tracker_renderer_board_column-'. (int)$value->getId() .'" />';
        }
        $html .= '</colgroup>';
        
        $html .= '<thead><tr>';
        
        /* not copied */$html .= '<th></th>'; /* not copied */
        $decorators = $field->getBind()->getDecorators();
        foreach ($values as $key => $value) {
            if (1) {
                $style = '';
                if (isset($decorators[$value->getId()])) {
                    $r = $decorators[$value->getId()]->r;
                    $g = $decorators[$value->getId()]->g;
                    $b = $decorators[$value->getId()]->b;
                    if ($r !== null && $g !== null && $b !== null ) {
                        //choose a text color to have right contrast (black on dark colors is quite useless)
                        $color = (0.3 * $r + 0.59 * $g + 0.11 * $b) < 128 ? 'white' : 'black';
                        $style = 'style="background-color:rgb('. (int)$r .', '. (int)$g .', '. (int)$b .'); color:'. $color .';"';
                    }
                }
                $html .= '<th '. $style .'>';
                $html .= $hp->purify($value->getLabel());
            } else {
                $html .= '<th>';
                if (isset($decorators[$value->getId()])) {
                    $html .= $decorators[$value->getId()]->decorate($hp->purify($value->getLabel()));
                } else {
                    $html .= $hp->purify($value->getLabel());
                }
            }
            $html .= '</th>';
        }
        $html .= '</tr></thead>';
        // }}}
        
        $html .= '<tbody>';
        foreach ($this->milestone->getPlannedArtifacts()->getChildren() as $child) {
            $data     = $child->getData();
            $swimline = $data['artifact'];
            $html .= '<tr valign="top">';
            $html .= '<td>';
            $html .= $swimline->fetchTitle();
            $html .= '</td>';
            
            $cards = $child->getChildren();
            foreach ($values as $value) {
                $html .= '<td>';
                $html .= '<ul>';
                foreach ($cards as $row) {
                    $data = $row->getData();
                    $artifact = $data['artifact'];
                    $artifact_status = $artifact->getStatus();
                    if (!$field || $artifact_status === $value->getLabel() || $artifact_status === null && $value->getId() == 100) {
                        $html .= '<li class="tracker_renderer_board_postit" id="tracker_renderer_board_postit-'. (int)$artifact->getId() .'">';
                        // TODO: use mustache templates?
                        $html .= '<div class="card">';
                        $html .= '<div class="card-actions">';
                        $html .= '<a href="'. TRACKER_BASE_URL .'/?aid='. (int)$artifact->getId() .'">#'. (int)$artifact->getId() .'</a>'; // TODO: Use artifact->getUrl or similar?
                        $html .= '</div>';
                        $html .= '<div class="tracker_renderer_board_content">';
                        $html .= $hp->purify($artifact->getTitle(), CODENDI_PURIFIER_BASIC_NOBR, $artifact->getTracker()->getGroupId());
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</li>';
                    }
                }
                $html .= '</ul>&nbsp;';
                $html .= '</td>';
            }
            
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }
}
?>

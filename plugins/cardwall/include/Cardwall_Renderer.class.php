<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once(TRACKER_BASE_DIR .'/Tracker/Report/Tracker_Report_Renderer.class.php');

class Cardwall_Renderer extends Tracker_Report_Renderer {
    
    protected $plugin;
    
    /**
     * Constructor
     *
     * @param Plugin $plugin      the parent cardwall plugin
     * @param int    $id          the id of the renderer
     * @param Report $report      the id of the report
     * @param string $name        the name of the renderer
     * @param string $description the description of the renderer
     * @param int    $rank        the rank
     * @param int    $field_id    the field id
     */
    public function __construct($plugin, $id, $report, $name, $description, $rank, $field_id) {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->plugin   = $plugin;
        $this->field_id = $field_id;
    }
    
    public function initiateSession() {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace("renderers");
        $this->report_session->set("{$this->id}.field_id",   $this->field_id);
    }
    
    /**
     * Fetch content of the renderer
     *
     * @param array $matching_ids
     * @param Request $request
     *
     * @return string
     */
    public function fetch($matching_ids, $request, $report_can_be_modified) {
        $html = '';
        
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        if (!$total_rows) {
            return 'Nothing to display';
        }
        
        $fact = Tracker_FormElementFactory::instance();
        
        $field = $fact->getFormElementById($this->field_id);
        $used  = array($this->field_id => $field);
        //TODO: check that field is a selectbox
        //TODO: check that user can read
        
        
        $html .= '<input type="hidden" id="tracker_report_cardwall_to_be_refreshed" value="0">';
        
        $html .= '<form id="tracker_report_cardwall_settings" action="" method="POST">';
        $html .= '<input type="hidden" value="'. (int)$this->report->id .'" name="report">';
        $html .= '<input type="hidden" value="'. (int)$this->id .'" name="renderer">';
        $html .= '<input type="hidden" value="renderer" name="func">';
        $html .= '<p>'. 'Columns:';
        $options  = '';
        $selected = '';
        foreach($fact->getUsedFormElementsByType($this->report->getTracker(), array('sb')) as $formElement) {
            if ($formElement->userCanRead() && count($formElement->getAllValues())) {
                $selected = '';
                if (isset($used[$formElement->getId()])) {
                    $selected = 'selected="selected"';
                }
                $options .= '<option value="'. $formElement->getId() .'" '. $selected .'>'. $formElement->getLabel() .'</option>';
            }
        }
        if ($options) {
            $html .= '<select name="renderer_cardwall[columns]" id="tracker_report_cardwall_settings_column" autocomplete="off">';
            if (!$selected) {
                $html .= '<option selected="selected" value="">'. '-- '.$GLOBALS['Language']->getText('plugin_tracker_report', 'toggle_criteria').'</option>';
            }
            $html .= $options;
            $html .= '</select>';
        }
        $html .= ' <input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html .= '</p>';
        $html .= '</form>';
        
        $nb_columns        = 1;
        $column_sql_select = '';
        $column_sql_from   = '';
        $values            = array(1);
        if ($field) {
            $values = $field->getAllValues();
            foreach ($values as $key => $value) {
                if ($value->isHidden()) {
                    unset($values[$key]);
                }
            }
            $nb_columns = count($values);
            if ($nb_columns) {
                $column_sql_select  = ", CVL.bindvalue_id AS col";
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
                $html .= 'There is no values in this field. Please choose another one.'; //TODO i18n
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="alert-message block-message warning">';
            $html .= 'Please select a field to group artifacts in columns';
            $html .= '</div>';
        }
        
        // Build a small sql query to fetch artifact titles (depends on tracker semantic)
        $sql = "SELECT A.id AS id, CVT.value AS title $column_sql_select
                FROM tracker_artifact AS A
                   LEFT JOIN (
                       tracker_changeset_value AS CV
                       INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                       INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                   ) ON (A.last_changeset_id = CV.changeset_id)
                   $column_sql_from
                WHERE A.id IN (". $matching_ids['id'] .")
        ";
        $dao = new DataAccessObject();
        $html .= '<div class="tracker_renderer_board nifty">';
        
        $html .= '<label id="tracker_renderer_board-nifty">';
        $html .= '<input type="checkbox" onclick="$(this).up(\'div.tracker_renderer_board\').toggleClassName(\'nifty\');" checked="checked" /> ';
        $html .= 'hand drawn view';
        $html .= '</label>';
        
        $html .= '<table width="100%" border="1" bordercolor="#ccc" cellspacing="2" cellpadding="10">';
        
        if ($field) {
            $html .= '<colgroup>';
            $width = floor(100 / $nb_columns);
            foreach ($values as $key => $value) {
                $html .= '<col id="tracker_renderer_board_column-'. (int)$value->getId() .'" width="'. $width .'%"/>';
            }
            $html .= '</colgroup>';
            
            $html .= '<thead><tr>';
            $decorators = $field->getBind()->getDecorators();
            foreach ($values as $key => $value) {
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
                //TODO: check that users are properly escaped
                $html .= Codendi_HTMLPurifier::instance()->purify($value->getLabel());
                $html .= '</th>';
            }
            $html .= '</tr></thead>';
        }
        
        $html .= '<tbody><tr valign="top">';
        
        $cards = $dao->retrieve($sql);
        foreach ($values as $value) {
            $html .= '<td>';
            $html .= '<ul>';
            foreach ($cards as $row) {
                if (!$field || $row['col'] == $value->getId()) {
                    $html .= '<li class="tracker_renderer_board_postit" id="tracker_renderer_board_postit-'. (int)$row['id'] .'">';
                    $html .= '<p class="tracker_renderer_board_title"><a href="'. TRACKER_BASE_URL .'/?aid='. (int)$row['id'] .'">#'. (int)$row['id'] .'</a></p>';
                    $html .= '<p class="tracker_renderer_board_content"> '. $row['title'] .'</p>'; //TODO: HTMLPurifier
                    $html .= '</li>';
                }
            }
            $html .= '</ul>&nbsp;';
            $html .= '</td>';
        }
        
        $html .= '</tr></tbody></table>';
        
        return $html;
    }

    /*----- Implements below some abstract methods ----*/

    public function getIcon() {
                return '<img src="'. $this->plugin->getThemePath().'/images/renderer.png" />';
    }

    public function delete() {}

    public function getType() {
        return 'plugin_cardwall';
    }

    public function processRequest(TrackerManager $tracker_manager, $request, $current_user) {
        $renderer_parameters = $request->get('renderer_cardwall');
        $this->initiateSession();
        if ($renderer_parameters && is_array($renderer_parameters)) {
            
            //Update the field_id parameter
            if (isset($renderer_parameters['columns'])) {
                $new_columns_field = (int)$renderer_parameters['columns'];
                if ($new_columns_field && ($this->field_id != $new_columns_field)) {
                    $this->report_session->set("{$this->id}.field_id", $new_columns_field);
                    $this->report_session->setHasChanged();
                    $this->field_id = $new_columns_field;
                }
            }
            
        }
    }

    public function fetchWidget() {
        return '';
    }
    
    /**
     * Create a renderer - add in db
     *     
     * @return bool true if success, false if failure
     */
    public function create() {
        $success = true;
        $rrf = Tracker_Report_RendererFactory::instance();
        if ($renderer_id = $rrf->saveRenderer($this->report, $this->name, $this->description, $this->getType())) {
            //field_id
            $this->saveRendererProperties($renderer_id);
        }
        return $success;
    }
    
    /**
     * Update the renderer
     *     
     * @return bool true if success, false if failure
     */
    public function update() {
        $success = true;
        if ($this->id > 0) {
            //field_id
            $this->saveRendererProperties($this->id);
        }
        return $success;
    }   

    public function duplicate($from_renderer, $field_mapping) { }

    public function afterSaveObject($renderer) { }
    
    /**
     * Save field_id in db
     *     
     * @param int $renderer_id the id of the renderer
     */
    protected function saveRendererProperties($renderer_id) {
        $dao = $this->getDao();
        $dao->save($renderer_id, $this->field_id);
    }
    
    /** 
     * Wrapper for Cardwall_RendererDao
     */
    public function getDao() {
        return new Cardwall_RendererDao();
    }
}
?>

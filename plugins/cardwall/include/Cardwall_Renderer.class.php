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
    
    /**
     * @var Plugin
     */
    protected $plugin;
    
    private $enable_qr_code = true;
    
    /**
     * Constructor
     *
     * @param Plugin $plugin         the parent cardwall plugin
     * @param int    $id             the id of the renderer
     * @param Report $report         the id of the report
     * @param string $name           the name of the renderer
     * @param string $description    the description of the renderer
     * @param int    $rank           the rank
     * @param int    $field_id       the field id
     * @param bool   $enable_qr_code Display the QR code to ease usage of tablets
     */
    public function __construct($plugin, $id, $report, $name, $description, $rank, $field_id, $enable_qr_code) {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->plugin         = $plugin;
        $this->field_id       = $field_id;
        $this->enable_qr_code = $enable_qr_code;
    }
    
    public function initiateSession() {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace("renderers");
        $this->report_session->set("{$this->id}.field_id",   $this->field_id);
    }
    
    private function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
    
    /**
     * @return Tracker_FormElement_Field
     */
    private function getField() {
        $field = $this->getFormElementFactory()->getFormElementById($this->field_id);
        if ($field) {
            if (!$field->userCanRead() || !is_a($field, 'Tracker_FormElement_Field_Selectbox')) {
                $field = null;
            }
        }
        return $field;
    }
    
    /**
     * @return int
     */
    private function getFieldId() {
        $field = $this->getField();
        
        if ($field) {
            return $field->getId();
        }
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
        
        $hp = Codendi_HTMLPurifier::instance();
        
        
        $form  = '';
        $form .= '<input type="hidden" id="tracker_report_cardwall_to_be_refreshed" value="0">';
        
        $form .= '<form id="tracker_report_cardwall_settings" action="" method="POST">';
        $form .= '<input type="hidden" value="'. (int)$this->report->id .'" name="report">';
        $form .= '<input type="hidden" value="'. (int)$this->id .'" name="renderer">';
        if ($request->existAndNonEmpty('pv')) {
            $form .= '<input type="hidden" value="'. (int)$request->get('pv') .'" name="pv">';
        }
        $form .= '<input type="hidden" value="renderer" name="func">';
        $form .= '<p>'. 'Columns:';
        $options  = '';
        $selected = '';
        $one_selected = false;
        foreach($this->getFormElementFactory()->getUsedFormElementsByType($this->report->getTracker(), array('sb')) as $formElement) {
            if ($formElement->userCanRead() && count($formElement->getAllValues())) {
                $selected = '';
                if ($formElement->getId() == $this->getFieldId()) {
                    $selected = 'selected="selected"';
                    $one_selected = true;
                }
                $options .= '<option value="'. $formElement->getId() .'" '. $selected .'>'. $hp->purify($formElement->getLabel()) .'</option>';
            }
        }
        if ($options) {
            $form .= '<select name="renderer_cardwall[columns]" id="tracker_report_cardwall_settings_column" autocomplete="off" onchange="this.form.submit();">';
            if (!$one_selected) {
                $form .= '<option selected="selected" value="">'. $GLOBALS['Language']->getText('global', 'please_choose_dashed') .'</option>';
            }
            $form .= $options;
            $form .= '</select>';
        }
        $form .= ' <input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $form .= '</p>';
        $form .= '</form>';
        
        $display_choose  = '';
        $display_choose .= '<div class="alert-message block-message warning">';
        $display_choose .= $GLOBALS['Language']->getText('plugin_cardwall', 'warn_please_choose');
        $display_choose .= '</div>';
        
        $html .= $this->fetchCards($matching_ids, $display_choose, $form);
        
        $proto = Config::get('sys_force_ssl') ? 'https' : 'http';
        $url   = $proto .'://'. Config::get('sys_default_domain') . TRACKER_BASE_URL .'/?'. http_build_query(
            array(
                'report'   => $this->report->id,
                'renderer' => $this->id,
                'pv'       => 2,
            )
        );
        
        if ($this->enable_qr_code) {
            $html .= $this->getHTMLQRCode($url);
        }
        return $html;
    }
    
    private function fetchCards($matching_ids, $display_choose = '', $form = '') {
        $html  = '';
        $hp    = Codendi_HTMLPurifier::instance();
        $field = $this->getField();
        
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        if (!$total_rows) {
            return '<p>'. $GLOBALS['Language']->getText('plugin_tracker', 'no_artifacts') .'</p>';
        }
        
        $html .= $form;
        
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
        } else {
            $html .= $display_choose;
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
        //echo $sql;
        $dao = new DataAccessObject();
        
        $nifty = Toggler::getClassname('tracker_renderer_board-nifty') == 'toggler' ? 'nifty' : false;
        $html .= '<div class="tracker_renderer_board '. $nifty .'">';
        
        $html .= '<label id="tracker_renderer_board-nifty">';
        $html .= '<input type="checkbox" onclick="$(this).up(\'div.tracker_renderer_board\').toggleClassName(\'nifty\'); new Ajax.Request(\'/toggler.php?id=tracker_renderer_board-nifty\');" ';
        if ($nifty) {
            $html .= 'checked="checked"';
        }
        $html .= ' />';
            $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'nifty_view');
        $html .= '</label>';
        
        $html .= '<table width="100%" border="1" bordercolor="#ccc" cellspacing="2" cellpadding="10">';
        
        if ($field) {
            $html .= '<colgroup>';
            foreach ($values as $key => $value) {
                $html .= '<col id="tracker_renderer_board_column-'. (int)$value->getId() .'" />';
            }
            $html .= '</colgroup>';
            
            $html .= '<thead><tr>';
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
                    $html .= '<p class="tracker_renderer_board_content"> '. $hp->purify($row['title'], CODENDI_PURIFIER_BASIC_NOBR, $this->report->getTracker()->getGroupId()) .'</p>';
                    $html .= '</li>';
                }
            }
            $html .= '</ul>&nbsp;';
            $html .= '</td>';
        }
        
        $html .= '</tr></tbody></table>';
        $html .= '</div>';
        return $html;
    }

    private function getHTMLQRCode($url) {
        $html = '';
        $html .= '<div class="plugin_cardwall_qrcode">';
        $html .= '<div id="plugin_cardwall_qrcode_toggler" class="'. Toggler::getClassname('plugin_cardwall_qrcode_toggler', false, true) .'">QR Code</div>';
        $html .= '<img src="http://chart.apis.google.com/chart?'. http_build_query(
            array(
                'chs'  => '150x150',
                'cht'  => 'qr',
                'chld' => 'L|0',
                'chl'  => $url,
            )).'" alt="QR code" widht="150" height="150"/>';
        $html .= '</div>';
        return $html;
    }
    
    /*----- Implements below some abstract methods ----*/

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
    
    /**
     * Fetch content to be displayed in widget
     */
    public function fetchWidget() {
        $html  = '';
        $html .= $this->fetchCards($this->report->getMatchingIds());
        $html .= $this->fetchWidgetGoToReport();
        return $html;
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
    
    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     * @param $formsMapping the form elements mapping
     */
    public function exportToXML($root, $formsMapping) {
        parent::exportToXML(&$root, $formsMapping);
        if ($mapping = (string)array_search($this->field_id, $formsMapping)) {
            $root->addAttribute('field_id', $mapping);
        }
    }
}
?>

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
     * @param int    $id          the id of the renderer
     * @param Report $report      the id of the report
     * @param string $name        the name of the renderer
     * @param string $description the description of the renderer
     * @param int    $rank        the rank
     * @param Plugin $plugin      the parent cardwall plugin
     */
    public function __construct($id, $report, $name, $description, $rank, $plugin) {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->plugin = $plugin;
    }
    
    public function initiateSession() {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace("renderers");
        //$this->report_session->set("{$this->id}.chunksz",   $this->chunksz);
        //$this->report_session->set("{$this->id}.multisort", $this->multisort);
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
        
        $field_id = 118;
        if ($field = Tracker_FormElementFactory::instance()->getFormElementById($field_id)) {
            //TODO: check that field is a selectbox
            $values = $field->getAllValues();
            foreach ($values as $key => $value) {
                if ($value->isHidden()) {
                    unset($values[$key]);
                }
            }
            
            $nb_columns = count($values);
            if ($nb_columns) {
                // Build a small sql query to fetch artifact titles (depends on tracker semantic)
                $sql = "SELECT A.id AS id, CVT.value AS title, CVL.bindvalue_id AS col
                        FROM tracker_artifact AS A
                           LEFT JOIN (
                               tracker_changeset_value AS CV
                               INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                               INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                           ) ON (A.last_changeset_id = CV.changeset_id)
                           LEFT JOIN (
                               tracker_changeset_value AS CV2
                               INNER JOIN tracker_changeset_value_list AS CVL ON (CVL.changeset_value_id = CV2.id)
                           ) ON (A.last_changeset_id = CV2.changeset_id AND CV2.field_id = $field_id) 
                        WHERE A.id IN (". $matching_ids['id'] .")
                ";
                $dao = new DataAccessObject();
                $html .= '<div class="tracker_renderer_board">';
                
                $html .= '<table width="100%" border="1" style="border-collapse: collapse;" bordercolor="#ccc" cellspacing="2" cellpadding="10">';
                
                $html .= '<colgroup>';
                $html .= implode('', array_fill(0, $nb_columns, '<col width="'. floor(100 / $nb_columns) .'%"/>'));
                $html .= '</colgroup>';
                
                $html .= '<thead><tr>';
                foreach ($values as $value) {
                    $html .= '<th>';
                    //TODO: check that users are properly escaped
                    $html .= Codendi_HTMLPurifier::instance()->purify($value->getLabel());
                    $html .= '</th>';
                }
                $html .= '</tr></thead>';
                
                $html .= '<tbody><tr valign="top">';
                
                $cards = $dao->retrieve($sql);
                foreach ($values as $value) {
                    $html .= '<td>';
                    $html .= '<ul>';
                    foreach ($cards as $row) {
                        if ($row['col'] == $value->getId()) {
                            $html .= '<li class="tracker_renderer_board_postit">';
                            $html .= '<p class="tracker_renderer_board_title"><a href="'. TRACKER_BASE_URL .'/?aid='. $row['id'] .'">#'. $row['id'] .'</a></p>';
                            $html .= '<p class="tracker_renderer_board_content"> '. $row['title'] .'</p>';
                            $html .= '</li>';
                        }
                    }
                    $html .= '</ul>';
                    $html .= '</td>';
                }
                
                $html .= '</tr></tbody></table>';
            }
        }
        return $html;
    }

    /*----- Implements below some abstract methods ----*/

    public function getIcon() {
                return '<img src="'. $this->plugin->getThemePath().'/images/renderer.png" />';
    }

    public function delete() {}

    public function getType() {
        return 'plugin_board';
    }

    public function processRequest(TrackerManager $tracker_manager, $request, $current_user) {
    }

    public function fetchWidget() {
        return '';
    }

    public function update() {
        return true;
    }   

    public function duplicate($from_renderer, $field_mapping) { }

    public function afterSaveObject($renderer) { }
}
?>

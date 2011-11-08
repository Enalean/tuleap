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
        
        // Build a small sql query to fetch artifact titles (depends on tracker semantic)
        $sql = "SELECT A.id AS id, CVT.value AS title
                FROM tracker_artifact AS A
                   LEFT JOIN (
                       tracker_changeset_value AS CV
                       INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                       INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                   ) ON (A.last_changeset_id = CV.changeset_id)
                WHERE A.id IN (". $matching_ids['id'] .")";
        $dao = new DataAccessObject();
        $html .= '<div class="tracker_renderer_board"><ul>';
        
        foreach ($dao->retrieve($sql) as $row) {
            $html .= '<li>';
            $html .= '<a href="/tracker/?aid='. $row['id'] .'">';
            $html .= '<p class="tracker_renderer_board_title">bug #'. $row['id'] .'</p>';
            $html .= '<p class="tracker_renderer_board_content"> '. $row['title'] .'</p>';
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        
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

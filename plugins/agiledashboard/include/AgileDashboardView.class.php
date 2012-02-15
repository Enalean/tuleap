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

class AgileDashboardView {
    
    /**
     * @var Service
     */
    private $service;
    
    /**
     * @var BaseLanguage
     */
    private $language;
    
    /**
     * @var Project $project 
     */
    private $fields;
    
    public function __construct(Service $service, BaseLanguage $language, array $fields) {
        $this->language = $language;
        $this->service  = $service;
        $this->fields   = $fields;
    }
    
    public function render() {
        $title = $this->language->getText('plugin_agiledashboard', 'title');
        
        $this->service->displayHeader($title, array(), array());
        
        $this->displayCriteria();
        
        $this->service->displayFooter();
    }
    
    private function displayCriteria() {
        foreach ($this->getCriteria() as $criteria) {
            echo $criteria->fetch();
        }
    }
    
    private function getCriteria() {
        $criteria = array();
        $report_id = $name = $description = $current_renderer_id = $parent_report_id = $user_id = $is_default = $tracker_id = $is_query_displayed = $updated_by = $updated_at = 0;
        $report = new Tracker_Report($report_id, $name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed, $updated_by, $updated_at);

        foreach ($this->fields as $field) {
            $id = null;
            $rank = 0;
            $is_advanced = false;

            $criteria[] = new Tracker_Report_Criteria($id, $report, $field, $rank, $is_advanced);
        }
        return $criteria;
    }
    
}
?>

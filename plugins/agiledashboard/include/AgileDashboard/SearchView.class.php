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

require_once 'common/project/Service.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Report/Tracker_Report.class.php';
require_once 'html.php';

class AgileDashboard_SearchView {
    
    /**
     * @var Service
     */
    private $service;
    
    /**
     * @var BaseLanguage
     */
    private $language;
    
    /**
     * @var Tracker_Report
     */
    private $report;
    
    /**
     * @var Array of Tracker_Report_Criteria
     */
    private $criteria;
    
    /**
     * @var Array of artifacts rows
     */
    private $artifacts;
    
    public function __construct(Service $service, BaseLanguage $language, Tracker_Report $report, array $criteria, $artifacts) {
        $this->language  = $language;
        $this->service   = $service;
        $this->report    = $report;
        $this->criteria  = $criteria;
        $this->artifacts = $artifacts;
    }
    
    public function render() {
        $title = $this->language->getText('plugin_agiledashboard', 'title');
        
        $this->service->displayHeader($title, array(), array());
        
        $report_can_be_modified = false;
        echo $this->report->fetchDisplayQuery($this->criteria, $report_can_be_modified);
        
        echo $this->fetchTable();
        
        $this->service->displayFooter();
    }
    
    private function fetchTable() {
        
        $html = '';
        $html .= '<table>';
        $html .= $this->fetchTHead();
        $html .= $this->fetchTBody();
        $html .= '</table>';
        return $html;
    }
    
    private function fetchTBody() {
        $html = '';
        $html .= '<tbody>';
        $i = 0;
        foreach ($this->artifacts as $row) {
            $html .= '<tr class="'. html_get_alt_row_color($i++) .'">';
            $html .= '<td>';
            $html .= '<a href="'.TRACKER_BASE_URL.'/?aid='.$row['id'].'"> '.$row['id'].'</a>';
            $html .= '</td>';
            $html .= '<td>';
            $html .= $row['title'];
            $html .= '</td>';
        foreach ($this->criteria as $header) {
            $html .= '<td>'. '' .'</td>';
        }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        return $html;
    }
    
    private function fetchTHead() {
        $html = '';
        $html .= '<thead>';
        $html .= '<tr class="boxtable">';
        $html .= '<td>id</td>';
        $html .= '<td>title</td>';
        foreach ($this->criteria as $header) {
            $html .= '<td>'. $header->field->getLabel().'</td>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        return $html;
    }
}
?>

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
    
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    
    /**
     * @var Tracker_SharedFormElementFactory
     */
    private $shared_factory;
    
    
    public function __construct(Service $service, BaseLanguage $language, Tracker_Report $report, array $criteria, $artifacts, Tracker_ArtifactFactory $artifact_factory, Tracker_SharedFormElementFactory $shared_factory) {
        $this->language         = $language;
        $this->service          = $service;
        $this->report           = $report;
        $this->criteria         = $criteria;
        $this->artifacts        = $artifacts;
        $this->artifact_factory = $artifact_factory;
        $this->shared_factory   = $shared_factory;
    }
    
    public function render() {
        $title = $this->language->getText('plugin_agiledashboard', 'title');
        
        $breadcrumbs = array(
            array(
                'url' => null,
                'title' => $title,
            )
        );
        
        $this->service->displayHeader($title, $breadcrumbs, array());
        
        $html  = '';
        $html .= '<div class="agiledashboard">';
        $html .= '<h1>'. $title .'</h1>';
        try {
            $html .= $this->fetchContent();
        } catch (Exception $e) {
            $html .= '<img src="data:image/jpg;base64,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" />';
        }
        $html .= '</div>';
        
        echo $html;
        
        $this->service->displayFooter();
    }
    
    private function fetchContent() {
        if ($this->criteria) {
            $html  = '';
            $html .= '<table><tr valign="top"><td>';
            $report_can_be_modified = false;
            $html .= $this->report->fetchDisplayQuery($this->criteria, $report_can_be_modified);
            $html .= '</td><td>';
            $html .= $this->fetchResults();
            $html .= '</td></tr></table>';
            return $html;
        }
        throw new Exception('There is no shared field to query across your trackers');
    }
    
    private function fetchResults() {
        $html = '';
        $html .= '<div class="">';
        try {
            $html .= $this->fetchTable();
        } catch (Exception $e) {
            $html .= '<em>'. $e->getMessage() .'</em>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function fetchTable() {
        if (count($this->artifacts)) {
            $html = '';
            $html .= '<table>';
            $html .= $this->fetchTHead();
            $html .= $this->fetchTBody();
            $html .= '</table>';
            return $html;
        }
        throw new Exception('No artifact match your query');
    }
    
    private function fetchTBody() {
        $html = '';
        $html .= '<tbody>';
        $i = 0;
        foreach ($this->artifacts as $row) {
            $artifact = $this->artifact_factory->getArtifactById($row['id']);
            if ($artifact) {
                $tracker = $artifact->getTracker();
                $html .= '<tr class="' . html_get_alt_row_color($i++) . '">';
                $html .= '<td>';
                $html .= $artifact->fetchDirectLinkToArtifact();
                $html .= '</td>';
                $html .= '<td>';
                $html .= $row['title'];
                $html .= '</td>';
                $html .= $this->fetchColumnsValues($artifact);
                $html .= '</tr>';
            }
        }
        $html .= '</tbody>';
        return $html;
    }
    
    private function fetchTHead() {
        $html = '';
        $html .= '<thead>';
        $html .= '<tr class="boxtable">';
        $html .= '<td class="boxtitle">id</td>';
        $html .= '<td class="boxtitle">title</td>';
        foreach ($this->criteria as $header) {
            $html .= '<td class="boxtitle">'. $header->field->getLabel().'</td>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        return $html;
    }
    
    private function fetchColumnsValues(Tracker_Artifact $artifact) {
        $html = '';
        foreach ($this->criteria as $criterion) {
            $value = '';
            if ($field = $this->shared_factory->getGoodField($artifact->getTracker(), $criterion->field)) {
                $value = $field->fetchChangesetValue($artifact->getId(), $artifact->getLastChangeset()->getId(), null);
            }
            $html .= '<td>'. $value .'</td>';
        }
        return $html;
    }
}
?>

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
    
    /**
     * @var Array of Tracker
     */
    private $trackers;
    
    public function __construct(Service $service, BaseLanguage $language, Tracker_Report $report, array $criteria, $artifacts, Tracker_ArtifactFactory $artifact_factory, Tracker_SharedFormElementFactory $shared_factory, $trackers) {
        $this->language         = $language;
        $this->service          = $service;
        $this->report           = $report;
        $this->criteria         = $criteria;
        $this->artifacts        = $artifacts;
        $this->artifact_factory = $artifact_factory;
        $this->shared_factory   = $shared_factory;
        $this->trackers         = $trackers;
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
            $html .= $this->fetchTrackerList();
        } catch (Exception $e) {
            $html .= '<em>'. $e->getMessage() .'</em>';
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
            $html .= $this->fetchResults();
            $html .= '</td></tr></table>';
            return $html;
        }
        throw new Exception('There is no shared field to query across your trackers');
    }
    
    private function fetchResults() {
        $html = '';
        $html .= '<div class="tracker_report_renderer">';
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
                $html .= $this->fetchColumnsValues($artifact, $row['last_changeset_id']);
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
        $html .= '<td class="boxtitle">'.$this->language->getText('plugin_agiledashboard', 'summary').'</td>';
        foreach ($this->criteria as $header) {
            $html .= '<td class="boxtitle">'. $header->field->getLabel().'</td>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        return $html;
    }
    
    private function fetchColumnsValues(Tracker_Artifact $artifact, $last_changeset_id) {
        $html = '';
        foreach ($this->criteria as $criterion) {
            $value = '';
            $field = $this->shared_factory->getFieldFromTrackerAndSharedField($artifact->getTracker(), $criterion->field);
            if ($field) {
                $value = $field->fetchChangesetValue($artifact->getId(), $last_changeset_id, null);
            }
            $html .= '<td>'. $value .'</td>';
        }
        return $html;
    }
    
    private function fetchTrackerList() {
        $html  = '';
        $html .= '<div>';
        if (count($this->trackers) > 0) {
            $html .= '<ul>';
            foreach($this->trackers as $tracker) {
                $html .= '<li>';
                $html .= $tracker->getName().' ('.$tracker->getProject()->getPublicName().')';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';
        return $html;
    }
}
?>

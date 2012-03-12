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
require_once dirname(__FILE__).'/../Report/Tracker_Report.class.php';
require_once dirname(__FILE__).'/../Hierarchy/Hierarchy.class.php';
require_once 'common/TreeNode/InjectPaddingInTreeNodeVisitor.class.php';

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
     * @var TreeNode of artifacts rows
     */
    private $tree_of_artifacts;
    
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
    
    private static $state_classes = array(
        TreeNode_GetStateVisitor::STATE_BLANK => 'tree-blank',
        TreeNode_GetStateVisitor::STATE_NODE  => 'tree-node',
        TreeNode_GetStateVisitor::STATE_PIPE  => 'tree-pipe',
        TreeNode_GetStateVisitor::STATE_LAST  => 'tree-last',
    );
    
    public function __construct(Service                          $service,
                                BaseLanguage                     $language, 
                                Tracker_Report                   $report, 
                                array                            $criteria, 
                                TreeNode                         $tree_of_artifacts, 
                                Tracker_ArtifactFactory          $artifact_factory, 
                                Tracker_SharedFormElementFactory $shared_factory, 
                                                                 $trackers) {
        $this->language          = $language;
        $this->service           = $service;
        $this->report            = $report;
        $this->criteria          = $criteria;
        $this->tree_of_artifacts = $tree_of_artifacts;
        $this->artifact_factory  = $artifact_factory;
        $this->shared_factory    = $shared_factory;
        $this->trackers          = $trackers;
        $this->treeVisistor      = new TreeNode_InjectPaddingInTreeNodeVisitor($collapsable = true);
        $this->tree_of_artifacts->accept($this->treeVisistor);
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
        if ($this->criteria) {
            $html .= $this->fetchContent();
            $html .= $this->fetchTrackerList();
        } else {
            $html .= '<em>'. 'There is no shared field to query across your trackers' .'</em>';
        }
        $html .= '</div>';
        
        echo $html;
        
        $this->service->displayFooter();
    }
    
    private function fetchContent() {
        $html  = '';
        $html .= '<table><tr valign="top"><td>';
        $report_can_be_modified = false;
        $html .= $this->report->fetchDisplayQuery($this->criteria, $report_can_be_modified);
        $html .= $this->fetchResults();
        $html .= '</td></tr></table>';
        return $html;
    }
    
    private function fetchResults() {
        $html  = '';
        $html .= '<div class="tracker_report_renderer">';
        if ($this->tree_of_artifacts->hasChildren()) {
            $html .= $this->fetchTable();
        } else {
            $html .= '<em>'. 'No artifact match your query' .'</em>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function fetchTable() {
        $html  = '';
        $html .= '<table cellspacing="1">';
        $html .= $this->fetchTHead();
        $html .= $this->fetchTBody();
        $html .= '</table>';
        return $html;
    }
    
    public function visit(TreeNode $node) {
        $html = '';
        $row = $node->getData();
        $artifact = $this->artifact_factory->getArtifactById($row['id']);
        if ($artifact) {
            $html .= '<tr class="' . html_get_alt_row_color($this->current_index++) . '" valign="top">';
            $html .= '<td nowrap>';
            $html .= $row['tree-padding'];
            $html .= $artifact->fetchDirectLinkToArtifact();
            $html .= '</td>';
            $html .= '<td>';
            $html .= $row['title'];
            $html .= '</td>';
            $html .= $this->fetchColumnsValues($artifact, $row['last_changeset_id']);
            $html .= '</tr>';
            foreach ($node->getChildren() as $child) {
                $html .= $child->accept($this);
            }
        }
        return $html;
    }
    
    private function fetchTBody() {
        $html  = '';
        $html .= '<tbody>';
        $this->current_index = 0;
        foreach ($this->tree_of_artifacts->getChildren() as $child) {
            $html.= $child->accept($this);
        }
        $html .= '</tbody>';
        return $html;
    }
    
    private function fetchTHead() {
        $html = '';
        $html .= '<thead>';
        $html .= '  <tr class="boxtable">';
        $html .= '    <th class="boxtitle"><span class="label">id</span></th>';
        $html .= '    <th class="boxtitle sortfirstasc"><span class="label">'.$this->language->getText('plugin_agiledashboard', 'summary').'</span></th>';
        foreach ($this->criteria as $header) {
            $html .= '<th class="boxtitle"><span class="label">'. $header->field->getLabel().'</span></th>';
        }
        $html .= '  </tr>';
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
        $html .= '<div class="agiledashboard_trackerlist">';
        $html .= $this->language->getText('plugin_agiledashboard', 'included_trackers_title');
        if (count($this->trackers) > 0) {
            $html .= '<ul>';
            foreach($this->trackers as $tracker) {
                $html .= '<li>';
                $html .= $tracker->getName().' ('.$tracker->getProject()->getPublicName().')';
                $html .= '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p><em>'.$this->language->getText('plugin_agiledashboard', 'included_trackers_not_found').'</em></p>';
        }
        $html .= '</div>';
        return $html;
    }
}
?>

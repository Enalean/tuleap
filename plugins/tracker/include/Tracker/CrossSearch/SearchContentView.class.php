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

/**
 * Renders both the cross-tracker search form and results. 
 */
class Tracker_CrossSearch_SearchContentView {
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
    protected $tree_of_artifacts;
    
    /**
     * @var Tracker_ArtifactFactory
     */
    protected $artifact_factory;
    
    /**
     * @var Tracker_FormElementFactory
     */
    private $factory;

    function __construct(Tracker_Report                   $report, 
                         array                            $criteria, 
                         TreeNode                         $tree_of_artifacts, 
                         Tracker_ArtifactFactory          $artifact_factory, 
                         Tracker_FormElementFactory       $factory) {
        
        $this->report            = $report;
        $this->criteria          = $criteria;
        $this->tree_of_artifacts = $tree_of_artifacts;
        $this->artifact_factory  = $artifact_factory;
        $this->factory           = $factory;
        
        $treeVisitor = new TreeNode_InjectPaddingInTreeNodeVisitor($collapsable = true);
        $this->tree_of_artifacts->accept($treeVisitor);
    }
    
    public function fetch() {
        $report_can_be_modified = false;
        
        $html  = '';
        $html .= '<table cellpadding="0" cellspacing="0"><tr valign="top"><td>';
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
            $html .= '<em>'. $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'no_matching_artifact').'</em>';
        }
        $html .= '</div>';
        
        return $html;
    }
    
    protected function fetchTable() {
        $html  = '';
        $html .= '<table cellspacing="1">';
        $html .= $this->fetchTHead();
        $html .= $this->fetchTBody();
        $html .= '</table>';
        return $html;
    }
    
    public function visit(TreeNode $node) {
        $html     = '';
        $row      = $node->getData();
        $artifact = $this->artifact_factory->getArtifactById($row['id']);
        
        if ($artifact) {
            $html .= '<tr class="' . html_get_alt_row_color($this->current_index++) . '" valign="top">';
            $html .= '<td nowrap>';
            $html .= $row['tree-padding'];
            $html .= $artifact->fetchDirectLinkToArtifact();
            $html .= '</td>';
            $html .= $this->fetchColumnsValues($artifact, $row);
            $html .= '</tr>';
            
            foreach ($node->getChildren() as $child) {
                $html .= $child->accept($this);
            }
        }
        
        return $html;
    }
    
    private function fetchTBody() {
        $this->current_index = 0;
        
        $html  = '';
        $html .= '<tbody>';
        foreach ($this->tree_of_artifacts->getChildren() as $child) {
            $html.= $child->accept($this);
        }
        $html .= '</tbody>';
        
        return $html;
    }
    
    private function fetchTHead() {
        $html  = '';
        $html .= '<thead>';
        $html .= '  <tr class="boxtable">';
        $html .= '    <th class="boxtitle"><span class="label">id</span></th>';
        foreach ($this->criteria as $criteria) {
            $html .= '<th class="boxtitle"><span class="label">'. $criteria->field->getLabel().'</span></th>';
        }
        $html .= '  </tr>';
        $html .= '</thead>';
        
        return $html;
    }
    
    private function fetchColumnsValues(Tracker_Artifact $artifact, array $row) {
        $html = '';
        
        foreach ($this->criteria as $criterion) {
            $value = '';
            $field = $this->getFieldFromReportField($criterion->field, $artifact->getTracker());
            if ($field) {
                $value = $this->getValueFromFieldOrRow($artifact, $field, $row);
            }
            
            $html .= '<td>'. $value .'</td>';
        }
        
        return $html;
    }
    
    private function getValueFromFieldOrRow(Tracker_Artifact $artifact, Tracker_Report_Field $field, array $row) {
        $value = '';

        if ($field instanceof Tracker_CrossSearch_ArtifactReportField) {
            $fields = $this->factory->getUsedArtifactLinkFields($field->getTracker());
            $artifact_link_field = $fields[0]; // TODO: empty array

            $key = 'art_link_' . $artifact_link_field->getId();
            if (isset($row[$key])) {
                $value = $row[$key];
            }
        } else {
            $value = $field->fetchChangesetValue($artifact->getId(), $row['last_changeset_id'], null);
        }
        
        return $value;
    }
    
    private function getFieldFromReportField(Tracker_Report_Field $report_field, Tracker $tracker) {
        if ($this->isASharedField($report_field)) {
            return $this->factory->getFieldFromTrackerAndSharedField($tracker, $report_field);
        } else {
            return $report_field;
        }
    }
    
    private function isASharedField(Tracker_Report_Field $report_field) {
        return !($report_field instanceof Tracker_CrossSearch_SemanticTitleReportField ||
                 $report_field instanceof Tracker_CrossSearch_SemanticStatusReportField ||
                 $report_field instanceof Tracker_CrossSearch_ArtifactReportField);
    }

}
?>

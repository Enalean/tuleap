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
require_once 'common/TreeNode/InjectSpanPaddingInTreeNodeVisitor.class.php';
require_once 'html.php';

class Tracker_CrossSearch_SearchContentView {
    /**
     * @var Tracker_Report
     */
    protected $report;
    
    /**
     * @var Array of Tracker_Report_Criteria
     */
    protected $criteria;
    
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

    /**
     * @var PFUser
     */
    protected $user;
    
    public function __construct(Tracker_Report                   $report, 
                                array                            $criteria, 
                                TreeNode                         $tree_of_artifacts, 
                                Tracker_ArtifactFactory          $artifact_factory, 
                                Tracker_FormElementFactory       $factory,
                                PFUser                           $user) {
        
        $this->report            = $report;
        $this->criteria          = $criteria;
        $this->tree_of_artifacts = $tree_of_artifacts;
        $this->artifact_factory  = $artifact_factory;
        $this->factory           = $factory;
        $this->user              = $user;
        $collapsable             = true;
        $treeVisitor             = new TreeNode_InjectSpanPaddingInTreeNodeVisitor($collapsable);
        $this->tree_of_artifacts->accept($treeVisitor);
    }
    
    public function fetch() {
        $report_can_be_modified = false;
        
        $html  = '';
        $html .= $this->report->fetchDisplayQuery($this->criteria, $report_can_be_modified, $this->user);
        $html .= $this->fetchResults();
        
        return $html;
    }
    
    protected function fetchResults() {
        $html  = '';
        $html .= '<div class="tracker_report_renderer">';
        $html .= $this->fetchResultActions();
        if ($this->tree_of_artifacts->hasChildren()) {
            $html .= $this->fetchTable();
        } else {
            $html .= $this->fetchNoMatchingArtifacts();
        }
        $html .= '</div>';
        
        return $html;
    }

    protected function fetchNoMatchingArtifacts() {
        return '<em>'. $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'no_matching_artifact').'</em>';
    }

    protected function fetchTable() {
        $html  = '';
        $html .= '<table id="treeTable" class="tree-view">';
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
            $html .= '<tr id="tree-node-' . $row['id'] . '" class="' . html_get_alt_row_color($this->current_index++) . '" >';
            $html .= '<td class="first-column">';
            $html .= $row['tree-padding'];
            $html .= sprintf($row['content-template'], $artifact->fetchDirectLinkToArtifact());
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
            $key = $field->getArtifactLinkFieldName($this->factory);
            if (isset($row[$key])) {
                $values = array();
                // GROUP_CONCAT retrieve as much results as linked artifacts, need to filter
                $linked_artifact_ids = array_unique(explode(',', $row[$key]));
                foreach ($linked_artifact_ids as $id) {
                    $values[]= $this->getArtifactLinkTitle($id);
                }
                $value = implode(', ', array_filter($values));
            }
            
        } else {
            $value = $field->fetchChangesetValue($artifact->getId(), $row['last_changeset_id'], null);
        }
        
        return $value;
    }
    
    private function getArtifactLinkTitle($id) {
        if ($artifact = $this->artifact_factory->getArtifactByIdUserCanView($this->user, $id)) {
            return $artifact->getTitle();
        }
        return '';
    }
    
    private function getFieldFromReportField(Tracker_Report_Field $report_field, Tracker $tracker) {
        if ($this->isASharedField($report_field)) {
            return $this->factory->getFieldFromTrackerAndSharedField($tracker, $report_field);
        } else {
            return $report_field;
        }
    }
    
    private function isASharedField(Tracker_Report_Field $report_field) {
        return !(  $report_field instanceof Tracker_CrossSearch_SemanticTitleReportField 
                || $report_field instanceof Tracker_CrossSearch_SemanticStatusReportField 
                || $report_field instanceof Tracker_CrossSearch_ArtifactReportField
        );
    }

    public function fetchResultActions() {
        return '<p class="tree-view-actions"></p>';
    }

}
?>

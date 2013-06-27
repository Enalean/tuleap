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

require_once 'common/TreeNode/InjectPaddingInTreeNodeVisitor.class.php';

class Tracker_Hierarchy_Presenter {
    
    // Manage translation
    public $__ = array(__CLASS__, '__trans');
    
    /**
     * @var Tracker_Hierarchy_HierarchicalTracker
     */
    public $tracker;
    
    /**
     * @var Array of Tracker
     */
    public $possible_children;
    
    /**
     * @var TreeNode
     */
    public $hierarchy;
    
    /**
     * @var string
     */
    public $tracker_name;
    
    public function __construct(Tracker_Hierarchy_HierarchicalTracker $tracker, array $possible_children, TreeNode $hierarchy ) {
        $this->tracker           = $tracker;
        $this->tracker_name      = $tracker->getUnhierarchizedTracker()->getName();
        $this->possible_children = array_values($possible_children);
        $this->hierarchy         = $hierarchy;
        $visitor                 = new TreeNode_InjectPaddingInTreeNodeVisitor();
        $this->hierarchy->accept($visitor);
    }
    
    public function getTrackerUrl() {
        return TRACKER_BASE_URL;
    }
    
    public function getTrackerId() {
        return $this->tracker->getId();
    }
    
    public function getManageHierarchyTitle() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin', 'manage_hierarchy_title');
    }
    
    public function getSubmitLabel() {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }
    
    public function getPossibleChildren() {
        $possible_children = array();
        
        foreach ($this->possible_children as $possible_child) {
            $selected = $this->getSelectedAttribute($possible_child);
                    
            $possible_children[] = array('id'       => $possible_child->getId(),
                                         'name'     => $possible_child->getName(),
                                         'selected' => $selected);
        }
        
        return $possible_children;
    }
    
    private function getSelectedAttribute(Tracker $possible_child) {
        if ($this->tracker->hasChild($possible_child)) {
            return 'selected="selected"';
        }
    }
    
    public function __trans($text) {
        $args = explode('|', $text);
        $secondary_key = array_shift($args);
        return $GLOBALS['Language']->getText('plugin_tracker_admin_hierarchy', $secondary_key, $args);
    }
    
}

?>

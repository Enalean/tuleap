<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

class Tracker_Hierarchy_Presenter {

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
     * @var array
     */
    public $trackers_not_in_hierarchy;

    /**
     * @var bool
     */
    public $has_trackers_not_in_hierarchy;

    /**
     * @var bool
     */
    public $cannot_be_used_in_hierarchy;

    /**
     * @var String
     */
    public $current_full_hierarchy_title;

    /**
     * @var String
     */
    public $edit_children_title;

    /**
     * @var String
     */
    public $cannot_be_used;

    /**
     * @var bool
     */
    public $can_be_defined;

    /**
     * @var string
     */
    public $disabled_hierarchy_title;

    /**
     * @var string
     */
    public $disabled_hierarchy_description;

    /**
     * @var String
     */
    public $hierarchy_title;

    public function __construct(
        Tracker_Hierarchy_HierarchicalTracker $tracker,
        array $possible_children,
        TreeNode $hierarchy,
        array $trackers_not_in_hierarchy,
        $is_child_is_disabled
    ) {
        $this->tracker           = $tracker;
        $this->possible_children = array_values($possible_children);
        $this->hierarchy         = $hierarchy;

        $this->trackers_not_in_hierarchy     = $trackers_not_in_hierarchy;
        $this->has_trackers_not_in_hierarchy = count($trackers_not_in_hierarchy) > 0;
        $this->cannot_be_used_in_hierarchy   = in_array(
            $tracker->getUnhierarchizedTracker(),
            $this->trackers_not_in_hierarchy
        );

        $visitor = new TreeNode_InjectPaddingInTreeNodeVisitor();
        $this->hierarchy->accept($visitor);
        usort($this->trackers_not_in_hierarchy, array($this, 'sortTrackerAlphabetically'));

        $this->current_full_hierarchy_title = $GLOBALS['Language']->getText(
            'plugin_tracker_admin_hierarchy',
            'current_full_hierarchy_title'
        );

        $this->edit_children_title = $GLOBALS['Language']->getText(
            'plugin_tracker_admin_hierarchy',
            'edit_children_title',
            $tracker->getUnhierarchizedTracker()->getName()
        );

        $this->cannot_be_used = $GLOBALS['Language']->getText(
            'plugin_tracker_admin_hierarchy',
            'cannot_be_used'
        );

        $this->can_be_defined = ! $is_child_is_disabled;

        $this->disabled_hierarchy_title       = dgettext('tuleap-tracker', 'The tracker hierarchy cannot be defined.');
        $this->disabled_hierarchy_description = dgettext(
            'tuleap-tracker',
            'Please reactivate the "_is_child" artifact link type to be able to define the tracker hierarchy.'
        );

        $this->hierarchy_title = dgettext('tuleap-tracker', 'Hierarchy');
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

    private function sortTrackerAlphabetically(Tracker $a, Tracker $b) {
        return strnatcasecmp($a->getName(), $b->getName());
    }
}
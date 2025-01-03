<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

class Tracker_Hierarchy_Presenter
{
    /**
     * @var Tracker_Hierarchy_HierarchicalTracker
     */
    public $tracker;
    /**
     * @var string
     */
    public $tracker_being_edited_name;

    /**
     * @var Array of Tracker
     */
    public $possible_children;

    /**
     * @var TreeNode
     */
    public $hierarchy;

    /**
     * @var String
     */
    public $current_full_hierarchy_title;

    /**
     * @var String
     */
    public $hierarchy_title;

    /**
     * @var string
     */
    public $tracker_used_in_trigger_rules_names;
    /**
     * @var int
     */
    public $tracker_used_in_trigger_rules_nb;

    /**
     * @param Tracker[] $trackers_used_in_trigger_rules
     */
    public function __construct(
        Tracker_Hierarchy_HierarchicalTracker $tracker,
        array $possible_children,
        TreeNode $hierarchy,
        array $trackers_used_in_trigger_rules,
    ) {
        $this->tracker           = $tracker;
        $this->possible_children = array_values($possible_children);
        $this->hierarchy         = $hierarchy;

        $visitor = new TreeNode_InjectPaddingInTreeNodeVisitor();
        $this->hierarchy->accept($visitor);

        $this->current_full_hierarchy_title = dgettext('tuleap-tracker', 'Current Full Hierarchy');

        $this->tracker_being_edited_name = $tracker->getUnhierarchizedTracker()->getName();

        $this->hierarchy_title = dgettext('tuleap-tracker', 'Hierarchy');

        $this->tracker_used_in_trigger_rules_names = implode(
            ', ',
            array_map(
                static function (Tracker $tracker): string {
                    return $tracker->getName();
                },
                $trackers_used_in_trigger_rules
            )
        );
        $this->tracker_used_in_trigger_rules_nb    = count($trackers_used_in_trigger_rules);
    }

    public function getTrackerUrl()
    {
        return TRACKER_BASE_URL;
    }

    public function getTrackerId()
    {
        return $this->tracker->getId();
    }

    public function getManageHierarchyTitle()
    {
        return dgettext('tuleap-tracker', 'Manage hierarchy of tracker');
    }

    public function getSubmitLabel()
    {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function getPossibleChildren()
    {
        $possible_children = [];

        foreach ($this->possible_children as $possible_child) {
            $selected = $this->getSelectedAttribute($possible_child);

            $possible_children[] = ['id'       => $possible_child->getId(),
                'name'     => $possible_child->getName(),
                'selected' => $selected,
            ];
        }

        return $possible_children;
    }

    private function getSelectedAttribute(Tracker $possible_child)
    {
        if ($this->tracker->hasChild($possible_child)) {
            return 'selected="selected"';
        }
    }
}

<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Hierarchy;

use Tracker;
use Tracker_Hierarchy_HierarchicalTracker;
use TreeNode;
use TreeNode_InjectPaddingInTreeNodeVisitor;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class HierarchyPresenter
{
    public string $tracker_being_edited_name;
    /**
     * @var Tracker[] $possible_children
     */
    public array $possible_children;
    public string $tracker_used_in_trigger_rules_names;
    public int $tracker_used_in_trigger_rules_nb;

    /**
     * @param Tracker[] $trackers_used_in_trigger_rules
     */
    public function __construct(
        private Tracker_Hierarchy_HierarchicalTracker $tracker,
        array $possible_children,
        public TreeNode $hierarchy,
        array $trackers_used_in_trigger_rules,
        public CSRFSynchronizerTokenInterface $csrf_token,
    ) {
        $this->possible_children = array_values($possible_children);

        $visitor = new TreeNode_InjectPaddingInTreeNodeVisitor();
        $this->hierarchy->accept($visitor);

        $this->tracker_being_edited_name = $tracker->getUnhierarchizedTracker()->getName();

        $this->tracker_used_in_trigger_rules_names = implode(
            ', ',
            array_map(
                static fn(Tracker $tracker): string => $tracker->getName(),
                $trackers_used_in_trigger_rules
            )
        );
        $this->tracker_used_in_trigger_rules_nb    = count($trackers_used_in_trigger_rules);
    }

    public function getTrackerId(): int
    {
        return $this->tracker->getId();
    }

    public function getSubmitLabel(): string
    {
        return dgettext('tuleap-tracker', 'Submit');
    }

    public function getPossibleChildren(): array
    {
        $possible_children = [];

        foreach ($this->possible_children as $possible_child) {
            $selected = $this->getSelectedAttribute($possible_child);

            $possible_children[] = [
                'id'       => $possible_child->getId(),
                'name'     => $possible_child->getName(),
                'selected' => $selected,
            ];
        }

        return $possible_children;
    }

    private function getSelectedAttribute(Tracker $possible_child): string
    {
        if ($this->tracker->hasChild($possible_child)) {
            return 'selected="selected"';
        }
        return '';
    }
}

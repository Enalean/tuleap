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

/** A "hierarchy-aware" tracker.
 *
 * This class handles the hierarchy aspect of a tracker.
 * It was written while I was not aware of TreeNode, and was aimed at becoming
 * a more generic hierarchy node representation.
 *
 * We should probably remove it, and use TreeNode Instead.
 */
class Tracker_Hierarchy_HierarchicalTracker
{

    /**
     * @var Tracker
     */
    private $unhierarchizedTracker;

    /**
     * @var Array of Tracker
     */
    private $children;

    public function __construct(Tracker $unhierarchizedTracker, array $children)
    {
        $this->unhierarchizedTracker   = $unhierarchizedTracker;
        $this->children = $children;
    }

    public function getUnhierarchizedTracker()
    {
        return $this->unhierarchizedTracker;
    }

    public function getId()
    {
        return $this->unhierarchizedTracker->getId();
    }

    public function getProject()
    {
        return $this->unhierarchizedTracker->getProject();
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function hasChild(Tracker $tracker)
    {
        return in_array($tracker, $this->children);
    }
}

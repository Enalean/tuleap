<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Artifact\Artifact;

/**
 * I am a Backlog Item
 */
interface AgileDashboard_Milestone_Backlog_IBacklogItem
{
    public const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';

    public function setInitialEffort($value);

    public function getInitialEffort();

    public function setStatus($status, $status_semantic);

    public function getStatus();

    public function setHasChildren($has_children);

    public function id();

    public function title(): string;

    public function getShortType(): string;

    public function color(): string;

    public function hasChildren();

    public function xRef();

    public function getParent();

    public function setParent(Artifact $parent);

    /**
     * @return Artifact
     */
    public function getArtifact();

    /**
     * @return bool
     */
    public function isInconsistent();

    public function getNormalizedStatusLabel();

    public function isOpen();

    public function getRemainingEffort();

    public function setRemainingEffort($value);
}

<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Trigger;

use Override;
use Tracker_Workflow_Trigger_RulesManager;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Tracker;

final readonly class ParentsTriggersUsageByFieldProvider implements ProvideParentsTriggersUsageByField
{
    public function __construct(
        private Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        private TriggersDao $dao,
        private ParentInHierarchyRetriever $parent_in_hierarchy_retriever,
    ) {
    }

    #[Override]
    public function isFieldUsedInParentTrackerTriggers(TrackerField $field): bool
    {
        return $this->trigger_rules_manager->isUsedInTrigger($field) && $this->dao->isTrackerTargetOfTriggers($field->getTrackerId());
    }

    /**
     * @return Option<Tracker>
     */
    #[Override]
    public function getParentTracker(TrackerField $field): Option
    {
        return $this->parent_in_hierarchy_retriever->getParentTracker($field->getTracker());
    }
}

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
use Tuleap\Tracker\FormElement\Field\TrackerField;

final readonly class TriggersUsageByFieldProvider implements ProvideTriggersUsageByField
{
    public function __construct(private Tracker_Workflow_Trigger_RulesManager $rule_trigger, private TriggersDao $dao)
    {
    }

    #[Override]
    public function isFieldUsedInCurrentTrackerTriggers(TrackerField $field): bool
    {
        return $this->rule_trigger->isUsedInTrigger($field) &&
            $this->dao->isTrackerSourceOfTriggers($field->getTrackerId());
    }
}

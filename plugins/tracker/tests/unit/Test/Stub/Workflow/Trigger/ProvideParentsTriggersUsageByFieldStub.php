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

namespace Tuleap\Tracker\Test\Stub\Workflow\Trigger;

use Override;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\Trigger\ProvideParentsTriggersUsageByField;

final readonly class ProvideParentsTriggersUsageByFieldStub implements ProvideParentsTriggersUsageByField
{
    /**
     * @param Option<Tracker> $tracker
     */
    private function __construct(private bool $has_parent_triggers, private Option $tracker)
    {
    }

    public static function withParentTriggers(): self
    {
        return new self(true, Option::fromValue(TrackerTestBuilder::aTracker()->build()));
    }

    public static function withoutParentTriggers(): self
    {
        return new self(false, Option::nothing(Tracker::class));
    }

    #[Override]
    public function isFieldUsedInParentTrackerTriggers(TrackerField $field): bool
    {
        return $this->has_parent_triggers;
    }

    #[Override]
    public function getParentTracker(TrackerField $field): Option
    {
        return $this->tracker;
    }
}

<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

final readonly class TrackerChecker
{
    public function __construct(private EventDispatcherInterface $event_manager)
    {
    }

    /**
     * @throws PostActionNonEligibleForTrackerException
     */
    public function checkPostActionsAreEligibleForTracker(Tracker $tracker, PostActionCollection $post_actions)
    {
        $event = new CheckPostActionsForTracker($tracker, $post_actions);
        $this->event_manager->dispatch($event);

        if (! $event->arePostActionsEligible()) {
            throw new PostActionNonEligibleForTrackerException($event->getErrorMessage());
        }
    }
}

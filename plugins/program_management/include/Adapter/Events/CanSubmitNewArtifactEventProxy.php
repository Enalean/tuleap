<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Events\CanSubmitNewArtifactEvent;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;

final class CanSubmitNewArtifactEventProxy implements CanSubmitNewArtifactEvent
{
    private function __construct(private TrackerReference $tracker_reference, private CanSubmitNewArtifact $event, private UserReference $user_identifier)
    {
    }

    public static function buildFromEvent(CanSubmitNewArtifact $event): self
    {
        return new self(TrackerReferenceProxy::fromTracker($event->getTracker()), $event, UserProxy::buildFromPFUser($event->getUser()));
    }

    #[\Override]
    public function getTrackerReference(): TrackerReference
    {
        return $this->tracker_reference;
    }

    #[\Override]
    public function disableArtifactSubmission(): void
    {
        $this->event->disableArtifactSubmission();
    }

    #[\Override]
    public function getUserIdentifier(): UserReference
    {
        return $this->user_identifier;
    }
}

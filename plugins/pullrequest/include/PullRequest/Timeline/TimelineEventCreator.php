<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Timeline;

use PFUser;
use Tuleap\PullRequest\PullRequest;

class TimelineEventCreator
{
    public function __construct(private Dao $timeline_dao)
    {
    }

    public function storeUpdateEvent(PullRequest $pull_request, PFUser $user): void
    {
        $this->timeline_dao->save($pull_request->getId(), $user->getId(), time(), TimelineGlobalEvent::UPDATE);
    }

    public function storeRebaseEvent(PullRequest $pull_request, PFUser $user): void
    {
        $this->timeline_dao->save($pull_request->getId(), $user->getId(), time(), TimelineGlobalEvent::REBASE);
    }

    public function storeMergeEvent(PullRequest $pull_request, PFUser $user): void
    {
        $this->timeline_dao->save($pull_request->getId(), $user->getId(), time(), TimelineGlobalEvent::MERGE);
    }

    public function storeAbandonEvent(PullRequest $pull_request, PFUser $user): void
    {
        $this->timeline_dao->save($pull_request->getId(), $user->getId(), time(), TimelineGlobalEvent::ABANDON);
    }

    public function storeReopenEvent(PullRequest $pull_request, PFUser $user): void
    {
        $this->timeline_dao->save($pull_request->getId(), $user->getId(), time(), TimelineGlobalEvent::REOPEN);
    }
}

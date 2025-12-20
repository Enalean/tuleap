<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AICrossTracker\Stub;

use Override;
use Tuleap\AICrossTracker\Assistant\ThreadID;
use Tuleap\AICrossTracker\Assistant\ThreadStorage;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\Option\Option;

final class ThreadStorageStub implements ThreadStorage
{
    private(set) \PFUser $user;
    private(set) int $widget_id;
    private bool $thread_exists = true;

    public function __construct(private readonly DatabaseUUIDFactory $uuid_factory)
    {
    }

    public function withoutExistingThread(): self
    {
        $this->thread_exists = false;
        return $this;
    }

    #[Override]
    public function createNew(\PFUser $user, ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget): ThreadID
    {
        $this->user      = $user;
        $this->widget_id = $widget->widget_id;
        return new ThreadID(
            $this->uuid_factory->buildUUIDFromBytesData(
                $this->uuid_factory->buildUUIDBytes()
            )
        );
    }

    #[Override]
    public function threadExists(\PFUser $user, ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, ThreadID $thread_id): Option
    {
        if ($this->thread_exists) {
            return Option::fromValue($thread_id);
        }
        return Option::nothing(ThreadID::class);
    }
}

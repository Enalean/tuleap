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

namespace Tuleap\AICrossTracker\Assistant;

use Override;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

final class ThreadStorageDao extends DataAccessObject implements ThreadStorage
{
    #[Override]
    public function createNew(\PFUser $user, ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget): ThreadID
    {
        $id = $this->uuid_factory->buildUUIDBytes();
        $this->getDB()->insert(
            'ai_crosstracker_completion_thread',
            [
                'id' => $id,
                'user_id' => $user->getId(),
                'widget_id' => $widget->widget_id,
            ]
        );
        return new ThreadID(
            $this->uuid_factory->buildUUIDFromBytesData($id)
        );
    }

    #[Override]
    public function threadExists(\PFUser $user, ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, ThreadID $thread_id): Option
    {
        $row = $this->getDB()->row(
            <<<EOT
            SELECT 1
            FROM ai_crosstracker_completion_thread
            WHERE user_id = ?
            AND widget_id = ?
            AND id = ?
            EOT,
            $user->getId(),
            $widget->widget_id,
            $thread_id->uuid->getBytes(),
        );
        if ($row !== null) {
            return Option::fromValue($thread_id);
        }
        return Option::nothing(ThreadID::class);
    }
}

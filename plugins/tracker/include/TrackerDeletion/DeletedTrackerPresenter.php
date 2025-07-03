<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\TrackerDeletion;

final class DeletedTrackerPresenter
{
    public int $id;
    public string $tracker;
    public \CSRFSynchronizerToken $csrf_token;

    public function __construct(
        int $tracker_id,
        string $tracker_name,
        public int $project_id,
        public string $project_name,
        public string $deletion_date,
        public \CSRFSynchronizerToken $restore_token,
    ) {
        $this->id         = $tracker_id;
        $this->tracker    = $tracker_name;
        $this->csrf_token = $restore_token;
    }
}

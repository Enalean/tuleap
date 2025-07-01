<?php
/*
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

declare(strict_types=1);

namespace Tuleap\Tracker\TrackerDeletion;

use CSRFSynchronizerToken;

final readonly class DeleteTrackerPresenterBuilder
{
    public function __construct(private TrackerDeletionRetriever $tracker_retriever)
    {
    }

    public function displayDeletedTrackers(): DeletedTrackersListPresenter
    {
        $deleted_trackers = $this->tracker_retriever->getDeletedTrackers();

        $deleted_trackers_presenters = [];
        $tracker_ids_warning         = [];
        $restore_token               = new CSRFSynchronizerToken('/tracker/admin/restore.php');

        foreach ($deleted_trackers as $tracker) {
            $project = $tracker->getProject();

            if (! $project || $project->getID() === null) {
                $tracker_ids_warning[] = $tracker->getId();
                continue;
            }

            $project_id    = (int) $project->getId();
            $project_name  = $project->getUnixName();
            $tracker_id    = $tracker->getId();
            $tracker_name  = $tracker->getName();
            $deletion_date = date('d-m-Y', (int) $tracker->deletion_date);

            $deleted_trackers_presenters[] = new DeletedTrackerPresenter(
                $tracker_id,
                $tracker_name,
                $project_id,
                $project_name,
                $deletion_date,
                $restore_token
            );
        }

        return new DeletedTrackersListPresenter(
            $deleted_trackers_presenters,
            $tracker_ids_warning,
            count($deleted_trackers_presenters) > 0
        );
    }
}

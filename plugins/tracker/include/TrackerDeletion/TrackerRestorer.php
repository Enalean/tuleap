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

use HTTPRequest;
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Tracker\RetrieveTracker;

final readonly class TrackerRestorer
{
    public function __construct(private RetrieveTracker $tracker_factory, private RestoreDeletedTracker $dao)
    {
    }

    public function restoreTracker(HTTPRequest $request, BaseLayout $response): void
    {
        $tracker_id = $request->get('tracker_id');
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        $tracker_name = $tracker->getName();
        $this->dao->restoreTrackerMarkAsDeleted((int) $tracker_id);
        $response->addFeedback('info', sprintf(dgettext('tuleap-tracker', 'The tracker \'%1$s\' has been properly restored'), $tracker_name));
        $response->redirect('/tracker/admin/restore.php');
    }
}

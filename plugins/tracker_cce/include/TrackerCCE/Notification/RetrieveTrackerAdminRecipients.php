<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Notification;

use PFUser;
use Tracker;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectAdminsUGroupRetriever;
use Tuleap\Project\UGroupRetriever;

final class RetrieveTrackerAdminRecipients implements TrackerAdminRecipientsRetriever
{
    public function __construct(
        private readonly ProjectAdminsUGroupRetriever $admins_ugroup_retriever,
        private readonly UGroupRetriever $ugroup_retriever,
    ) {
    }

    /**
     * @return Ok<non-empty-array<PFUser>> | Err<Fault>
     */
    public function retrieveRecipients(Tracker $tracker): Ok | Err
    {
        $admin_ugroup = $this->admins_ugroup_retriever->getProjectAdminsUGroup($tracker->getProject());
        $result       = $admin_ugroup->getMembers();

        $tracker_ugroups = $tracker->getAuthorizedUgroupsByPermissionType();
        foreach ($tracker_ugroups as $permission => $tracker_ugroup) {
            if ($permission === Tracker::PERMISSION_ADMIN) {
                foreach ($tracker_ugroup as $ugroup_id) {
                    $ugroup = $this->ugroup_retriever->getUGroup($tracker->getProject(), $ugroup_id);
                    if ($ugroup) {
                        $result = [...$result, ...$ugroup->getMembers()];
                    }
                }
            }
        }

        $result = array_unique($result);

        if (empty($result)) {
            return Result::err(Fault::fromMessage('No tracker administrator found'));
        }

        return Result::ok($result);
    }
}

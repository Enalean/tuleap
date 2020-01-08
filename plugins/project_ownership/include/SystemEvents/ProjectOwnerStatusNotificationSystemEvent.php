<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\SystemEvents;

use Tuleap\Project\ProjectStatusMapper;
use Tuleap\ProjectOwnership\Exceptions\FailedToNotifyProjectMemberException;
use Tuleap\ProjectOwnership\Notification\Sender;

class ProjectOwnerStatusNotificationSystemEvent extends \SystemEvent
{
    public const NAME = 'PROJECT_OWNER_STATUS_NOTIFICATION';

    /**
     * @var Sender
     */
    private $mail_sender;

    public function verbalizeParameters($with_link)
    {
        list($group_id, $new_status) = $this->getParametersAsArray();

        $status_label = ProjectStatusMapper::getProjectStatusLabelFromStatusFlag($new_status);

        return "Notify members of project " . $this->verbalizeProjectId($group_id, $with_link) . " is now " . $status_label;
    }

    public function process()
    {
        list($group_id, $new_status) = $this->getParametersAsArray();

        try {
            $this->notifyProjectMembers($group_id, $new_status);
        } catch (FailedToNotifyProjectMemberException $exception) {
            $this->error($exception->getMessage());
        }

        $this->done();

        return true;
    }

    public function injectDependencies(Sender $mail_sender)
    {
        $this->mail_sender = $mail_sender;
    }

    /**
     * @throws FailedToNotifyProjectMemberException
     */
    private function notifyProjectMembers($project_id, $status)
    {
        $this->mail_sender->sendNotification($project_id, $status);
    }
}

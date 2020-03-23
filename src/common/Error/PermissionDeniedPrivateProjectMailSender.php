<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Error;

use PFUser;
use Project;

final class PermissionDeniedPrivateProjectMailSender extends PermissionDeniedMailSender
{
    protected function getPermissionDeniedMailBody(
        Project $project,
        PFUser $user,
        string $href_approval,
        string $message_to_admin,
        string $link
    ): string {
        return $GLOBALS['Language']->getText(
            'include_exit',
            'mail_content_private_project',
            [
                $user->getRealName(),
                $user->getName(),
                $link,
                $project->getPublicName(),
                $href_approval,
                $message_to_admin,
                $user->getEmail()
            ]
        );
    }

    protected function getPermissionDeniedMailSubject(Project $project, PFUser $user): string
    {
        return $GLOBALS['Language']->getText(
            'include_exit',
            'mail_subject_private_project',
            [$project->getPublicName(), $user->getRealName()]
        );
    }
}

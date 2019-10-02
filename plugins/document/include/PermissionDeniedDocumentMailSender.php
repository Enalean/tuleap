<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Document;

use PFUser;
use Project;
use Tuleap\Error\PermissionDeniedMailSender;

class PermissionDeniedDocumentMailSender extends PermissionDeniedMailSender
{
    /**
     * Returns the type of the error to manage
     *
     * @return String
     */
    public function getType()
    {
        return 'mail_content_docman_permission_denied';
    }

    protected function getPermissionDeniedMailBody(
        Project $project,
        PFUser $user,
        string $href_approval,
        string $message_to_admin,
        string $link
    ) {
        return $GLOBALS['Language']->getText(
            'plugin_docman',
            'mail_content_docman_permission_denied',
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

    protected function getPermissionDeniedMailSubject(Project $project, PFUser $user)
    {
        return $GLOBALS['Language']->getText(
            'plugin_docman',
            'mail_subject_docman_permission_denied',
            [$project->getPublicName(), $user->getRealName()]
        );
    }
}

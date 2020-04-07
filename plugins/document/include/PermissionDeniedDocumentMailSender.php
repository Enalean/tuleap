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

declare(strict_types=1);

namespace Tuleap\Document;

use PFUser;
use Project;
use Tuleap\Error\PermissionDeniedMailSender;

class PermissionDeniedDocumentMailSender extends PermissionDeniedMailSender
{
    protected function getPermissionDeniedMailBody(
        Project $project,
        PFUser $user,
        string $href_approval,
        string $message_to_admin,
        string $link
    ): string {
        return sprintf(dgettext('tuleap-docman', 'Dear document manager,

%1$s (login: %2$s) requests access to the following document in project "%4$s":
<%3$s>

%1$s wrote a message for you:
%6$s

Someone set permissions on this item, preventing users of having access to this resource.
If you decide to accept the request, please take the appropriate actions to grant him permission and communicate that information to the requester.
Otherwise, please inform the requester (%7$s) that he will not get access to the requested data.
--
%1$s.'), $user->getRealName(), $user->getName(), $link, $project->getPublicName(), $href_approval, $message_to_admin, $user->getEmail());
    }

    protected function getPermissionDeniedMailSubject(Project $project, PFUser $user): string
    {
        return sprintf(dgettext('tuleap-docman', '%2$s requests access to a document in "%1$s"'), $project->getPublicName(), $user->getRealName());
    }
}

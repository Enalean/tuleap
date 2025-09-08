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

declare(strict_types=1);

namespace Tuleap\Project;

use PFUser;
use Project;

/**
 * /!\ Warning /!\
 *
 * You will come here and think that the implementation bellow is incorrect because of
 * Public inc. Restricted permission.
 *
 * The default implementation is correct here because this status only apply on Git,
 * MediaWiki and Dashboard access. Therefore, unless you are dealing with code in those
 * plugins, you should rely on this implementation.
 */
class RestrictedUserCanAccessProjectVerifier implements RestrictedUserCanAccessVerifier
{
    #[\Override]
    public function isRestrictedUserAllowedToAccess(PFUser $user, ?Project $project = null): bool
    {
        return $project && $project->isSuperPublic();
    }
}

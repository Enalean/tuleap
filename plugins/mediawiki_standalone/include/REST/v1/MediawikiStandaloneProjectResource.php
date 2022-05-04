<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\REST\v1;

use Tuleap\REST\Header;

final class MediawikiStandaloneProjectResource
{
    /**
     * @url OPTIONS {id}/mediawiki_standalone
     *
     * @param int $id Id of the project
     */
    public function options(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get Mediawiki permissions of the current user
     *
     * @url    GET {id}/mediawiki_standalone_permissions
     *
     * @oauth2-scope read:mediawiki_standalone
     *
     * @param int $id Id of the project
     */
    protected function getPermissions(int $id): GetPermissionsRepresentation
    {
        return new GetPermissionsRepresentation(new PermissionsRepresentation(false, false, false, false));
    }
}

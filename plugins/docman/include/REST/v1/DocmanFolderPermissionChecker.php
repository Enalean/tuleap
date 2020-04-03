<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_PermissionsManager;
use Tuleap\REST\I18NRestException;

class DocmanFolderPermissionChecker
{
    /**
     * @var Docman_PermissionsManager
     */
    private $permissions_manager;

    public function __construct(Docman_PermissionsManager $permissions_manager)
    {
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @throws I18NRestException
     */
    public function checkUserCanWriteFolder(\PFUser $current_user, int $folder_id): void
    {
        if (! $this->permissions_manager->userCanWrite($current_user, $folder_id)) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-docman', "You are not allowed to write on folder with id '%d'"),
                    $folder_id
                )
            );
        }
    }
}

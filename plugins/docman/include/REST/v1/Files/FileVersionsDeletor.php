<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Files;

use Luracast\Restler\RestException;
use Tuleap\Docman\Version\IDeleteVersion;
use Tuleap\Docman\Version\IRetrieveVersion;
use Tuleap\Docman\Version\VersionNotFoundException;

final class FileVersionsDeletor
{
    public function __construct(
        private IRetrieveVersion $version_retriever,
        private IDeleteVersion $version_deletor,
        private \Docman_ItemFactory $item_factory,
    ) {
    }

    public function delete(int $id, \PFUser $user): void
    {
        try {
            $version = $this->version_retriever->getVersion($id);
        } catch (VersionNotFoundException $exception) {
            throw new RestException(404, "Version to delete not found");
        }

        $item = $this->item_factory->getItemFromDb((int) $version->getItemId());
        if (! $item) {
            throw new RestException(404, "Version to delete not found");
        }

        $permissions_manager = \Docman_PermissionsManager::instance((int) $item->getGroupId());
        if (! $permissions_manager->userCanDelete($user, $item)) {
            throw new RestException(404, "Version to delete not found");
        }

        if (! $this->version_deletor->deleteSpecificVersion($item, (int) $version->getNumber())) {
            throw new UnableToDeleteVersionException();
        }
    }
}

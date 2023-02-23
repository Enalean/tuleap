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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Luracast\Restler\RestException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Version\IRetrieveVersion;
use Tuleap\Docman\Version\VersionNotFoundException;

final class EmbeddedFileVersionContentRetriever
{
    public function __construct(
        private IRetrieveVersion $version_retriever,
        private \Docman_ItemFactory $item_factory,
        private \EventManager $event_manager,
    ) {
    }

    public function getContent(int $id, \PFUser $user): VersionContentRepresentation
    {
        try {
            $version = $this->version_retriever->getVersion($id);
        } catch (VersionNotFoundException $exception) {
            throw new RestException(404);
        }

        $item = $this->item_factory->getItemFromDb((int) $version->getItemId());
        if (! $item) {
            throw new RestException(404);
        }

        if (! $item->accept(new DoesItemHasExpectedTypeVisitor(\Docman_EmbeddedFile::class))) {
            throw new RestException(
                400,
                'Item is not an embedded file'
            );
        }

        $permissions_manager = \Docman_PermissionsManager::instance((int) $item->getGroupId());
        if (! $permissions_manager->userCanRead($user, $item->getId())) {
            throw new RestException(404);
        }

        $this->event_manager->processEvent(
            'plugin_docman_event_access',
            [
                'group_id' => $item->getGroupId(),
                'item'     => $item,
                'version'  => $version->getNumber(),
                'user'     => $user,
            ]
        );

        $version_file_path = $version->getPath();
        if ($version_file_path === null || $version_file_path === '') {
            throw new \RuntimeException(sprintf('No file path found to access version #%d', $id));
        }

        return new VersionContentRepresentation(
            (int) $version->getNumber(),
            \Psl\File\read($version_file_path),
        );
    }
}

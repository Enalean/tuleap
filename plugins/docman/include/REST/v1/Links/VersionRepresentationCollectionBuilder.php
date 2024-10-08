<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

namespace Tuleap\Docman\REST\v1\Links;

use Tuleap\Docman\Version\LinkVersionDao;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final class VersionRepresentationCollectionBuilder
{
    public function __construct(
        private LinkVersionDao $dao,
        private RetrieveUserById $user_retriever,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function buildVersionsCollection(
        \Docman_Link $item,
        int $limit,
        int $offset,
    ): PaginatedLinkVersionRepresentationCollection {
        $dar      = $this->dao->searchByItemId($item->getId(), $offset, $limit);
        $versions = [];
        foreach ($dar as $row) {
            $version = new \Docman_LinkVersion($row);

            $author = $this->user_retriever->getUserById((int) $version->getAuthorId());
            if (! $author) {
                continue;
            }

            $versions[] = LinkVersionRepresentation::build(
                (int) $version->getId(),
                $row['number'],
                $row['label'],
                (int) $item->getGroupId(),
                (int) $item->getId(),
                $author,
                (new \DateTimeImmutable())->setTimestamp((int) $version->getDate()),
                (string) $version->getChangelog(),
                $this->provide_user_avatar_url,
            );
        }

        return new PaginatedLinkVersionRepresentationCollection(
            $versions,
            $this->dao->countByItemId($item->getId())
        );
    }
}

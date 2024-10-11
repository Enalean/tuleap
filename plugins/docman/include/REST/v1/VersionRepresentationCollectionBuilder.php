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

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\ApprovalTable\TableFactoryForFileBuilder;
use Tuleap\Docman\Item\PaginatedFileVersionRepresentationCollection;
use Tuleap\Docman\REST\v1\Files\FileVersionRepresentation;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Docman\View\DocmanViewURLBuilder;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final class VersionRepresentationCollectionBuilder
{
    public function __construct(
        private VersionDao $docman_version_dao,
        private CoAuthorDao $co_author_dao,
        private RetrieveUserById $user_retriever,
        private TableFactoryForFileBuilder $table_factory_builder,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function buildVersionsCollection(
        \Docman_File $item,
        int $limit,
        int $offset,
    ): PaginatedFileVersionRepresentationCollection {
        $table_factory = $this->table_factory_builder->getTableFactoryForFile($item);

        $dar      = $this->docman_version_dao->searchByItemId($item->getId(), $offset, $limit);
        $versions = [];
        foreach ($dar as $row) {
            $version = new \Docman_Version();
            $version->initFromRow($row);

            $table         = $table_factory->getTableFromVersion($version);
            $approval_href = $table
                ? DocmanViewURLBuilder::buildActionUrl(
                    $item,
                    ['default_url' => '/plugins/docman/?'],
                    [
                        'group_id' => $item->getGroupId(),
                        'action'   => 'details',
                        'section'  => 'approval',
                        'id'       => $item->getId(),
                        'version'  => $version->getNumber(),
                    ],
                    true,
                )
                : null;

            $author = $this->user_retriever->getUserById((int) $version->getAuthorId());
            if (! $author) {
                continue;
            }

            $coauthors = array_values(
                array_filter(
                    array_map(
                        fn (array $row): ?\PFUser => $this->user_retriever->getUserById($row['user_id']),
                        $this->co_author_dao->searchByVersionId((int) $version->getId()),
                    )
                )
            );

            $versions[] = FileVersionRepresentation::build(
                (int) $version->getId(),
                $row['number'],
                $row['label'],
                $row['filename'],
                (int) $item->getGroupId(),
                (int) $item->getId(),
                $approval_href,
                $author,
                $coauthors,
                (new \DateTimeImmutable())->setTimestamp((int) $version->getDate()),
                (string) $version->getChangelog(),
                $version->getAuthoringTool(),
                $this->provide_user_avatar_url,
            );
        }

        return new PaginatedFileVersionRepresentationCollection(
            $versions,
            $this->docman_version_dao->countByItemId($item->getId())
        );
    }
}

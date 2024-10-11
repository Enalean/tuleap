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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Tuleap\Docman\ApprovalTable\TableFactoryForFileBuilder;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Docman\View\DocmanViewURLBuilder;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final class VersionRepresentationCollectionBuilder
{
    public function __construct(
        private VersionDao $dao,
        private RetrieveUserById $user_retriever,
        private TableFactoryForFileBuilder $table_factory_builder,
        private ProjectByIDFactory $project_factory,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function buildVersionsCollection(
        \Docman_EmbeddedFile $item,
        int $limit,
        int $offset,
    ): PaginatedEmbeddedFileVersionRepresentationCollection {
        $table_factory = $this->table_factory_builder->getTableFactoryForFile($item);

        $project        = $this->project_factory->getProjectById((int) $item->getGroupId());
        $open_item_href = '/plugins/document/' . urlencode($project->getUnixNameLowerCase())
            . '/folder/' . urlencode((string) $item->getParentId())
            . '/' . urlencode((string) $item->getId());

        $dar      = $this->dao->searchByItemId($item->getId(), $offset, $limit);
        $versions = [];
        foreach ($dar as $row) {
            $version = new \Docman_Version($row);

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

            $open_href = $open_item_href . '/' . urlencode((string) $version->getId());

            $author = $this->user_retriever->getUserById((int) $version->getAuthorId());
            if (! $author) {
                continue;
            }

            $versions[] = EmbeddedFileVersionRepresentation::build(
                (int) $version->getId(),
                (int) $version->getNumber(),
                $version->getLabel(),
                (int) $item->getGroupId(),
                (int) $item->getId(),
                $approval_href,
                $open_href,
                $author,
                (new \DateTimeImmutable())->setTimestamp((int) $version->getDate()),
                (string) $version->getChangelog(),
                $this->provide_user_avatar_url,
            );
        }

        return new PaginatedEmbeddedFileVersionRepresentationCollection(
            $versions,
            $this->dao->countByItemId($item->getId())
        );
    }
}

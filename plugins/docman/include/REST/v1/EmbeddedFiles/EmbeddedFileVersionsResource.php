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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\REST\v1\Files\FileVersionsDeletor;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Docman\Version\VersionRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

final class EmbeddedFileVersionsResource extends AuthenticatedResource
{
    public const string NAME = 'docman_embedded_file_versions';

    /**
     * @url OPTIONS {id}
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsDelete();
    }

    /**
     * Delete version
     *
     * Delete a version of an embedded file. Please note that the last version of an embedded file cannot be deleted.
     *
     * @url    DELETE {id}
     * @access protected
     * @status 204
     */
    public function deleteId(int $id): void
    {
        $this->checkAccess();

        (new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_EmbeddedFile::class),
            new VersionRetriever(new VersionDao()),
            new \Docman_VersionFactory(),
            new VersionDao(),
            new \Docman_ItemFactory(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
        ))->delete(
            $id,
            \UserManager::instance()->getCurrentUser()
        );
    }

    /**
     * @url OPTIONS {id}/content
     */
    public function optionsContent(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get content
     *
     * Get the content of a specific version of an embedded file.
     *
     * @url    GET {id}/content
     * @access hybrid
     *
     */
    public function getContent(int $id): VersionContentRepresentation
    {
        $this->checkAccess();

        return (new EmbeddedFileVersionContentRetriever(
            new VersionRetriever(new VersionDao()),
            new \Docman_ItemFactory(),
            \EventManager::instance()
        ))->getContent(
            $id,
            \UserManager::instance()->getCurrentUser()
        );
    }
}

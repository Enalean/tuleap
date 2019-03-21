<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Docman\REST;

use Tuleap\Docman\REST\v1\DocmanEmbeddedFilesResource;
use Tuleap\Docman\REST\v1\DocmanFilesResource;
use Tuleap\Docman\REST\v1\DocmanFoldersResource;
use Tuleap\Docman\REST\v1\DocmanItemsResource;

class ResourcesInjector
{
    const NAME          = 'docman_items';
    const FILES_NAME    = 'docman_files';
    const FOLDER_NAME   = 'docman_folders';
    const EMBEDDED_NAME = 'docman_embedded_files';

    public function populate(\Luracast\Restler\Restler $restler)
    {
        $restler->addAPIClass(
            DocmanItemsResource::class,
            self::NAME
        );

        $restler->addAPIClass(
            DocmanFilesResource::class,
            self::FILES_NAME
        );

        $restler->addAPIClass(
            DocmanFoldersResource::class,
            self::FOLDER_NAME
        );

        $restler->addAPIClass(
            DocmanEmbeddedFilesResource::class,
            self::EMBEDDED_NAME
        );
    }
}

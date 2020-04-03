<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use Luracast\Restler\Restler;
use Project;
use Tuleap\Docman\REST\v1\DocmanEmbeddedFilesResource;
use Tuleap\Docman\REST\v1\DocmanEmptyDocumentsResource;
use Tuleap\Docman\REST\v1\DocmanFilesResource;
use Tuleap\Docman\REST\v1\DocmanFoldersResource;
use Tuleap\Docman\REST\v1\DocmanItemsResource;
use Tuleap\Docman\REST\v1\DocmanLinksResource;
use Tuleap\Docman\REST\v1\DocmanWikiResource;
use Tuleap\Docman\REST\v1\ProjectMetadataResource;
use Tuleap\Docman\REST\v1\Service\DocmanServiceResource;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;

class ResourcesInjector
{
    public const NAME                = 'docman_items';
    public const FILES_NAME          = 'docman_files';
    public const FOLDER_NAME         = 'docman_folders';
    public const EMBEDDED_NAME       = 'docman_embedded_files';
    public const WIKI_NAME           = 'docman_wikis';
    public const LINK_NAME           = 'docman_links';
    public const EMPTY_DOCUMENT_NAME = 'docman_empty_documents';

    public function populate(Restler $restler)
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

        $restler->addAPIClass(
            DocmanWikiResource::class,
            self::WIKI_NAME
        );

        $restler->addAPIClass(
            DocmanLinksResource::class,
            self::LINK_NAME
        );

        $restler->addAPIClass(
            DocmanEmptyDocumentsResource::class,
            self::EMPTY_DOCUMENT_NAME
        );

        $restler->addAPIClass(
            ProjectMetadataResource::class,
            ProjectRepresentation::ROUTE
        );
        $restler->addAPIClass(
            DocmanServiceResource::class,
            ProjectRepresentation::ROUTE
        );
    }

    public static function declareProjectResources(array &$resources, Project $project): void
    {
        if (! $project->usesService(\DocmanPlugin::SERVICE_SHORTNAME)) {
            return;
        }
        $resources[] = (new ProjectResourceReference())->build($project, ProjectMetadataResource::RESOURCE_TYPE);
        $resources[] = (new ProjectResourceReference())->build($project, DocmanServiceResource::RESOURCE_TYPE);
    }
}

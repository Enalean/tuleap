<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Docman_ItemFactory;
use Luracast\Restler\RestException;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Document\ArtidocRetriever;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\RESTLogger;
use UserManager;

final class ArtidocResource extends AuthenticatedResource
{
    public const ROUTE      = 'artidoc';
    private const MAX_LIMIT = 50;

    /**
     * @url OPTIONS {id}/sections
     */
    public function optionsSections(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get sections
     *
     * Get sections of an artidoc document
     *
     * @url    GET {id}/sections
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param int $limit Number of elements displayed {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return \Tuleap\Artidoc\REST\v1\ArtidocSectionRepresentation[]
     *
     * @status 200
     * @throws RestException
     */
    public function getSections(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        return $this->getBuilder()
            ->build($id, $limit, $offset, UserManager::instance()->getCurrentUser())
            ->match(
                function (PaginatedArtidocSectionRepresentationCollection $collection) use ($limit, $offset) {
                    Header::sendPaginationHeaders($limit, $offset, $collection->total, self::MAX_LIMIT);
                    return $collection->sections;
                },
                function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
                    throw new RestException(404);
                },
            );
    }

    private function getBuilder(): PaginatedArtidocSectionRepresentationCollectionBuilder
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $dao       = new ArtidocDao();
        $retriever = new ArtidocRetriever(
            \ProjectManager::instance(),
            $dao,
            new Docman_ItemFactory(),
            new DocumentServiceFromAllowedProjectRetriever($plugin),
        );

        $transformer = new RawSectionsToRepresentationTransformer(
            new \Tracker_ArtifactDao(),
            \Tracker_ArtifactFactory::instance(),
        );

        return new PaginatedArtidocSectionRepresentationCollectionBuilder($retriever, $dao, $transformer);
    }
}

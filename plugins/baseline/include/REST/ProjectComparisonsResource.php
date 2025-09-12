<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

use DI\Container;
use Luracast\Restler\RestException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

class ProjectComparisonsResource extends AuthenticatedResource
{
    public const int MAX_PAGINATION_LIMIT = 50;

    /** @var Container */
    private $container;

    public function __construct()
    {
        $this->container = ContainerBuilderFactory::create()->build();
    }

    /**
     * Get baselines comparisons
     *
     * Get all the baselines comparisons of a given project.
     * /!\ Some comparisons may be filtered with security reason, but total count returned represents all available
     * comparisons INCLUDING the ones you're not authorized to see.
     *
     * @url    GET {id}/baselines_comparisons
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements to fetch (not authorized element are hidden, so you may get less element than requested) {@from path} {@min 1} {@max 50}
     * @param int $offset Position of the first element to display (first position is 0). Comparisons are sorted by creation date (most recent first) {@from path}
     *
     * @return ComparisonsPageRepresentation {@type Tuleap\Baseline\REST\ComparisonsPageRepresentation}
     * @throws RestException
     * @throws ForbiddenRestException
     */
    public function getComparisons(int $id, int $limit = self::MAX_PAGINATION_LIMIT, int $offset = 0): ComparisonsPageRepresentation
    {
        $this->checkAccess();

        $page_representation = $this->container
            ->get(ProjectComparisonController::class)
            ->get($id, $limit, $offset);

        Header::sendPaginationHeaders(
            $limit,
            $offset,
            $page_representation->getTotalCount(),
            self::MAX_PAGINATION_LIMIT
        );

        return $page_representation;
    }

    /**
     * @url OPTIONS {id}/baselines_comparisons
     */
    public function options(int $id): void
    {
        Header::allowOptionsGet();
    }
}

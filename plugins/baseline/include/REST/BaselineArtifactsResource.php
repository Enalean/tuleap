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
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

class BaselineArtifactsResource extends AuthenticatedResource
{
    /** @var Container */
    private $container;

    public function __construct()
    {
        $this->container = ContainerBuilderFactory::create()->build();
    }

    /**
     * Get artifacts
     *
     * Get all artifacts contained in a baseline.
     *
     * <p>
     *  By default, only first level (i.e. artifacts linked to baseline artifact).
     *  <br/>
     *  <code>query</code> parameter can be used to retrieve sub levels.
     * </p>
     *
     * <p>
     *  <code>query</code> parameter:
     *  <ul>
     *      <li>format is <code>{"ids":[x,y,z]}</code> where <code>x</code>, <code>y</code> and <code>z</code> are ids of artifacts to retrieve</li>
     *      <li>it must be a URL-encoded JSON object</li>
     *      <li>no more than 100 artifacts can be requested at once with this parameter</li>
     *  </ul>
     * </p>
     *
     * @url    GET {id}/artifacts
     * @access hybrid
     *
     * @param int    $id    Id of the baseline
     * @param string $query JSON object of search criteria properties {@from query}
     *
     * @return BaselineArtifactCollectionRepresentation {@type Tuleap\Baseline\REST\BaselineArtifactCollectionRepresentation}
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 520
     */
    public function getBaselines(int $id, ?string $query = null): BaselineArtifactCollectionRepresentation
    {
        $this->checkAccess();

        return $this->container
            ->get(BaselineArtifactController::class)
            ->get($id, $query);
    }

    /**
     * @url OPTIONS {id}/artifacts
     */
    public function optionsArtifacts($id)
    {
        Header::allowOptionsGet();
    }
}

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
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\User\Password\PasswordExpiredException;

class ComparisonsResource extends AuthenticatedResource
{
    /** @var Container */
    private $container;

    public function __construct()
    {
        $this->container = ContainerBuilderFactory::create()->build();
    }

    /**
     * Create a new baseline comparison.
     *
     * Create a new comparison between two baselines.
     *
     * @url    POST
     * @status 201
     * @access protected
     *
     * @param int    $base_baseline_id        Id of the baseline used as base comparison {@from body}
     * @param int    $compared_to_baseline_id Id of the baseline to be compared {@from body}
     * @param string $name                    Name of the comparison {@from body}
     * @param string $comment                 Comment {@from body}
     *
     * @return Tuleap\Baseline\REST\ComparisonRepresentation
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws ForbiddenRestException
     * @throws NotFoundRestException
     */
    protected function post(
        int $base_baseline_id,
        int $compared_to_baseline_id,
        ?string $name = null,
        ?string $comment = null,
    ): ComparisonRepresentation {
        $this->checkAccess();
        return $this->container
            ->get(ComparisonController::class)
            ->post($name, $comment, $base_baseline_id, $compared_to_baseline_id);
    }

    /**
     * Get a Comparison
     *
     * Get a comparison between two baselines
     *
     * @url    GET /{id}
     * @access hybrid
     *
     * @param int $id The comparison id
     *
     * @return Tuleap\Baseline\REST\ComparisonRepresentation
     * @throws \Rest_Exception_InvalidTokenException
     * @throws I18NRestException 401
     * @throws NotFoundRestException 404
     * @throws PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     * @throws \Luracast\Restler\RestException
     */
    public function getById(int $id): ComparisonRepresentation
    {
        $this->checkAccess();
        return $this->container
            ->get(ComparisonController::class)
            ->getById($id);
    }

    /**
     * Delete a Comparison
     *
     * Delete a Comparison by id.
     *
     * @url    DELETE{id}
     * @status 200
     * @access protected
     *
     * @throws \Rest_Exception_InvalidTokenException
     * @throws NotFoundRestException 404
     * @throws ForbiddenRestException 403
     * @throws PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     * @throws \Luracast\Restler\RestException
     *
     */
    protected function delete(int $id)
    {
        $this->checkAccess();
        return $this->container
            ->get(ComparisonController::class)
            ->delete($id);
    }

    /**
     * @url OPTIONS
     */
    public function optionsArtifacts()
    {
        Header::allowOptionsGetPostDelete();
    }
}

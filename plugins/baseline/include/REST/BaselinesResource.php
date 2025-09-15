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
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

use DI\Container;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\User\Password\PasswordExpiredException;

class BaselinesResource extends AuthenticatedResource
{
    /** @var Container */
    private $container;

    public function __construct()
    {
        $this->container = ContainerBuilderFactory::create()->build();
    }

    /**
     * Create a new Baseline
     *
     * Create a new Baseline on current date time.
     * Example:
     * <pre>
     * {
     *   "name": "",
     *   "artifact_id": 14,
     *   "snapshot_date": "2019-03-21T14:47:03+01:00"
     * }
     * </pre>
     * snapshot_date format is ISO 8601.
     * Current date is used if no snapshot date provided.
     *
     * @url    POST
     * @status 201
     * @access protected
     *
     * @param string $name          Name of the baseline {@from body}
     * @param int    $artifact_id   Id of an artifact {@from body}
     * @param string $snapshot_date Snapshot date of the baseline {@from body}
     *
     * @return Tuleap\Baseline\REST\BaselineRepresentation
     * @throws \Rest_Exception_InvalidTokenException
     * @throws I18NRestException 401
     * @throws ForbiddenRestException 403
     * @throws NotFoundRestException 404
     * @throws PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     * @throws \Luracast\Restler\RestException
     */
    protected function post(string $name, int $artifact_id, ?string $snapshot_date = null): BaselineRepresentation
    {
        $this->checkAccess();
        return $this->container
            ->get(BaselineController::class)
            ->post($name, $artifact_id, $snapshot_date);
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptionsPost();
    }

    /**
     * Delete a Baseline
     *
     * Delete a Baseline by id.
     *
     * @url    DELETE{id}
     * @status 200
     * @access protected
     *
     * @throws \Rest_Exception_InvalidTokenException
     * @throws NotFoundRestException 404
     * @throws ForbiddenRestException 403
     * @throws I18NRestException 409
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
            ->get(BaselineController::class)
            ->delete($id);
    }

    /**
     * Get a Baseline
     *
     * Get a Baseline
     *
     * @url    GET {id}
     * @access hybrid
     *
     * @param int $id The baseline id
     *
     * @return Tuleap\Baseline\REST\BaselineRepresentation
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
    public function getById(int $id): BaselineRepresentation
    {
        $this->checkAccess();
        return $this->container
            ->get(BaselineController::class)
            ->getById($id);
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id)
    {
        Header::allowOptionsGet();
    }
}

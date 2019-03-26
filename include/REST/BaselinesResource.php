<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;

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
     *
     * @url    POST
     * @status 201
     * @access public
     *
     * @param string $name        Name of the baseline {@from body}
     * @param int    $artifact_id Id of an artifact {@from body}
     *
     * @return Tuleap\Baseline\REST\BaselineRepresentation
     * @throws \Rest_Exception_InvalidTokenException
     * @throws I18NRestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     * @throws \Luracast\Restler\RestException
     */
    public function post(string $name, int $artifact_id): BaselineRepresentation
    {
        $this->checkAccess();
        return $this->container
            ->get(BaselineController::class)
            ->post($name, $artifact_id);
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptionsPost();
    }

    /**
     * Get a Baseline
     *
     * Get a Baseline
     *
     * @url    GET {id}
     * @access public
     *
     * @param int $id The baseline id
     *
     * @return Tuleap\Baseline\REST\BaselineRepresentation
     * @throws \Rest_Exception_InvalidTokenException
     * @throws I18NRestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws \User_PasswordExpiredException
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

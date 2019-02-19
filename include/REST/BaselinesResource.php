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

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Support\DependenciesContext;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\I18NRestException;

class BaselinesResource extends AuthenticatedResource
{
    /** @var DependenciesContext */
    private $context;

    public function __construct()
    {
        $this->context = new DependenciesContext();
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
     * @param string $name         Name of the baseline {@from body}
     * @param int    $milestone_id Id of a milestone {@from body}
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
    public function post(string $name, int $milestone_id): BaselineRepresentation
    {
        $this->checkAccess();
        return $this->context
            ->getBaselineController()
            ->post($name, $milestone_id);
    }

    /**
     * Get a virtual simplified Baseline
     *
     * Get a virtual Baseline based on artifact id and date
     *
     * @url    GET
     * @access public
     *
     * @param int    $artifact_id The artifact id {@from query}
     * @param string $date        format: YYYY-MM-DD {@from query}
     *
     * @return Tuleap\Baseline\REST\SimplifiedBaselineRepresentation
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
    public function getByArtifactIdAndDate(int $artifact_id, string $date): SimplifiedBaselineRepresentation
    {
        $this->checkAccess();
        return $this->context
            ->getBaselineController()
            ->getByMilestoneIdAndDate($artifact_id, $date);
    }
}

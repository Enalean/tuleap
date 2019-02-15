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

use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;

class BaselinesResource extends AuthenticatedResource
{
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

        $controller = new BaselinesController(
            UserManager::build(),
            Tracker_Artifact_ChangesetFactoryBuilder::build(),
            Tracker_ArtifactFactory::instance(),
            new FieldRepository(),
            new ArtifactPermissionsChecker(UserManager::build(), ProjectStatusVerificator::build())
        );
        return $controller->getByArtifactIdAndDate($artifact_id, $date);
    }
}

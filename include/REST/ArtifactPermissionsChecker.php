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
 *
 */

namespace Tuleap\Baseline\REST;

use PFUser;
use Tracker_Artifact;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;

class ArtifactPermissionsChecker
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProjectStatusVerificator
     */
    private $project_status_verificator;

    public function __construct(UserManager $user_manager, ProjectStatusVerificator $project_status_verificator)
    {
        $this->user_manager               = $user_manager;
        $this->project_status_verificator = $project_status_verificator;
    }

    /**
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function getCurrentUser(): PFUser
    {
        return $this->user_manager->getCurrentUser();
    }

    /**
     * @throws I18NRestException 403
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function checkRead(Tracker_Artifact $artifact)
    {
        if (! $artifact->userCanView($this->getCurrentUser())) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-baseline', 'You cannot access to this artifact')
            );
        }

        $tracker = $artifact->getTracker();
        if (! $tracker->userCanView($this->getCurrentUser())) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-baseline', 'You cannot access to this artifact')
            );
        }

        $project = $tracker->getProject();
        $this->project_status_verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);
    }
}

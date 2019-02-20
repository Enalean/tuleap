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

namespace Tuleap\Baseline;

use Tracker_Artifact;

class ArtifactPermissions
{
    /** @var SecurityContext */
    private $security_context;

    /**
     * @var ProjectPermissions
     */
    private $project_permissions;

    public function __construct(SecurityContext $security_context, ProjectPermissions $project_permissions)
    {
        $this->security_context    = $security_context;
        $this->project_permissions = $project_permissions;
    }

    /**
     * @throws NotAuthorizedException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function checkRead(Tracker_Artifact $artifact)
    {
        if (! $artifact->userCanView($this->security_context->getCurrentUser())) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', 'You cannot read this artifact')
            );
        }

        $tracker = $artifact->getTracker();
        if (! $tracker->userCanView($this->security_context->getCurrentUser())) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', 'You cannot read this artifact because you cannot access to its tracker')
            );
        }

        $project = $tracker->getProject();
        try {
            $this->project_permissions->checkRead($project);
        } catch (NotAuthorizedException $e) {
            throw new NotAuthorizedException(
                dgettext(
                    'tuleap-baseline',
                    'You cannot read this artifact because you cannot access to its project'
                )
            );
        }
    }
}

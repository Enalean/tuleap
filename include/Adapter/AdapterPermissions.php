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

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Project;
use Project_AccessException;
use Tracker_Artifact;
use URLVerification;

class AdapterPermissions
{
    /** @var URLVerification */
    private $url_verification;

    public function __construct(URLVerification $url_verification)
    {
        $this->url_verification = $url_verification;
    }

    public function canUserReadArtifact(PFUser $user, Tracker_Artifact $artifact): bool
    {
        if (! $artifact->userCanView($user)) {
            return false;
        }

        $tracker = $artifact->getTracker();
        if (! $tracker->userCanView($user)) {
            return false;
        }

        $project = $tracker->getProject();
        return $this->canUserReadProject($user, $project);
    }

    public function canUserReadProject(PFUser $user, Project $project): bool
    {
        try {
            $this->url_verification->userCanAccessProject($user, $project);
            return true;
        } catch (Project_AccessException $e) {
            return false;
        }
    }
}

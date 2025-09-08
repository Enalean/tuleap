<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanSubmit;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;

final class UserCanSubmitInTrackerVerifier implements VerifyUserCanSubmit
{
    public function __construct(private \UserManager $user_manager, private \TrackerFactory $tracker_factory, private VerifySubmissionPermissions $verify_submission_permissions)
    {
    }

    #[\Override]
    public function canUserSubmitArtifact(UserIdentifier $user_identifier, TrackerReference $tracker): bool
    {
        $full_tracker = $this->tracker_factory->getTrackerById($tracker->getId());
        $full_user    = $this->user_manager->getUserById($user_identifier->getId());

        if (! $full_tracker || ! $full_user) {
            return false;
        }

        return $this->verify_submission_permissions->canUserSubmitArtifact(
            $full_user,
            $full_tracker
        );
    }
}

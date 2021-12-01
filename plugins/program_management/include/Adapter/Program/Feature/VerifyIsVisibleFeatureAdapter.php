<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisibleByProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class VerifyIsVisibleFeatureAdapter implements VerifyFeatureIsVisibleByProgram, VerifyFeatureIsVisible
{
    private \Tracker_ArtifactFactory $artifact_factory;
    private RetrieveUser $retrieve_user;

    public function __construct(\Tracker_ArtifactFactory $artifact_factory, RetrieveUser $retrieve_user)
    {
        $this->artifact_factory = $artifact_factory;
        $this->retrieve_user    = $retrieve_user;
    }

    public function isVisibleFeature(
        int $feature_id,
        UserIdentifier $user_identifier,
        ProgramIdentifier $program,
        ?PermissionBypass $bypass,
    ): bool {
        if ($bypass) {
            $artifact = $this->artifact_factory->getArtifactById($feature_id);
            return $artifact !== null && (int) $artifact->getTracker()->getGroupId() === $program->getId();
        }
        $user     = $this->retrieve_user->getUserWithId($user_identifier);
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        return $artifact !== null && (int) $artifact->getTracker()->getGroupId() === $program->getId();
    }

    public function isVisible(int $feature_id, UserIdentifier $user_identifier): bool
    {
        $user     = $this->retrieve_user->getUserWithId($user_identifier);
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        return $artifact !== null;
    }
}

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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class VerifyIsVisibleFeatureAdapter implements VerifyIsVisibleFeature
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(\Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    public function isVisibleFeature(int $feature_id, \PFUser $user, ProgramIdentifier $program): bool
    {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        return $artifact !== null && (int) $artifact->getTracker()->getGroupId() === $program->getId();
    }
}

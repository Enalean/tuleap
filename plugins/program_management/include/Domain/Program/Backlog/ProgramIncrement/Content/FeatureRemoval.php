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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;

/**
 * I am an order to un-plan the Feature from all Program Increments
 * @psalm-immutable
 */
final class FeatureRemoval
{
    public int $feature_id;
    public UserCanPrioritize $user;

    private function __construct(int $feature_id, UserCanPrioritize $user)
    {
        $this->feature_id = $feature_id;
        $this->user       = $user;
    }

    /**
     * @throws FeatureHasPlannedUserStoryException
     */
    public static function fromFeature(
        VerifyHasAtLeastOnePlannedUserStory $story_verifier,
        FeatureIdentifier $feature,
        UserCanPrioritize $user,
    ): self {
        if ($story_verifier->hasAtLeastOnePlannedUserStory($feature, $user)) {
            throw new FeatureHasPlannedUserStoryException($feature->id);
        }
        return new self($feature->id, $user);
    }
}

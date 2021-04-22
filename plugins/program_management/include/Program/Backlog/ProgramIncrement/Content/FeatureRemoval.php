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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Program\Program;

/**
 * I am an order to un-plan the Feature from all Program Increments
 * @psalm-immutable
 */
final class FeatureRemoval
{
    /**
     * @var int
     */
    public $feature_id;
    /**
     * @var \PFUser
     */
    public $user;

    private function __construct(int $feature_id, \PFUser $user)
    {
        $this->feature_id = $feature_id;
        $this->user       = $user;
    }

    /**
     * @throws FeatureHasPlannedUserStoryException
     */
    public static function fromRawData(
        FeatureIdentifier $feature,
        \PFUser $user,
        Program $program,
        VerifyIsVisibleFeature $visible_verifier,
        VerifyLinkedUserStoryIsNotPlanned $story_verifier
    ): ?self {
        if (! $visible_verifier->isVisibleFeature($feature, $user, $program)) {
            return null;
        }
        if ($story_verifier->isLinkedToAtLeastOnePlannedUserStory($user, $feature)) {
            throw new FeatureHasPlannedUserStoryException($feature->id);
        }
        return new self($feature->id, $user);
    }
}

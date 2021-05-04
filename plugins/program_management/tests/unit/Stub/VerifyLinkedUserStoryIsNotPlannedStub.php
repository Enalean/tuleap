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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;

final class VerifyLinkedUserStoryIsNotPlannedStub implements VerifyLinkedUserStoryIsNotPlanned
{
    /** @var bool */
    private $is_linked;

    private function __construct(bool $is_linked = false)
    {
        $this->is_linked = $is_linked;
    }

    public function isLinkedToAtLeastOnePlannedUserStory(\PFUser $user, FeatureIdentifier $feature): bool
    {
        return $this->is_linked;
    }

    public function hasStoryLinked(\PFUser $user, FeatureIdentifier $feature): bool
    {
        return false;
    }

    public static function buildLinkedStories(): self
    {
        return new self(true);
    }

    public static function buildNotLinkedStories(): self
    {
        return new self(false);
    }
}

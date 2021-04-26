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

use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;

/**
 * I am an order to plan a Feature in a Program Increment
 * @psalm-immutable
 */
final class FeatureAddition
{
    /**
     * @var FeatureIdentifier
     */
    public $feature;
    /**
     * @var \PFUser
     */
    public $user;
    /**
     * @var ProgramIncrementIdentifier
     */
    public $program_increment;

    private function __construct(
        FeatureIdentifier $feature,
        \PFUser $user,
        ProgramIncrementIdentifier $program_increment
    ) {
        $this->feature           = $feature;
        $this->user              = $user;
        $this->program_increment = $program_increment;
    }

    /**
     * @throws FeatureCannotBePlannedInProgramIncrementException
     */
    public static function fromFeature(
        VerifyCanBePlannedInProgramIncrement $can_be_planned_verifier,
        FeatureIdentifier $feature,
        ProgramIncrementIdentifier $program_increment,
        \PFUser $user
    ): self {
        $can_be_planned = $can_be_planned_verifier->canBePlannedInProgramIncrement(
            $feature->id,
            $program_increment->getId()
        );
        if (! $can_be_planned) {
            throw new FeatureCannotBePlannedInProgramIncrementException($feature->id, $program_increment->getId());
        }
        return new self($feature, $user, $program_increment);
    }
}

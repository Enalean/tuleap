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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class FeatureIdentifierCollection
{
    /**
     * @param FeatureIdentifier[] $features
     */
    private function __construct(private array $features)
    {
    }

    public static function fromProgramIncrement(
        SearchFeatures $feature_searcher,
        VerifyFeatureIsVisible $feature_verifier,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): self {
        return new self(
            FeatureIdentifier::buildCollectionFromProgramIncrement(
                $feature_searcher,
                $feature_verifier,
                $program_increment,
                $user
            )
        );
    }

    public function getFeatures(): array
    {
        return $this->features;
    }
}

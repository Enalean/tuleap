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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Feature\CheckIsValidFeature;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FeatureChecker implements CheckIsValidFeature
{
    public function __construct(
        private VerifyIsFeature $feature_verifier,
        private VerifyFeatureIsVisible $visibility_verifier,
    ) {
    }

    #[\Override]
    public function checkIsFeature(int $feature_id, UserIdentifier $user): void
    {
        if (! $this->feature_verifier->isFeature($feature_id)) {
            throw new FeatureIsNotPlannableException($feature_id);
        }
        if (! $this->visibility_verifier->isVisibleFeature($feature_id, $user)) {
            throw new FeatureNotFoundException($feature_id);
        }
    }
}

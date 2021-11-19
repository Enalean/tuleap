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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlannableFeatureIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;

final class PlannableFeatureBuilder
{
    public static function build(int $feature_id): PlannableFeatureIdentifier
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $feature         = FeatureIdentifier::fromId(
            VerifyFeatureIsVisibleStub::buildVisibleFeature(),
            $feature_id,
            $user_identifier
        );
        assert($feature instanceof FeatureIdentifier);

        return PlannableFeatureIdentifier::build(
            VerifyIsPlannableStub::buildPlannableElement(),
            RetrieveTrackerOfArtifactStub::withIds(1),
            $feature
        );
    }
}

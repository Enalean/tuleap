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
use Tuleap\ProgramManagement\Tests\Stub\CheckIsValidFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleByProgramStub;

final class FeatureIdentifierBuilder
{
    public static function build(int $feature_id, int $program_id): FeatureIdentifier
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $program         = ProgramIdentifierBuilder::buildWithId($program_id);
        $feature         = FeatureIdentifier::fromIdAndProgram(
            VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures(),
            $feature_id,
            $user_identifier,
            $program,
            null
        );
        assert($feature !== null);
        return $feature;
    }

    public static function withId(int $feature_id): FeatureIdentifier
    {
        return FeatureIdentifier::fromId(
            CheckIsValidFeatureStub::withAlwaysValidFeatures(),
            $feature_id,
            UserIdentifierStub::buildGenericUser()
        );
    }
}

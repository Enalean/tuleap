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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureCrossReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureURIStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;

final class FeatureBuilder
{
    public static function build(string $title): Feature
    {
        $feature_identifier = FeatureIdentifierBuilder::withId(1);

        return Feature::fromFeatureIdentifier(
            RetrieveFeatureTitleStub::withTitle($title),
            new RetrieveFeatureURIStub(),
            RetrieveFeatureCrossReferenceStub::withShortname("feature"),
            VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
            FeatureHasUserStoriesVerifierBuilder::buildWithUserStories(),
            RetrieveBackgroundColorStub::withColor('fiesta-red'),
            RetrieveTrackerOfFeatureStub::withId(1),
            $feature_identifier,
            UserIdentifierStub::buildGenericUser()
        );
    }
}

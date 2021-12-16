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

use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\FeatureOfUserStoryRetriever;
use Tuleap\ProgramManagement\Tests\Stub\CheckIsValidFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureCrossReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureURIStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchParentFeatureOfAUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;

final class FeatureOfUserStoryRetrieverBuilder
{
    public static function withSuccessiveFeatures(string $first_title, string $other_titles): FeatureOfUserStoryRetriever
    {
        $title_retriever           = RetrieveFeatureTitleStub::withSuccessiveTitles($first_title, $other_titles);
        $uri_retriever             = new RetrieveFeatureURIStub();
        $cross_reference_retriever = RetrieveFeatureCrossReferenceStub::withSuccessiveShortNames('feature', 'feature');
        $planned_verifier          = VerifyHasAtLeastOnePlannedUserStoryStub::withPlannedUserStory();
        $check_is_valid_feature    = CheckIsValidFeatureStub::withAlwaysValidFeatures();
        $background_retriever      = RetrieveBackgroundColorStub::withSuccessiveColors('fiesta-red', 'fiesta-red');
        $tracker_retriever         = RetrieveTrackerOfFeatureStub::withSuccessiveIds(10, 10);

        return new FeatureOfUserStoryRetriever(
            $title_retriever,
            $uri_retriever,
            $cross_reference_retriever,
            $planned_verifier,
            $check_is_valid_feature,
            $background_retriever,
            $tracker_retriever,
            SearchParentFeatureOfAUserStoryStub::withParentFeatureId(1),
            FeatureHasUserStoriesVerifierBuilder::buildWithUserStories()
        );
    }
}

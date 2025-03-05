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

use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureIdentifierCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_FEATURE_ID  = 424;
    private const SECOND_FEATURE_ID = 399;
    private SearchFeaturesStub $feature_searcher;

    protected function setUp(): void
    {
        $this->feature_searcher = SearchFeaturesStub::withFeatureIds(self::FIRST_FEATURE_ID, self::SECOND_FEATURE_ID);
    }

    private function getCollection(): FeatureIdentifierCollection
    {
        return FeatureIdentifierCollection::fromProgramIncrement(
            $this->feature_searcher,
            VerifyFeatureIsVisibleStub::withAlwaysVisibleFeatures(),
            ProgramIncrementIdentifierBuilder::buildWithId(81),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsCollectionFromProgramIncrement(): void
    {
        $features    = $this->getCollection()->getFeatures();
        $feature_ids = array_map(static fn(FeatureIdentifier $feature) => $feature->getId(), $features);

        self::assertCount(2, $feature_ids);
        self::assertContains(self::FIRST_FEATURE_ID, $feature_ids);
        self::assertContains(self::SECOND_FEATURE_ID, $feature_ids);
    }

    public function testItBuildsEmptyCollectionWhenNoFeaturesInProgramIncrement(): void
    {
        $this->feature_searcher = SearchFeaturesStub::withoutFeatures();
        self::assertCount(0, $this->getCollection()->getFeatures());
    }
}

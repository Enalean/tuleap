<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;

final class ArtifactIdsValuesCollectionTest extends TestCase
{
    public function testItReturnsOkForSimpleValue(): void
    {
        $result       = ArtifactIdsValuesCollection::fromValueWrapper(new SimpleValueWrapper(1105));
        $artifact_ids = $result->unwrapOr(null)->artifact_ids ?? [];
        self::assertCount(1, $artifact_ids);
        self::assertSame(1105, $artifact_ids[0]);
    }

    public function testItReturnsErrForCurrentUser(): void
    {
        $user_value = new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser(
            UserTestBuilder::anActiveUser()->build()
        ));

        $result = ArtifactIdsValuesCollection::fromValueWrapper($user_value);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToCurrentUserFault::class, $result->error);
    }

    public function testItReturnsErrForCurrentDateTime(): void
    {
        $result = ArtifactIdsValuesCollection::fromValueWrapper(new CurrentDateTimeValueWrapper(null, null));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToCurrentDateTimeFault::class, $result->error);
    }

    public function testItReturnsErrForStatusOpen(): void
    {
        $result = ArtifactIdsValuesCollection::fromValueWrapper(new StatusOpenValueWrapper());
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToStatusOpenFault::class, $result->error);
    }

    public function testItReturnsOkForBetweenValue(): void
    {
        $min_id = 5;
        $max_id = 8;
        $result = ArtifactIdsValuesCollection::fromValueWrapper(
            new BetweenValueWrapper(
                new SimpleValueWrapper($min_id),
                new SimpleValueWrapper($max_id)
            )
        );

        $artifact_ids = $result->unwrapOr(null)->artifact_ids ?? [];
        self::assertEquals([$min_id, $max_id], $artifact_ids);
    }

    public function testItReturnsErrForBetweenValueWhenMinIsGreaterThanMax(): void
    {
        $min_id = 8;
        $max_id = 5;
        $result = ArtifactIdsValuesCollection::fromValueWrapper(
            new BetweenValueWrapper(
                new SimpleValueWrapper($min_id),
                new SimpleValueWrapper($max_id)
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonWithBetweenValuesMinGreaterThanMaxFault::class, $result->error);
    }

    public function testItThrowsForInValueAsArtifactIdValuesListsNeverSupportIn(): void
    {
        $this->expectException(\LogicException::class);

        ArtifactIdsValuesCollection::fromValueWrapper(
            new InValueWrapper(
                [
                    new SimpleValueWrapper(5),
                    new SimpleValueWrapper(8),
                ]
            )
        );
    }
}

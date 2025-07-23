<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\EmptyStringComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ListToMyselfForAnonymousComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToNowComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStringComparisonException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactSubmitterCheckerTest extends TestCase
{
    private const CURRENT_USER_NAME = 'mmishra';
    private const VALID_USER_NAME   = 'rnawaz';
    private Metadata $metadata;
    private ProvideAndRetrieveUserStub $user_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata       = new Metadata('submitted_by');
        $user                 = UserTestBuilder::aUser()->withUserName(self::CURRENT_USER_NAME)->build();
        $this->user_retriever = ProvideAndRetrieveUserStub::build($user)
            ->withUsers([$user, UserTestBuilder::aUser()->withUserName(self::VALID_USER_NAME)->build()]);
    }

    /**
     * @throws EmptyStringComparisonException
     * @throws ListToMyselfForAnonymousComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws ToNowComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToStringComparisonException
     */
    private function check(Comparison $comparison): void
    {
        $checker = new ArtifactSubmitterChecker($this->user_retriever);
        $checker->checkAlwaysThereFieldIsValidForComparison($comparison, $this->metadata);
    }

    public function testItAllowsValidUsername(): void
    {
        $this->expectNotToPerformAssertions();
        $this->check(new EqualComparison($this->metadata, new SimpleValueWrapper(self::VALID_USER_NAME)));
    }

    public function testItAllowsInValues(): void
    {
        $this->expectNotToPerformAssertions();
        $this->check(new InComparison(
            $this->metadata,
            new InValueWrapper([
                new SimpleValueWrapper(self::CURRENT_USER_NAME),
                new SimpleValueWrapper(self::VALID_USER_NAME),
            ])
        ));
    }

    public static function generateInvalidComparisonsToEmptyString(): iterable
    {
        $metadata    = new Metadata('submitted_by');
        $empty_value = new SimpleValueWrapper('');
        yield 'in()' => [new InComparison($metadata, new InValueWrapper([$empty_value]))];
        yield 'not in()' => [new NotInComparison($metadata, new InValueWrapper([$empty_value]))];
        yield '=' => [new EqualComparison($metadata, new InValueWrapper([$empty_value]))];
        yield '!=' => [new NotEqualComparison($metadata, new InValueWrapper([$empty_value]))];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidComparisonsToEmptyString')]
    public function testItForbidsEmptyValueForComparisons(Comparison $comparison): void
    {
        $this->expectException(EmptyStringComparisonException::class);
        $this->check($comparison);
    }

    public function testItAllowsMyselfValueWhenCurrentUserIsLoggedIn(): void
    {
        $this->expectNotToPerformAssertions();
        $this->check(new EqualComparison($this->metadata, new CurrentUserValueWrapper($this->user_retriever)));
    }

    public function testItForbidsMyselfValueWhenCurrentUserIsAnonymous(): void
    {
        $this->expectException(ListToMyselfForAnonymousComparisonException::class);
        $this->check(
            new EqualComparison(
                $this->metadata,
                new CurrentUserValueWrapper(
                    ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build())
                )
            )
        );
    }

    public function testItForbidsUsernameThatDoesNotMatchUser(): void
    {
        $this->expectException(ToStringComparisonException::class);
        $this->check(new EqualComparison($this->metadata, new SimpleValueWrapper('unknown_user')));
    }

    public function testItForbidsStatusOpen(): void
    {
        $this->expectException(ToStatusOpenComparisonException::class);
        $this->check(new EqualComparison($this->metadata, new StatusOpenValueWrapper()));
    }

    public function testItForbidsNowValue(): void
    {
        $this->expectException(ToNowComparisonException::class);
        $this->check(new EqualComparison($this->metadata, new CurrentDateTimeValueWrapper(null, null)));
    }
}

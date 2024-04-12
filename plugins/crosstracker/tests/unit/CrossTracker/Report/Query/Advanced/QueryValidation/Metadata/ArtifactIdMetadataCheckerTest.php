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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use Throwable;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\EmptyStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListToMyselfForAnonymousComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToAnyStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToEmptyStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToIntegerLesserThanOneException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToMyselfComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToNowComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToStringComparisonException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;

final class ArtifactIdMetadataCheckerTest extends TestCase
{
    private Metadata $metadata;

    protected function setUp(): void
    {
        $this->metadata = new Metadata('id');
    }

    /**
     * @throws ListToMyselfForAnonymousComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToNowComparisonException
     * @throws ToStringComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws EmptyStringComparisonException
     */
    private function check(Comparison $comparison): void
    {
        $checker = new ArtifactIdMetadataChecker();
        $checker->checkAlwaysThereFieldIsValidForComparison($comparison, $this->metadata);
    }

    public function testItAllowsTheEqualOperator(): void
    {
        $comparison = new EqualComparison($this->metadata, new SimpleValueWrapper(105));

        $this->expectNotToPerformAssertions();
        $this->check($comparison);
    }

    public function testItAllowsTheNotEqualOperator(): void
    {
        $comparison = new NotEqualComparison($this->metadata, new SimpleValueWrapper(105));

        $this->expectNotToPerformAssertions();
        $this->check($comparison);
    }

    public function testItAllowsTheLesserThanOperator(): void
    {
        $comparison = new LesserThanComparison($this->metadata, new SimpleValueWrapper(105));

        $this->expectNotToPerformAssertions();
        $this->check($comparison);
    }

    public function testItAllowsTheLesserThanOrEqualOperator(): void
    {
        $comparison = new LesserThanOrEqualComparison($this->metadata, new SimpleValueWrapper(105));

        $this->expectNotToPerformAssertions();
        $this->check($comparison);
    }

    public function testItAllowsTheGreaterThanOperator(): void
    {
        $comparison = new GreaterThanComparison($this->metadata, new SimpleValueWrapper(105));

        $this->expectNotToPerformAssertions();
        $this->check($comparison);
    }

    public function testItAllowsTheGreaterThanOrEqualOperator(): void
    {
        $comparison = new GreaterThanOrEqualComparison($this->metadata, new SimpleValueWrapper(105));

        $this->expectNotToPerformAssertions();
        $this->check($comparison);
    }

    public static function generateComparisonsWithInvalidValues(): iterable
    {
        $user           = UserTestBuilder::aUser()->withUserName('jdoe')->build();
        $user_retriever = ProvideAndRetrieveUserStub::build($user);
        $metadata       = new Metadata('id');

        // Equal operator
        yield '@id = empty string' => [new EqualComparison($metadata, new SimpleValueWrapper('')), ToEmptyStringComparisonException::class];
        yield '@id = a string' => [new EqualComparison($metadata, new SimpleValueWrapper('1090')), ToAnyStringComparisonException::class];
        yield '@id = an integer lesser than 1' => [new EqualComparison($metadata, new SimpleValueWrapper(0)), ToIntegerLesserThanOneException::class];
        yield '@id = OPEN()' => [new EqualComparison($metadata, new StatusOpenValueWrapper()), ToStatusOpenComparisonException::class];
        yield '@id = NOW()' => [new EqualComparison($metadata, new CurrentDateTimeValueWrapper(null, null)), ToNowComparisonException::class];
        yield '@id = MYSELF()' => [new EqualComparison($metadata, new CurrentUserValueWrapper($user_retriever)), ToMyselfComparisonException::class];

        // Not Equal operator
        yield '@id != empty string' => [new NotEqualComparison($metadata, new SimpleValueWrapper('')), ToEmptyStringComparisonException::class];
        yield '@id != a string' => [new NotEqualComparison($metadata, new SimpleValueWrapper('1090')), ToAnyStringComparisonException::class];
        yield '@id != an integer lesser than 1' => [new NotEqualComparison($metadata, new SimpleValueWrapper(0)), ToIntegerLesserThanOneException::class];
        yield '@id != OPEN()' => [new NotEqualComparison($metadata, new StatusOpenValueWrapper()), ToStatusOpenComparisonException::class];
        yield '@id != NOW()' => [new NotEqualComparison($metadata, new CurrentDateTimeValueWrapper(null, null)), ToNowComparisonException::class];
        yield '@id != MYSELF()' => [new NotEqualComparison($metadata, new CurrentUserValueWrapper($user_retriever)), ToMyselfComparisonException::class];

        // Lesser Than operator
        yield '@id < empty string' => [new LesserThanComparison($metadata, new SimpleValueWrapper('')), ToEmptyStringComparisonException::class];
        yield '@id < a string' => [new LesserThanComparison($metadata, new SimpleValueWrapper('1090')), ToAnyStringComparisonException::class];
        yield '@id < an integer lesser than 1' => [new LesserThanComparison($metadata, new SimpleValueWrapper(0)), ToIntegerLesserThanOneException::class];
        yield '@id < OPEN()' => [new LesserThanComparison($metadata, new StatusOpenValueWrapper()), ToStatusOpenComparisonException::class];
        yield '@id < NOW()' => [new LesserThanComparison($metadata, new CurrentDateTimeValueWrapper(null, null)), ToNowComparisonException::class];
        yield '@id < MYSELF()' => [new LesserThanComparison($metadata, new CurrentUserValueWrapper($user_retriever)), ToMyselfComparisonException::class];

        // Lesser Than or Equal operator
        yield '@id <= empty string' => [new LesserThanOrEqualComparison($metadata, new SimpleValueWrapper('')), ToEmptyStringComparisonException::class];
        yield '@id <= a string' => [new LesserThanOrEqualComparison($metadata, new SimpleValueWrapper('1090')), ToAnyStringComparisonException::class];
        yield '@id <= an integer lesser than 1' => [new LesserThanOrEqualComparison($metadata, new SimpleValueWrapper(0)), ToIntegerLesserThanOneException::class];
        yield '@id <= OPEN()' => [new LesserThanOrEqualComparison($metadata, new StatusOpenValueWrapper()), ToStatusOpenComparisonException::class];
        yield '@id <= NOW()' => [new LesserThanOrEqualComparison($metadata, new CurrentDateTimeValueWrapper(null, null)), ToNowComparisonException::class];
        yield '@id <= MYSELF()' => [new LesserThanOrEqualComparison($metadata, new CurrentUserValueWrapper($user_retriever)), ToMyselfComparisonException::class];

        // Greater Than operator
        yield '@id > empty string' => [new GreaterThanComparison($metadata, new SimpleValueWrapper('')), ToEmptyStringComparisonException::class];
        yield '@id > a string' => [new GreaterThanComparison($metadata, new SimpleValueWrapper('1090')), ToAnyStringComparisonException::class];
        yield '@id > an integer lesser than 1' => [new GreaterThanComparison($metadata, new SimpleValueWrapper(0)), ToIntegerLesserThanOneException::class];
        yield '@id > OPEN()' => [new GreaterThanComparison($metadata, new StatusOpenValueWrapper()), ToStatusOpenComparisonException::class];
        yield '@id > NOW()' => [new GreaterThanComparison($metadata, new CurrentDateTimeValueWrapper(null, null)), ToNowComparisonException::class];
        yield '@id > MYSELF()' => [new GreaterThanComparison($metadata, new CurrentUserValueWrapper($user_retriever)), ToMyselfComparisonException::class];

        // Greater Than or Equal operator
        yield '@id >= empty string' => [new GreaterThanOrEqualComparison($metadata, new SimpleValueWrapper('')), ToEmptyStringComparisonException::class];
        yield '@id >= a string' => [new GreaterThanOrEqualComparison($metadata, new SimpleValueWrapper('1090')), ToAnyStringComparisonException::class];
        yield '@id >= an integer lesser than 1' => [new GreaterThanOrEqualComparison($metadata, new SimpleValueWrapper(0)), ToIntegerLesserThanOneException::class];
        yield '@id >= OPEN()' => [new GreaterThanOrEqualComparison($metadata, new StatusOpenValueWrapper()), ToStatusOpenComparisonException::class];
        yield '@id >= NOW()' => [new GreaterThanOrEqualComparison($metadata, new CurrentDateTimeValueWrapper(null, null)), ToNowComparisonException::class];
        yield '@id >= MYSELF()' => [new GreaterThanOrEqualComparison($metadata, new CurrentUserValueWrapper($user_retriever)), ToMyselfComparisonException::class];
    }

    /**
     * @psalm-param class-string<Throwable> $expected_exception
     * @dataProvider generateComparisonsWithInvalidValues
     */
    public function testItThrowsExceptionWhenProvidedValueIsInvalid(Comparison $comparison, string $expected_exception): void
    {
        $this->expectException($expected_exception);
        $this->check($comparison);
    }

    public static function generateInvalidComparisons(): iterable
    {
        $value    = new SimpleValueWrapper(105);
        $metadata = new Metadata('id');

        yield 'in()' => [new InComparison($metadata, new InValueWrapper([$value]))];
        yield 'not in()' => [new NotInComparison($metadata, new InValueWrapper([$value]))];
        yield 'between()' => [new BetweenComparison($metadata, new BetweenValueWrapper($value, $value))];
    }

    /**
     * @dataProvider generateInvalidComparisons
     */
    public function testItThrowsWhenOperatorIsForbidden(Comparison $comparison): void
    {
        $this->expectException(OperatorNotAllowedForMetadataException::class);
        $this->check($comparison);
    }
}

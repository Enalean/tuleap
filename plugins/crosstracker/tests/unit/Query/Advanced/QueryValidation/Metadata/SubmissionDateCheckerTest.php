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
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorToNowComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToMyselfComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStringComparisonException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmissionDateCheckerTest extends TestCase
{
    private Metadata $metadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata = new Metadata('submitted_on');
    }

    /**
     * @throws EmptyStringComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws OperatorToNowComparisonException
     * @throws ToMyselfComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToStringComparisonException
     */
    private function check(Comparison $comparison): void
    {
        $checker = new SubmissionDateChecker();
        $checker->checkAlwaysThereFieldIsValidForComparison($comparison, $this->metadata);
    }

    public function testItForbidsInvalidDateString(): void
    {
        $this->expectException(ToStringComparisonException::class);
        $this->check(
            new EqualComparison($this->metadata, new SimpleValueWrapper('invalid'))
        );
    }

    public function testItAllowsValidDate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->check(
            new EqualComparison($this->metadata, new SimpleValueWrapper('2027-03-23'))
        );
    }

    public function testItAllowsValidDateTime(): void
    {
        $this->expectNotToPerformAssertions();
        $this->check(
            new EqualComparison($this->metadata, new SimpleValueWrapper('2017-12-08 05:39'))
        );
    }

    public function testItAllowsBetween(): void
    {
        $this->expectNotToPerformAssertions();
        $this->check(
            new BetweenComparison(
                $this->metadata,
                new BetweenValueWrapper(
                    new SimpleValueWrapper('2006-06-02 02:41'),
                    new CurrentDateTimeValueWrapper(null, null),
                )
            )
        );
    }

    public static function generateInvalidComparisonsToEmptyString(): iterable
    {
        $metadata    = new Metadata('submitted_on');
        $empty_value = new SimpleValueWrapper('');
        yield "= ''" => [new EqualComparison($metadata, $empty_value)];
        yield "!= ''" => [new NotEqualComparison($metadata, $empty_value)];
        yield "> ''" => [new GreaterThanComparison($metadata, $empty_value)];
        yield ">= ''" => [new GreaterThanOrEqualComparison($metadata, $empty_value)];
        yield "< ''" => [new LesserThanComparison($metadata, $empty_value)];
        yield "<= ''" => [new LesserThanOrEqualComparison($metadata, $empty_value)];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidComparisonsToEmptyString')]
    public function testItForbidsEmptyValueForComparisons(Comparison $comparison): void
    {
        $this->expectException(EmptyStringComparisonException::class);
        $this->check($comparison);
    }

    public function testItForbidsNowValueForEquals(): void
    {
        $this->expectException(OperatorToNowComparisonException::class);
        $this->check(new EqualComparison($this->metadata, new CurrentDateTimeValueWrapper(null, null)));
    }

    public function testItForbidsNowValueForNotEquals(): void
    {
        $this->expectException(OperatorToNowComparisonException::class);
        $this->check(new NotEqualComparison($this->metadata, new CurrentDateTimeValueWrapper(null, null)));
    }

    public function testItForbidsMyselfValue(): void
    {
        $this->expectException(ToMyselfComparisonException::class);
        $this->check(
            new EqualComparison(
                $this->metadata,
                new CurrentUserValueWrapper(
                    ProvideCurrentUserStub::buildWithUser(
                        UserTestBuilder::buildWithDefaults()
                    )
                )
            )
        );
    }

    public function testItForbidsStatusOpen(): void
    {
        $this->expectException(ToStatusOpenComparisonException::class);
        $this->check(new EqualComparison($this->metadata, new StatusOpenValueWrapper()));
    }
}

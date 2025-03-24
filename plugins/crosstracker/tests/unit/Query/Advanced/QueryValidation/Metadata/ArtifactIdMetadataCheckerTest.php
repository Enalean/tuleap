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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToAnyStringComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToEmptyStringComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToIntegerLesserThanOneException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToMyselfComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToNowComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\WithBetweenValuesMinGreaterThanMaxException;
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactIdMetadataCheckerTest extends TestCase
{
    /**
     * @throws ToNowComparisonException
     * @throws ToAnyStringComparisonException
     * @throws WithBetweenValuesMinGreaterThanMaxException
     * @throws ToIntegerLesserThanOneException
     * @throws ToStatusOpenComparisonException
     * @throws ToEmptyStringComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws ToMyselfComparisonException
     */
    private function check(Comparison $comparison): void
    {
        $checker = new ArtifactIdMetadataChecker();
        $checker->checkAlwaysThereFieldIsValidForComparison($comparison, new Metadata('id'));
    }

    public static function generateAllowedComparisonsWithValidValues(): iterable
    {
        $metadata = new Metadata('id');

        yield '@id = int, when int > 0' => [new EqualComparison($metadata, new SimpleValueWrapper(105))];
        yield '@id != int, when int > 0' => [new NotEqualComparison($metadata, new SimpleValueWrapper(105))];
        yield '@id < int, when int > 0' => [new LesserThanComparison($metadata, new SimpleValueWrapper(105))];
        yield '@id <= int, when int > 0' => [new LesserThanOrEqualComparison($metadata, new SimpleValueWrapper(105))];
        yield '@id > int, when int > 0' => [new GreaterThanComparison($metadata, new SimpleValueWrapper(105))];
        yield '@id >= int, when int > 0' => [new GreaterThanOrEqualComparison($metadata, new SimpleValueWrapper(105))];
        yield '@id BETWEEN(min, max), min === max AND are > 0' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new SimpleValueWrapper(1)))];
        yield '@id BETWEEN(min, max), min < max AND are > 0' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new SimpleValueWrapper(10)))];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateAllowedComparisonsWithValidValues')]
    public function testItDoesNothingWhenTheComparisonAndTheValuesAreValid(Comparison $comparison): void
    {
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

        // Between operator
        yield '@id BETWEEN(empty string, int > 0)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(''), new SimpleValueWrapper(1))), ToEmptyStringComparisonException::class];
        yield '@id BETWEEN(int > 0, empty string)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new SimpleValueWrapper(''))), ToEmptyStringComparisonException::class];
        yield '@id BETWEEN(string, int > 0)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper('1'), new SimpleValueWrapper(1))), ToAnyStringComparisonException::class];
        yield '@id BETWEEN(int > 0, string)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new SimpleValueWrapper('1'))), ToAnyStringComparisonException::class];
        yield '@id BETWEEN(int < 1, int > 0)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(0), new SimpleValueWrapper(1))), ToIntegerLesserThanOneException::class];
        yield '@id BETWEEN(int > 0, int < 1)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new SimpleValueWrapper(0))), ToIntegerLesserThanOneException::class];
        yield '@id BETWEEN(OPEN(), int > 0)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new StatusOpenValueWrapper(), new SimpleValueWrapper(1))), ToStatusOpenComparisonException::class];
        yield '@id BETWEEN(int > 0, OPEN())' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new StatusOpenValueWrapper())), ToStatusOpenComparisonException::class];
        yield '@id BETWEEN(NOW(), int > 0)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new CurrentDateTimeValueWrapper(null, null), new SimpleValueWrapper(1))), ToNowComparisonException::class];
        yield '@id BETWEEN(int > 0, NOW())' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new CurrentDateTimeValueWrapper(null, null))), ToNowComparisonException::class];
        yield '@id BETWEEN(MYSELF(), int > 0)' => [new BetweenComparison($metadata, new BetweenValueWrapper(new CurrentUserValueWrapper($user_retriever), new SimpleValueWrapper(1))), ToMyselfComparisonException::class];
        yield '@id BETWEEN(int > 0, MYSELF())' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(1), new CurrentUserValueWrapper($user_retriever))), ToMyselfComparisonException::class];
        yield '@id BETWEEN(int > 0, int > 0), when min > max' => [new BetweenComparison($metadata, new BetweenValueWrapper(new SimpleValueWrapper(2), new SimpleValueWrapper(1))), WithBetweenValuesMinGreaterThanMaxException::class];
    }

    /**
     * @psalm-param class-string<Throwable> $expected_exception
     */
    #[DataProvider('generateComparisonsWithInvalidValues')]
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
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidComparisons')]
    public function testItThrowsWhenOperatorIsForbidden(Comparison $comparison): void
    {
        $this->expectException(OperatorNotAllowedForMetadataException::class);
        $this->check($comparison);
    }
}

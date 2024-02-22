<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Tests\Builders;

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListValueValidator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\BetweenComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotInComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class InvalidSearchableCollectorParametersBuilder
{
    private Comparison $comparison;
    private \PFUser $user;
    /** @var list<\Tracker> */
    private array $trackers;

    private function __construct()
    {
        $this->trackers = [
            TrackerTestBuilder::aTracker()->withId(73)->build(),
            TrackerTestBuilder::aTracker()->withId(36)->build(),
        ];
        $this->user     = UserTestBuilder::buildWithId(161);

        $this->comparison = new EqualComparison(
            new Field('romeo'),
            new SimpleValueWrapper(12)
        );
    }

    public static function aParameter(): self
    {
        return new self();
    }

    public function withUser(\PFUser $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @no-named-arguments
     */
    public function onTrackers(\Tracker $tracker, \Tracker ...$other_trackers): self
    {
        $this->trackers = [$tracker, ...$other_trackers];
        return $this;
    }

    public function build(): InvalidSearchableCollectorParameters
    {
        $comparison_parameters = new InvalidComparisonCollectorParameters(
            new InvalidSearchablesCollection(),
            $this->trackers,
            $this->user
        );
        $comparison_checker    = new EqualComparisonChecker(
            new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME),
            new ListValueValidator(
                new EmptyStringAllowed(),
                ProvideAndRetrieveUserStub::build($this->user)
            )
        );
        return new InvalidSearchableCollectorParameters(
            $comparison_parameters,
            $comparison_checker,
            $this->comparison,
            new FlatInvalidFieldChecker(
                $this->comparison,
                new FloatFieldChecker(),
                new IntegerFieldChecker(),
                new TextFieldChecker(),
                new DateFieldChecker(),
                new EqualComparisonVisitor(),
                new NotEqualComparisonVisitor(),
                new LesserThanComparisonVisitor(),
                new LesserThanOrEqualComparisonVisitor(),
                new GreaterThanComparisonVisitor(),
                new GreaterThanOrEqualComparisonVisitor(),
                new BetweenComparisonVisitor(),
                new InComparisonVisitor(),
                new NotInComparisonVisitor()
            )
        );
    }
}

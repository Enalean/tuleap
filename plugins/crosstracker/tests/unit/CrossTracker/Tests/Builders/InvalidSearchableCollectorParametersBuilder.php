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
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListValueValidator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class InvalidSearchableCollectorParametersBuilder
{
    private InvalidComparisonCollectorParameters $comparison_parameters;
    private ComparisonChecker $comparison_checker;
    private Comparison $comparison;

    private function __construct()
    {
        $trackers = [
            TrackerTestBuilder::aTracker()->withId(73)->build(),
            TrackerTestBuilder::aTracker()->withId(36)->build(),
        ];
        $user     = UserTestBuilder::buildWithId(161);

        $this->comparison_parameters = new InvalidComparisonCollectorParameters(
            new InvalidSearchablesCollection(),
            $trackers,
            $user
        );
        $this->comparison_checker    = new EqualComparisonChecker(
            new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME),
            new ListValueValidator(
                new EmptyStringAllowed(),
                ProvideAndRetrieveUserStub::build($user)
            )
        );
        $this->comparison            = new EqualComparison(
            new Field('romeo'),
            new SimpleValueWrapper(12)
        );
    }

    public static function aParameter(): self
    {
        return new self();
    }

    public function build(): InvalidSearchableCollectorParameters
    {
        return new InvalidSearchableCollectorParameters(
            $this->comparison_parameters,
            $this->comparison_checker,
            $this->comparison
        );
    }
}

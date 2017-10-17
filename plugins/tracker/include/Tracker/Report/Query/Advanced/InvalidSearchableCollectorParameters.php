<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\IProvideTheInvalidFieldCheckerForAComparison;

class InvalidSearchableCollectorParameters implements VisitorParameters
{
    /**
     * @var IProvideTheInvalidFieldCheckerForAComparison
     */
    private $checker_provider;
    /**
     * @var Comparison
     */
    private $comparison;
    /**
     * @var InvalidFieldsCollectorParameters
     */
    private $invalid_fields_collector_parameters;

    public function __construct(
        InvalidFieldsCollectorParameters $invalid_fields_collector_parameters,
        IProvideTheInvalidFieldCheckerForAComparison $checker_provider,
        Comparison $comparison
    ) {
        $this->checker_provider                    = $checker_provider;
        $this->comparison                          = $comparison;
        $this->invalid_fields_collector_parameters = $invalid_fields_collector_parameters;
    }

    /**
     * @return IProvideTheInvalidFieldCheckerForAComparison
     */
    public function getCheckerProvider()
    {
        return $this->checker_provider;
    }

    /**
     * @return Comparison
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * @return InvalidFieldsCollectorParameters
     */
    public function getInvalidFieldsCollectorParameters()
    {
        return $this->invalid_fields_collector_parameters;
    }
}

<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

final class InvalidSearchableCollectorParameters implements VisitorParameters
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
     * @var InvalidComparisonCollectorParameters
     */
    private $invalid_searchables_collector_parameters;
    /**
     * @var InvalidMetadata\ICheckMetadataForAComparison
     */
    private $metadata_checker;

    public function __construct(
        InvalidComparisonCollectorParameters $invalid_searchables_collector_parameters,
        IProvideTheInvalidFieldCheckerForAComparison $checker_provider,
        InvalidMetadata\ICheckMetadataForAComparison $metadata_checker,
        Comparison $comparison,
    ) {
        $this->checker_provider                         = $checker_provider;
        $this->metadata_checker                         = $metadata_checker;
        $this->comparison                               = $comparison;
        $this->invalid_searchables_collector_parameters = $invalid_searchables_collector_parameters;
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
     * @return InvalidComparisonCollectorParameters
     */
    public function getInvalidSearchablesCollectorParameters()
    {
        return $this->invalid_searchables_collector_parameters;
    }

    /**
     * @return InvalidMetadata\ICheckMetadataForAComparison
     */
    public function getMetadataChecker()
    {
        return $this->metadata_checker;
    }
}

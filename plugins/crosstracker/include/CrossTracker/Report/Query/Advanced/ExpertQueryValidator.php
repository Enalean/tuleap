<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;

class ExpertQueryValidator
{
    /** @var Parser */
    private $parser;
    /** @var SizeValidatorVisitor */
    private $size_validator;
    /** @var InvalidComparisonCollectorVisitor */
    private $invalid_comparison_collector;

    public function __construct(
        Parser $parser,
        SizeValidatorVisitor $size_validator,
        InvalidComparisonCollectorVisitor $invalid_comparison_collector
    ) {
        $this->parser                       = $parser;
        $this->size_validator               = $size_validator;
        $this->invalid_comparison_collector = $invalid_comparison_collector;
    }

    /**
     * @param CrossTrackerReport $report
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws LimitSizeIsExceededException
     * @throws SyntaxError
     */
    public function validateExpertQuery(CrossTrackerReport $report)
    {
        $parsed_expert_query = $this->parser->parse($report->getExpertQuery());
        $this->size_validator->checkSizeOfTree($parsed_expert_query);

        $invalid_searchables_collection = new InvalidSearchablesCollection();
        $this->invalid_comparison_collector->collectErrors(
            $parsed_expert_query,
            $invalid_searchables_collection
        );
        $nonexistent_searchables    = $invalid_searchables_collection->getNonexistentSearchables();
        $nb_nonexistent_searchables = count($nonexistent_searchables);
        if ($nb_nonexistent_searchables > 0) {
            $message = sprintf(
                dngettext(
                    'tuleap-crosstracker',
                    "We cannot search on '%s', we don't know what it refers to. Please refer to the documentation for the allowed comparisons.",
                    "We cannot search on '%s', we don't know what they refer to. Please refer to the documentation for the allowed comparisons.",
                    $nb_nonexistent_searchables
                ),
                implode("', '", $nonexistent_searchables)
            );
            throw new SearchablesDoNotExistException($message);
        }

        $invalid_searchable_errors = $invalid_searchables_collection->getInvalidSearchableErrors();
        if ($invalid_searchable_errors) {
            throw new SearchablesAreInvalidException($invalid_searchable_errors);
        }
    }
}

<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced;

class ExpertQueryValidator
{
    public function __construct(
        private readonly ParserCacheProxy $parser,
        private readonly SizeValidatorVisitor $size_validator,
    ) {
    }

    /**
     * @param string $expert_query
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     */
    public function validateExpertQuery(
        $expert_query,
        IBuildInvalidSearchablesCollection $invalid_searchables_collection_builder,
    ) {
        $parsed_expert_query = $this->parser->parse($expert_query);
        $this->size_validator->checkSizeOfTree($parsed_expert_query);

        $invalid_searchables_collection = $invalid_searchables_collection_builder->buildCollectionOfInvalidSearchables($parsed_expert_query);

        $nonexistent_searchables    = $invalid_searchables_collection->getNonexistentSearchables();
        $nb_nonexistent_searchables = count($nonexistent_searchables);
        if ($nb_nonexistent_searchables > 0) {
            $message = sprintf(
                dngettext(
                    'tuleap-tracker',
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

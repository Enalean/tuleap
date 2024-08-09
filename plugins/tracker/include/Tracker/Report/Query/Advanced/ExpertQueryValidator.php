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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Logical;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Selectable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;

final readonly class ExpertQueryValidator
{
    public function __construct(
        private ParserCacheProxy $parser,
        private SizeValidatorVisitor $size_validator,
    ) {
    }

    /**
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SyntaxError
     * @throws SelectablesDoNotExistException
     * @throws SelectablesAreInvalidException
     */
    public function validateExpertQuery(
        string $expert_query,
        bool $expert_mode,
        IBuildInvalidSearchablesCollection $invalid_searchables_collection_builder,
        IBuildInvalidSelectablesCollection $invalid_selectables_collection_builder,
    ): void {
        $query     = $this->parser->parse($expert_query);
        $condition = $query->getCondition();
        $this->size_validator->checkSizeOfTree($condition);

        $this->checkSearchables($condition, $invalid_searchables_collection_builder);
        $this->checkSelectables($query->getSelect(), $expert_mode, $invalid_selectables_collection_builder);
    }

    /**
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function checkSearchables(
        Logical $condition,
        IBuildInvalidSearchablesCollection $invalid_searchables_collection_builder,
    ): void {
        $invalid_searchables_collection = $invalid_searchables_collection_builder->buildCollectionOfInvalidSearchables($condition);

        $nonexistent_searchables    = $invalid_searchables_collection->getNonexistentSearchables();
        $nb_nonexistent_searchables = count($nonexistent_searchables);
        if ($nb_nonexistent_searchables > 0) {
            throw new SearchablesDoNotExistException($nonexistent_searchables);
        }

        $invalid_searchable_errors = $invalid_searchables_collection->getInvalidSearchableErrors();
        if ($invalid_searchable_errors) {
            throw new SearchablesAreInvalidException($invalid_searchable_errors);
        }
    }

    /**
     * @param Selectable[] $selectables
     * @throws SelectablesDoNotExistException
     * @throws SelectablesAreInvalidException
     * @throws SyntaxError
     */
    private function checkSelectables(
        array $selectables,
        bool $expert_mode,
        IBuildInvalidSelectablesCollection $invalid_selectables_collection_builder,
    ): void {
        if (! $expert_mode && $selectables !== []) {
            // This way user think its query is not valid tql
            throw new SyntaxError('', '', '', 0, 0, 0);
        }

        $invalid_selectables_collection = $invalid_selectables_collection_builder->buildCollectionOfInvalidSelectables($selectables);
        if ($invalid_selectables_collection->getNonExistentSelectables() !== []) {
            throw new SelectablesDoNotExistException($invalid_selectables_collection->getNonExistentSelectables());
        }

        if ($invalid_selectables_collection->getInvalidSelectablesErrors() !== []) {
            throw new SelectablesAreInvalidException($invalid_selectables_collection->getInvalidSelectablesErrors());
        }
    }
}

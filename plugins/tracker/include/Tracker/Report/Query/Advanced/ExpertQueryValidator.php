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

use PFUser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Logical;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderBy;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Query;
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
     * @throws LimitSizeIsExceededException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    public function validateExpertQuery(
        string $expert_query,
        IBuildInvalidSearchablesCollection $invalid_searchables_collection_builder,
        IBuildInvalidSelectablesCollection $invalid_selectables_collection_builder,
        IBuildInvalidOrderBy $invalid_order_by_builder,
    ): void {
        $query     = $this->parser->parse($expert_query);
        $condition = $query->getCondition();
        $this->size_validator->checkSizeOfTree($condition);

        $this->checkSearchables($condition, $invalid_searchables_collection_builder);

        $this->validate($query, $invalid_selectables_collection_builder, $invalid_order_by_builder);
    }

    /**
     * @throws LimitSizeIsExceededException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    public function validateLinks(
        string $expert_query,
        IBuildInvalidSelectablesCollection $invalid_selectables_collection_builder,
        IBuildInvalidOrderBy $invalid_order_by_builder,
    ): void {
        $query     = $this->parser->parse($expert_query);
        $condition = $query->getCondition();
        $this->size_validator->checkSizeOfTree($condition);

        $this->validate($query, $invalid_selectables_collection_builder, $invalid_order_by_builder);
    }

    /**
     * @throws OrderByIsInvalidException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     */
    private function validate(
        Query $query,
        IBuildInvalidSelectablesCollection $invalid_selectables_collection_builder,
        IBuildInvalidOrderBy $invalid_order_by_builder,
    ): void {
        $this->checkSelectables($query->getSelect(), $invalid_selectables_collection_builder);
        $this->checkOrderBy($query->getOrderBy(), $invalid_order_by_builder);
    }

    /**
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     * @throws Grammar\SyntaxError
     * @throws SyntaxNotSupportedException
     * @throws LimitSizeIsExceededException
     */
    public function validateQueryFromSingleTracker(
        string $expert_query,
        IBuildInvalidSearchablesCollection $invalid_searchables_collection_builder,
    ): void {
        $query = $this->parser->parse($expert_query);

        if ($query->getSelect() !== [] || $query->getOrderBy() !== null || $query->getFrom() !== null) {
            throw new SyntaxNotSupportedException();
        }

        $condition = $query->getCondition();
        $this->size_validator->checkSizeOfTree($condition);

        $this->checkSearchables($condition, $invalid_searchables_collection_builder);
    }

    /**
     * @throws FromIsInvalidException
     * @throws MissingFromException
     */
    public function validateFromQuery(
        Query $query,
        IBuildInvalidFromCollection $invalid_from_collection_builder,
        PFUser $user,
    ): void {
        if ($query->getFrom() === null) {
            throw new MissingFromException();
        }

        $invalid_from_collection = $invalid_from_collection_builder->buildCollectionOfInvalidFrom(
            $query->getFrom(),
            $user
        );
        if ($invalid_from_collection->getInvalidFrom() !== []) {
            throw new FromIsInvalidException($invalid_from_collection->getInvalidFrom());
        }
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
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     */
    private function checkSelectables(
        array $selectables,
        IBuildInvalidSelectablesCollection $invalid_selectables_collection_builder,
    ): void {
        $invalid_selectables_collection = $invalid_selectables_collection_builder->buildCollectionOfInvalidSelectables($selectables);
        if ($invalid_selectables_collection->getNonExistentSelectables() !== []) {
            throw new SelectablesDoNotExistException($invalid_selectables_collection->getNonExistentSelectables());
        }

        if ($invalid_selectables_collection->getInvalidSelectablesErrors() !== []) {
            throw new SelectablesAreInvalidException($invalid_selectables_collection->getInvalidSelectablesErrors());
        }
    }

    /**
     * @throws OrderByIsInvalidException
     */
    private function checkOrderBy(
        ?OrderBy $order_by,
        IBuildInvalidOrderBy $invalid_order_by_builder,
    ): void {
        if ($order_by === null) {
            return;
        }
        // No need to check for not expert mode.
        // Here if we have ORDER BY, it means we have valid SELECT, so $expert_mode === true

        $invalid_order_by = $invalid_order_by_builder->buildInvalidOrderBy($order_by);
        if ($invalid_order_by !== null) {
            throw new OrderByIsInvalidException($invalid_order_by->message, $invalid_order_by->i18n_message);
        }
    }
}

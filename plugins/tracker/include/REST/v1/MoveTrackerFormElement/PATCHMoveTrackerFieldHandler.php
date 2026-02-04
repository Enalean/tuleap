<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\MoveTrackerFormElement;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Container\Column\ColumnContainer;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\RetrieveAnyTypeOfUsedFormElementById;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\REST\v1\TrackerFieldRepresentations\MoveTrackerFieldsPATCHRepresentation;

final readonly class PATCHMoveTrackerFieldHandler
{
    public function __construct(
        private RetrieveAnyTypeOfUsedFormElementById $retrieve_used_fields_by_id,
        private FieldDao $field_dao,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function handle(TrackerFormElement $field, MoveTrackerFieldsPATCHRepresentation $payload): Ok|Err
    {
        return $this->checkFieldIsMovable($field)
            ->andThen(fn () => $this->getFieldIfAny($payload->parent_id))
            ->andThen(
                fn (Option $parent_field) =>
                    $this->checkFieldCanBeMovedIntoParent($field, $parent_field)->andThen(
                        fn () => $this->getRankInParentElement($parent_field, $payload->next_sibling_id)
                    )
                    ->andThen(
                        function (Option $rank) use ($field, $parent_field) {
                            $field->rank      = $rank->unwrapOr('end');
                            $field->parent_id = $parent_field->mapOr(fn ($parent): int => $parent->getId(), 0);

                            return $this->field_dao->save($field)
                                ? Result::ok(null)
                                : Result::err(FieldNotSavedFault::build($field));
                        }
                    )
            );
    }

    /**
     * @param Option<TrackerFormElement> $parent_field
     * @return Ok<null>|Err<Fault>
     */
    private function checkFieldCanBeMovedIntoParent(TrackerFormElement $field, Option $parent_field): Ok|Err
    {
        return $parent_field->match(
            function ($parent) use ($field) {
                if ($field instanceof FieldsetContainer) {
                    return Result::err(
                        FieldCannotBeMovedFault::buildFieldsetNotIntoTrackerRoot(),
                    );
                }

                return $this->checkParentElementIsAColumnContainer($parent);
            },
            fn () => $this->checkOnlyFieldsetsCanBeMovedAtTrackerRoot($field),
        );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function checkOnlyFieldsetsCanBeMovedAtTrackerRoot(TrackerFormElement $field): Ok|Err
    {
        if (! ($field instanceof FieldsetContainer)) {
            return Result::err(
                FieldCannotBeMovedFault::buildFieldsCanOnlyBeMovedIntoColumns()
            );
        }
        return Result::ok(null);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function checkParentElementIsAColumnContainer(TrackerFormElement $parent_field): Ok|Err
    {
        if (! ($parent_field instanceof ColumnContainer)) {
            return Result::err(
                FieldCannotBeMovedFault::buildFieldsCanOnlyBeMovedIntoColumns(),
            );
        }
        return Result::ok(null);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function checkFieldIsMovable(TrackerFormElement $field): Ok|Err
    {
        if ($field instanceof ColumnContainer) {
            return Result::err(
                FieldCannotBeMovedFault::buildColumnsCannotBeMoved(),
            );
        }
        return Result::ok(null);
    }

    /**
     * @param Option<TrackerFormElement> $parent_field
     * @return Ok<Option<int>>|Err<Fault>
     */
    private function getRankInParentElement(Option $parent_field, ?int $next_sibling_id): Ok|Err
    {
        return $this->getFieldIfAny($next_sibling_id)->andThen(
            function (Option $sibling_field) use ($parent_field) {
                return $sibling_field->mapOr(
                    function (TrackerFormElement $sibling) use ($parent_field) {
                        $parent_id = $parent_field->mapOr(fn ($parent) => $parent->getId(), 0);
                        if ($sibling->parent_id !== $parent_id) {
                            return Result::err(FieldCannotBeMovedFault::buildSiblingIsNotChildOfParent($sibling));
                        }

                        return Result::ok(Option::fromValue((int) $sibling->getRank()));
                    },
                    Result::ok(Option::nothing(\Psl\Type\int())),
                );
            },
        );
    }

    /**
     * @return Ok<Option<TrackerFormElement>>|Err<Fault>
     */
    private function getFieldIfAny(?int $field_id): Ok|Err
    {
        if ($field_id === null) {
            return Result::ok(Option::nothing(TrackerFormElement::class));
        }

        $field = $this->retrieve_used_fields_by_id->getFormElementById($field_id);
        if (! $field) {
            return Result::err(FieldCannotBeMovedFault::buildFieldUnusedOrNotFound($field_id));
        }

        return Result::ok(Option::fromValue($field));
    }
}

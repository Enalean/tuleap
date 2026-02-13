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

use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\FormElement\TrackerFormElement;

/**
 * @psalm-immutable
 */
final readonly class FieldCannotBeMovedFault extends Fault
{
    public static function buildFieldsetNotIntoTrackerRoot(): Fault
    {
        return new self('Fieldsets cannot be moved in a parent element other than the tracker root.');
    }

    public static function buildFieldsCanOnlyBeMovedIntoColumns(): Fault
    {
        return new self('Fields can only be moved into columns.');
    }

    public static function buildParentFieldIsNotAContainer(TrackerFormElement $parent_field): Fault
    {
        return new self("Field #$parent_field->id is neither a fieldset nor a column.");
    }

    public static function buildColumnsCannotBeMoved(): Fault
    {
        return new self('Column fields cannot be moved.');
    }

    public static function buildSiblingIsNotChildOfParent(TrackerFormElement $sibling): Fault
    {
        return new self("Field #$sibling->id is not a child of the target parent.");
    }

    public static function buildFieldUnusedOrNotFound(int $field_id): Fault
    {
        return new self("Field #$field_id is not found or is unused in its tracker.");
    }
}

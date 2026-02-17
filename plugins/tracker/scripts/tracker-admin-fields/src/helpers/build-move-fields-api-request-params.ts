/*
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

import type { TransformedDropContext } from "./SuccessfulDropContextTransformer";
import { isColumnWrapper } from "./is-column-wrapper";
import { isColumn } from "./is-column";
import type { MoveFieldsAPIRequestParams } from "./save-new-fields-order";

const getNextSiblingId = (next_sibling: TransformedDropContext["next_sibling"]): number | null => {
    if (!next_sibling) {
        return null;
    }

    if (isColumnWrapper(next_sibling)) {
        return next_sibling.columns[0].field.field_id;
    }

    return next_sibling.field.field_id;
};

export const buildMoveFieldsAPIRequestParams = (
    moved_element: TransformedDropContext["moved_element"],
    destination_parent: TransformedDropContext["destination_parent"],
    next_sibling: TransformedDropContext["next_sibling"],
): MoveFieldsAPIRequestParams => ({
    field_id: moved_element.field.field_id,
    parent_id: isColumn(destination_parent) ? destination_parent.field.field_id : null,
    next_sibling_id: getNextSiblingId(next_sibling),
});

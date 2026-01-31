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

import { type Result, ok, err } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { Child, ElementWithChildren } from "../type";
import { isColumnWrapper } from "./is-column-wrapper";

export const getFieldIndexInParent = (
    parent: ElementWithChildren,
    field: Child,
): Result<number, Fault> => {
    const is_field_a_column_wrapper = isColumnWrapper(field);
    const index = parent.children.findIndex((child) => {
        if (isColumnWrapper(child)) {
            return is_field_a_column_wrapper && child.identifier === field.identifier;
        }
        return !is_field_a_column_wrapper && child.field.field_id === field.field.field_id;
    });

    if (index === -1) {
        return err(
            Fault.fromMessage(
                `Unable to find index of element #${is_field_a_column_wrapper ? field.identifier : field.field.field_id} in its parent.`,
            ),
        );
    }

    return ok(index);
};

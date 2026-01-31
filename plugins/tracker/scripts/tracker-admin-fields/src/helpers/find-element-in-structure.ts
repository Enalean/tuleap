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

import type { Child, ElementWithChildren } from "../type";
import { isColumnWrapper } from "./is-column-wrapper";
import { isElementWithChildren } from "./is-element-with-children";

export const findElementInStructure = (
    element_id: number | string,
    children: ElementWithChildren["children"],
): Child | null => {
    for (const child of children) {
        if (isElementWithChildren(child)) {
            if (child.field.field_id === element_id) {
                return child;
            }

            const field = findElementInStructure(element_id, child.children);
            if (!field) {
                continue;
            }
            return field;
        }

        if (isColumnWrapper(child)) {
            if (child.identifier === element_id) {
                return child;
            }

            for (const column of child.columns) {
                if (column.field.field_id === element_id) {
                    return column;
                }

                const field = findElementInStructure(element_id, column.children);
                if (!field) {
                    continue;
                }
                return field;
            }
        }

        if ("field" in child && child.field.field_id === element_id) {
            return child;
        }
    }

    return null;
};

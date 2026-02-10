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

import { type Ref } from "vue";
import { getAttributeOrThrow } from "@tuleap/dom";
import type { PossibleDropCallbackParameter } from "@tuleap/drag-and-drop";
import type { Child, ElementWithChildren } from "../type";
import { ROOT_CONTAINER_ID } from "../type";
import { findElementInStructure } from "./find-element-in-structure";
import { isColumn } from "./is-column";
import { isFieldset } from "./is-fieldset";

export type DropRulesEnforcer = {
    isDropPossible(context: PossibleDropCallbackParameter): boolean;
};

export const getDropRulesEnforcer = (tracker_root: Ref<ElementWithChildren>): DropRulesEnforcer => {
    const getDraggedElementFromContext = (context: PossibleDropCallbackParameter): Child | null => {
        return findElementInStructure(
            Number.parseInt(getAttributeOrThrow(context.dragged_element, "data-element-id"), 10),
            tracker_root.value.children,
        );
    };

    const getDropzoneElementFromContext = (
        context: PossibleDropCallbackParameter,
    ): Child | null => {
        const dropzone_id = getAttributeOrThrow(context.target_dropzone, "data-container-id");
        if (dropzone_id === ROOT_CONTAINER_ID) {
            return null;
        }

        return findElementInStructure(
            Number.parseInt(dropzone_id, 10),
            tracker_root.value.children,
        );
    };

    return {
        isDropPossible(context): boolean {
            const dragged_element = getDraggedElementFromContext(context);
            const dropzone_element = getDropzoneElementFromContext(context);
            const is_dragged_element_a_fieldset =
                dragged_element !== null && isFieldset(dragged_element);

            if (dropzone_element === null) {
                return is_dragged_element_a_fieldset;
            }

            if (is_dragged_element_a_fieldset) {
                return false;
            }

            return isColumn(dropzone_element);
        },
    };
};

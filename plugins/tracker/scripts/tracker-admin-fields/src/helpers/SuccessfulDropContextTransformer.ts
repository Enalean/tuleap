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

import { validate as isUuid } from "uuid";
import { type Ref } from "vue";
import { type Result, ok, err } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop";
import { getAttributeOrThrow } from "@tuleap/dom";
import type { Child, ColumnWrapper, ContainerId, ElementWithChildren } from "../type";
import { ROOT_CONTAINER_ID } from "../type";
import { findElementInStructure } from "./find-element-in-structure";
import { isElementWithChildren } from "./is-element-with-children";
import { isColumnWrapper } from "./is-column-wrapper";

export type TransformedDropContext = {
    moved_element: Exclude<Child, ColumnWrapper>;
    source_parent: ElementWithChildren;
    next_sibling: Child | null;
    destination_parent: ElementWithChildren;
};

export type SuccessfulDropContextTransformer = {
    transformSuccessfulDropContext(
        context: SuccessfulDropCallbackParameter,
    ): Result<TransformedDropContext, Fault>;
};

export const getSuccessfulDropContextTransformer = (
    tracker_root: Ref<ElementWithChildren>,
): SuccessfulDropContextTransformer => {
    const getContainerId = (container: HTMLElement): ContainerId => {
        const container_id = getAttributeOrThrow(container, "data-container-id");
        if (container_id === ROOT_CONTAINER_ID) {
            return container_id;
        }
        return Number.parseInt(container_id, 10);
    };

    const findParent = (element: HTMLElement): ElementWithChildren => {
        const container_id = getContainerId(element);
        if (container_id === ROOT_CONTAINER_ID) {
            return tracker_root.value;
        }

        const parent = findElementInStructure(container_id, tracker_root.value.children);

        if (!isElementWithChildren(parent)) {
            throw new Error(`Element with id #${container_id} is not a valid container.`);
        }

        return parent;
    };

    const findElementInParent = (
        parent: ElementWithChildren,
        element: HTMLElement,
    ): TransformedDropContext["moved_element"] | null => {
        const found = findElementInStructure(
            Number.parseInt(getAttributeOrThrow(element, "data-element-id"), 10),
            parent.children,
        );

        if (found && isColumnWrapper(found)) {
            throw new Error("Elements of type ColumnWrapper cannot be moved.");
        }

        return found;
    };

    const findSiblingInParent = (
        parent: ElementWithChildren,
        element: HTMLElement,
    ): Child | null => {
        const element_id = getAttributeOrThrow(element, "data-element-id");

        return findElementInStructure(
            isUuid(element_id)
                ? element_id
                : Number.parseInt(getAttributeOrThrow(element, "data-element-id"), 10),
            parent.children,
        );
    };

    return {
        transformSuccessfulDropContext(
            context: SuccessfulDropCallbackParameter,
        ): Result<TransformedDropContext, Fault> {
            try {
                const source_parent = findParent(context.source_dropzone);
                const destination_parent = findParent(context.target_dropzone);

                const moved_element = findElementInParent(source_parent, context.dropped_element);
                if (!moved_element) {
                    return err(Fault.fromMessage("Moved element not found."));
                }

                return ok({
                    moved_element,
                    source_parent,
                    next_sibling:
                        context.next_sibling instanceof HTMLElement
                            ? findSiblingInParent(destination_parent, context.next_sibling)
                            : null,
                    destination_parent,
                });
            } catch (e) {
                return e instanceof Error
                    ? err(Fault.fromError(e))
                    : err(Fault.fromMessage("Unable to transform successful drop context."));
            }
        },
    };
};

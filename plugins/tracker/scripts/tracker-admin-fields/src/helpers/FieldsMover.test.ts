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

import { describe, it, expect, beforeEach } from "vitest";
import { v4 as uuidv4 } from "uuid";
import {
    ARTIFACT_ID_FIELD,
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    DATE_FIELD,
    STRING_FIELD,
    FLOAT_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type { Child, ContainerId, ElementWithChildren, Fieldset } from "../type";
import { ROOT_CONTAINER_ID } from "../type";
import { getFieldsMover } from "./FieldsMover";
import { findElementInStructure } from "./find-element-in-structure";
import { isElementWithChildren } from "./is-element-with-children";
import { isColumnWrapper } from "./is-column-wrapper";
import type { MoveFieldsAPIRequestParams } from "./save-new-fields-order";

describe("FieldsMover", () => {
    let tracker_root: ElementWithChildren;

    const column_wrapper_id = uuidv4();

    beforeEach(() => {
        tracker_root = {
            children: [
                {
                    field: { field_id: 1, type: CONTAINER_FIELDSET },
                    children: [
                        {
                            identifier: column_wrapper_id,
                            columns: [
                                {
                                    field: { field_id: 4, type: CONTAINER_COLUMN },
                                    children: [
                                        { field: { field_id: 6, type: STRING_FIELD } },
                                        { field: { field_id: 7, type: DATE_FIELD } },
                                    ],
                                },
                                {
                                    field: { field_id: 5, type: CONTAINER_COLUMN },
                                    children: [{ field: { field_id: 9, type: ARTIFACT_ID_FIELD } }],
                                },
                            ],
                        },
                    ],
                } as unknown as Fieldset,
                {
                    field: { field_id: 2, type: CONTAINER_FIELDSET },
                    children: [{ field: { field_id: 10, type: FLOAT_FIELD } }],
                } as unknown as Fieldset,
            ],
        };
    });

    const moveField = (context: {
        move_element_with_id: number | string;
        from_parent_with_id: ContainerId;
        before_sibling_with_id: number | string | null;
        to_destination_parent_with_id: ContainerId;
    }): MoveFieldsAPIRequestParams => {
        const moved_element = findElementInStructure(
            context.move_element_with_id,
            tracker_root.children,
        );
        const source_parent =
            context.from_parent_with_id !== ROOT_CONTAINER_ID
                ? findElementInStructure(context.from_parent_with_id, tracker_root.children)
                : tracker_root;
        const next_sibling =
            context.before_sibling_with_id !== null
                ? findElementInStructure(context.before_sibling_with_id, tracker_root.children)
                : null;
        const destination_parent =
            context.to_destination_parent_with_id !== ROOT_CONTAINER_ID
                ? findElementInStructure(
                      context.to_destination_parent_with_id,
                      tracker_root.children,
                  )
                : tracker_root;

        if (
            !moved_element ||
            isColumnWrapper(moved_element) ||
            !isElementWithChildren(source_parent) ||
            !isElementWithChildren(destination_parent)
        ) {
            throw new Error("Unable to build a test TransformedDropContext");
        }

        const result = getFieldsMover().moveField({
            moved_element,
            source_parent,
            next_sibling,
            destination_parent,
        });

        if (!result.isOk()) {
            throw new Error(`Expected an ok, got: ${result.error}`);
        }
        return result.value;
    };

    const getChildrenInParent = (parent_id: ContainerId): Child[] => {
        const parent = findElementInStructure(parent_id, tracker_root.children);
        if (!isElementWithChildren(parent)) {
            throw new Error(`Element with id #${parent_id} is not an ElementWithChildren.`);
        }
        return parent.children;
    };

    it("When there is no next sibling, the the element will be moved at the end of the destination parent", () => {
        const context = {
            move_element_with_id: 9,
            from_parent_with_id: 5,
            to_destination_parent_with_id: 4,
            before_sibling_with_id: null,
        };
        const move = moveField(context);

        expect(getChildrenInParent(context.from_parent_with_id)).toStrictEqual([]);
        expect(getChildrenInParent(context.to_destination_parent_with_id)).toStrictEqual([
            { field: { field_id: 6, type: STRING_FIELD } },
            { field: { field_id: 7, type: DATE_FIELD } },
            { field: { field_id: 9, type: ARTIFACT_ID_FIELD } },
        ]);
        expect(move).toStrictEqual({
            field_id: context.move_element_with_id,
            parent_id: context.to_destination_parent_with_id,
            next_sibling_id: null,
        });
    });

    it("When there is a next sibling, the the element will be moved before it inside the destination parent", () => {
        const context = {
            move_element_with_id: 6,
            from_parent_with_id: 4,
            to_destination_parent_with_id: 5,
            before_sibling_with_id: 9,
        };
        const move = moveField(context);

        expect(getChildrenInParent(context.from_parent_with_id)).toStrictEqual([
            { field: { field_id: 7, type: DATE_FIELD } },
        ]);
        expect(getChildrenInParent(context.to_destination_parent_with_id)).toStrictEqual([
            { field: { field_id: 6, type: STRING_FIELD } },
            { field: { field_id: 9, type: ARTIFACT_ID_FIELD } },
        ]);
        expect(move).toStrictEqual({
            field_id: context.move_element_with_id,
            parent_id: context.to_destination_parent_with_id,
            next_sibling_id: context.before_sibling_with_id,
        });
    });

    it("should be able to move fieldsets in tracker root", () => {
        const context = {
            move_element_with_id: 2,
            from_parent_with_id: ROOT_CONTAINER_ID,
            to_destination_parent_with_id: ROOT_CONTAINER_ID,
            before_sibling_with_id: 1,
        };

        const move = moveField(context);

        expect(
            tracker_root.children.map((child) => ("field" in child ? child.field.field_id : 0)),
        ).toStrictEqual([context.move_element_with_id, context.before_sibling_with_id]);

        expect(move).toStrictEqual({
            field_id: context.move_element_with_id,
            parent_id: null,
            next_sibling_id: context.before_sibling_with_id,
        });
    });

    it("should be able to move fieldsets at the end of tracker root", () => {
        const context = {
            move_element_with_id: 1,
            from_parent_with_id: ROOT_CONTAINER_ID,
            to_destination_parent_with_id: ROOT_CONTAINER_ID,
            before_sibling_with_id: null,
        };

        const move = moveField(context);

        expect(
            tracker_root.children.map((child) => ("field" in child ? child.field.field_id : 0)),
        ).toStrictEqual([2, 1]);

        expect(move).toStrictEqual({
            field_id: context.move_element_with_id,
            parent_id: null,
            next_sibling_id: null,
        });
    });
});

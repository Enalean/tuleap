/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { CONTAINER_COLUMN, CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import type { Column, ColumnWrapper, Fieldset } from "../type";

type OneColumn = "one-column";
type TwoColumns = "two-columns";
type ThreeColumns = "three-columns";
type CustomLayout = "custom";

export const ONE_COLUMN: OneColumn = "one-column";
export const TWO_COLUMNS: TwoColumns = "two-columns";
export const THREE_COLUMNS: ThreeColumns = "three-columns";
export const CUSTOM_LAYOUT: CustomLayout = "custom";

type FieldsetLayout = "one-column" | "two-columns" | "three-columns" | "custom";

export function getFieldsetLayout(fieldset: Fieldset): FieldsetLayout {
    if (fieldset.children.length === 0) {
        return CUSTOM_LAYOUT;
    }

    if (
        fieldset.children.length === 1 &&
        isChildAColumnThatDoesNotContainAContainer(fieldset.children[0])
    ) {
        return ONE_COLUMN;
    }

    if (
        fieldset.children.length === 2 &&
        fieldset.children.every((child) => isChildAColumnThatDoesNotContainAContainer(child))
    ) {
        return TWO_COLUMNS;
    }

    if (
        fieldset.children.length === 3 &&
        fieldset.children.every((child) => isChildAColumnThatDoesNotContainAContainer(child))
    ) {
        return THREE_COLUMNS;
    }

    if (fieldset.children.length === 1 && isChildAColumnWrapper(fieldset.children[0])) {
        if (
            fieldset.children[0].columns.length === 1 &&
            isChildAColumnThatDoesNotContainAContainer(fieldset.children[0].columns[0])
        ) {
            return ONE_COLUMN;
        }

        if (
            fieldset.children[0].columns.length === 2 &&
            fieldset.children[0].columns.every((child) =>
                isChildAColumnThatDoesNotContainAContainer(child),
            )
        ) {
            return TWO_COLUMNS;
        }

        if (
            fieldset.children[0].columns.length === 3 &&
            fieldset.children[0].columns.every((child) =>
                isChildAColumnThatDoesNotContainAContainer(child),
            )
        ) {
            return THREE_COLUMNS;
        }

        return CUSTOM_LAYOUT;
    }

    if (fieldset.children.every((child) => !isChildAContainer(child))) {
        return ONE_COLUMN;
    }

    return CUSTOM_LAYOUT;
}

function isChildAColumn(child: Fieldset["children"][0]): child is Column {
    return "field" in child && child.field.type === CONTAINER_COLUMN;
}

function isChildAFieldset(child: Fieldset["children"][0]): child is Fieldset {
    return "field" in child && child.field.type === CONTAINER_FIELDSET;
}

function isChildAColumnWrapper(child: Fieldset["children"][0]): child is ColumnWrapper {
    return "columns" in child;
}

function isChildAContainer(child: Fieldset["children"][0]): boolean {
    return isChildAFieldset(child) || isChildAColumnWrapper(child) || isChildAColumn(child);
}

function isChildAColumnThatDoesNotContainAContainer(child: Fieldset["children"][0]): boolean {
    return (
        "field" in child &&
        child.field.type === CONTAINER_COLUMN &&
        "children" in child &&
        child.children.every((grandchild) => !isChildAContainer(grandchild))
    );
}

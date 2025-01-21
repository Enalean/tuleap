/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 *
 */

import type { ColumnDefinition } from "../../../../../type";
import { useClassesForCollapsedColumn } from "./classes-for-collapsed-column-composable";

describe("useClassesForCollapsedColumn", () => {
    it("does not add classes when column is not collapsed", () => {
        const column: ColumnDefinition = {
            id: 1,
            label: "TODO",
            color: "inca-silver",
            mappings: [],
            is_collapsed: false,
            has_hover: false,
        };

        const classes = useClassesForCollapsedColumn(column).getClasses();
        expect(classes).toStrictEqual([]);
    });

    it("adds classes for non hovered column", () => {
        const column: ColumnDefinition = {
            id: 1,
            label: "TODO",
            color: "inca-silver",
            mappings: [],
            is_collapsed: true,
            has_hover: false,
        };
        const classes = useClassesForCollapsedColumn(column).getClasses();
        expect(classes).toStrictEqual(["taskboard-cell-collapsed"]);
    });

    it("adds classes for hovered column", () => {
        const column: ColumnDefinition = {
            id: 1,
            label: "TODO",
            color: "inca-silver",
            mappings: [],
            is_collapsed: true,
            has_hover: true,
        };
        const classes = useClassesForCollapsedColumn(column).getClasses();
        expect(classes).toStrictEqual([
            "taskboard-cell-collapsed",
            "taskboard-cell-collapsed-hover",
        ]);
    });
});

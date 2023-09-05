/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import ColumnsSkeleton from "./ColumnsSkeleton.vue";
import CardSkeleton from "./CardSkeleton.vue";
import type { ColumnDefinition } from "../../../../../type";

describe("ColumnsSkeleton", () => {
    it("displays a fixed amount of skeletons depending on column index", () => {
        [
            {
                column_index: 0,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 4,
            },
            {
                column_index: 1,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 1,
            },
            {
                column_index: 2,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 2,
            },
            {
                column_index: 3,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 3,
            },
            {
                column_index: 4,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 1,
            },
            {
                column_index: 5,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 4,
            },
            {
                column_index: 6,
                column: { is_collapsed: false } as ColumnDefinition,
                expected_number_of_skeletons: 1,
            },
        ].forEach(({ column_index, column, expected_number_of_skeletons }) => {
            const wrapper = shallowMount(ColumnsSkeleton, { propsData: { column_index, column } });

            expect(wrapper.classes("taskboard-cell-collapsed")).toBe(false);
            expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(
                expected_number_of_skeletons,
            );
        });
    });

    it("Given a column is collapsed, no skeleton is displayed in it", () => {
        const wrapper = shallowMount(ColumnsSkeleton, {
            propsData: {
                column_index: 0,
                column: { is_collapsed: true } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(0);
    });
});

/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SelectableTable from "./SelectableTable.vue";

describe(`SelectableTable`, () => {
    const getWrapper = (total: number): VueWrapper<InstanceType<typeof SelectableTable>> => {
        return shallowMount(SelectableTable, {
            props: {
                table_state: {
                    row_collection: [],
                    columns: new Set(""),
                    uuids_of_loading_rows: [],
                    uuids_of_error_rows: [],
                },
                total,
            },
        });
    };

    describe(`SelectableTable`, () => {
        it("displays EmptyState", () => {
            const wrapper = getWrapper(0);
            expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
            expect(wrapper.find("[data-test=selectable-table]").exists()).toBe(false);
        });

        it("displays table components", () => {
            const wrapper = getWrapper(2);
            expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
            expect(wrapper.find("[data-test=selectable-table]").exists()).toBe(true);
        });
    });
});

/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import type { ColumnDefinition, Swimlane } from "../../../../type";
import InvalidMappingSwimlane from "./InvalidMappingSwimlane.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ParentCell from "./ParentCell.vue";
import type { RootState } from "../../../../store/type";
import InvalidMappingCell from "./Cell/InvalidMappingCell.vue";

async function createWrapper(
    columns: ColumnDefinition[],
    swimlane: Swimlane,
): Promise<Wrapper<InvalidMappingSwimlane>> {
    return shallowMount(InvalidMappingSwimlane, {
        localVue: await createTaskboardLocalVue(),
        mocks: { $store: createStoreMock({ state: { column: { columns } } as RootState }) },
        propsData: { swimlane },
    });
}

describe(`InvalidMappingSwimlane`, () => {
    it("displays the parent card in its own cell when status does not map to a column", async () => {
        const columns = [
            { id: 2, label: "To do" } as ColumnDefinition,
            { id: 3, label: "Done" } as ColumnDefinition,
        ];
        const swimlane = { card: { id: 43, mapped_list_value: null } } as Swimlane;

        const wrapper = await createWrapper(columns, swimlane);

        expect(wrapper.findComponent(ParentCell).exists()).toBe(true);
        expect(wrapper.findAllComponents(InvalidMappingCell)).toHaveLength(2);
    });
});

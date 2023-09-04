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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SoloSwimlane from "./SoloSwimlane.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ColumnDefinition, Swimlane } from "../../../../type";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import type { RootState } from "../../../../store/type";

async function createWrapper(
    columns: ColumnDefinition[],
    swimlane: Swimlane,
): Promise<Wrapper<SoloSwimlane>> {
    return shallowMount(SoloSwimlane, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    column: { columns },
                } as RootState,
            }),
        },
        propsData: { swimlane },
    });
}

describe("SoloSwimlane", () => {
    it(`Given the solo card is in the Done column
        and that column is collapsed
        then the solo swimlane is not displayed at all`, async () => {
        const columns = [
            {
                id: 2,
                label: "To do",
                is_collapsed: false,
                mappings: [{ accepts: [{ id: 101 }] }],
            } as ColumnDefinition,
            {
                id: 3,
                label: "Done",
                is_collapsed: true,
                mappings: [{ accepts: [{ id: 103 }] }],
            } as ColumnDefinition,
        ];
        const swimlane = { card: { id: 43, mapped_list_value: { id: 103 } } } as Swimlane;
        const wrapper = await createWrapper(columns, swimlane);

        expect(wrapper.html()).toBe("");
    });
});

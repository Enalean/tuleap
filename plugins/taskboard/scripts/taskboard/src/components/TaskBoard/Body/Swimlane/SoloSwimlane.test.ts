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

import { shallowMount, Wrapper } from "@vue/test-utils";
import SoloSwimlane from "./SoloSwimlane.vue";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { ColumnDefinition, Swimlane } from "../../../../type";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import { RootState } from "../../../../store/type";

async function createWrapper(
    columns: ColumnDefinition[],
    target_column: ColumnDefinition,
    swimlane: Swimlane
): Promise<Wrapper<SoloSwimlane>> {
    return shallowMount(SoloSwimlane, {
        localVue: await createTaskboardLocalVue(),
        mocks: { $store: createStoreMock({ state: { column: { columns } } as RootState }) },
        propsData: { swimlane, column: target_column }
    });
}

describe("SoloSwimlane", () => {
    it("displays the parent card in Done column when status maps this column", async () => {
        const done_column = { id: 3, label: "Done", is_collapsed: false } as ColumnDefinition;

        const columns = [
            { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
            done_column
        ];
        const swimlane = { card: { id: 43 } } as Swimlane;
        const wrapper = await createWrapper(columns, done_column, swimlane);

        expect(wrapper.element).toMatchSnapshot();
    });

    it(`Given the parent card is in Done column
        and status maps this column
        and column is collapsed
        then swimlane is not displayed at all`, async () => {
        const done_column = { id: 3, label: "Done", is_collapsed: true } as ColumnDefinition;

        const columns = [
            { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
            done_column
        ];
        const swimlane = { card: { id: 43 } } as Swimlane;
        const wrapper = await createWrapper(columns, done_column, swimlane);

        expect(wrapper.isEmpty()).toBe(true);
    });
});

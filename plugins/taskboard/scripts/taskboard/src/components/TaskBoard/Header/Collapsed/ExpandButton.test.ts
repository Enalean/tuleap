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
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ExpandButton from "./ExpandButton.vue";
import type { ColumnDefinition } from "../../../../type";
import type { RootState } from "../../../../store/type";

async function getWrapper(column: ColumnDefinition): Promise<Wrapper<Vue>> {
    return shallowMount(ExpandButton, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    column: {},
                } as RootState,
            }),
        },
        propsData: { column },
    });
}

describe("ExpandButton", () => {
    it("Displays column label as a title", async () => {
        const column: ColumnDefinition = { label: "Done" } as ColumnDefinition;
        const wrapper = await getWrapper(column);

        expect(wrapper.attributes("title")).toBe('Expand "Done" column');
    });

    it("When user clicks on the button, the column is expanded", async () => {
        const column: ColumnDefinition = { label: "Done" } as ColumnDefinition;
        const wrapper = await getWrapper(column);

        const button = wrapper.get("[data-test=button]");
        button.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("column/expandColumn", column);
    });
});

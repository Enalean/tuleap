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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import ExpandButton from "./ExpandButton.vue";
import type { ColumnDefinition } from "../../../../type";

describe("ExpandButton", () => {
    const mock_expand_column = jest.fn();

    function getWrapper(column: ColumnDefinition): VueWrapper<InstanceType<typeof ExpandButton>> {
        return shallowMount(ExpandButton, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        column: {
                            actions: {
                                expandColumn: mock_expand_column,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            props: { column },
        });
    }
    it("Displays column label as a title", () => {
        const column: ColumnDefinition = { label: "Done" } as ColumnDefinition;
        const wrapper = getWrapper(column);

        expect(wrapper.attributes("title")).toBe('Expand "Done" column');
    });

    it("When user clicks on the button, the column is expanded", () => {
        const column: ColumnDefinition = { label: "Done" } as ColumnDefinition;
        const wrapper = getWrapper(column);

        const button = wrapper.get("[data-test=button]");
        button.trigger("click");
        expect(mock_expand_column).toHaveBeenCalledWith(expect.anything(), column);
    });
});

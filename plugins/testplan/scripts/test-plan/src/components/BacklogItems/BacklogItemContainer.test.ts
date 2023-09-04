/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import BacklogItemContainer from "./BacklogItemContainer.vue";
import BacklogItemCard from "./BacklogItemCard.vue";
import type { BacklogItem } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("BacklogItemContainer", () => {
    const load_test_defs = jest.fn();

    function createWrapper(
        backlog_item: BacklogItem,
    ): VueWrapper<InstanceType<typeof BacklogItemContainer>> {
        load_test_defs.mockReset();
        return shallowMount(BacklogItemContainer, {
            props: {
                backlog_item,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        backlog_item: {
                            namespaced: true,
                            state: backlog_item,
                            actions: {
                                loadTestDefinitions: load_test_defs,
                            },
                        },
                    },
                }),
            },
        });
    }

    it("Displays the backlog item as a card", async () => {
        const wrapper = await createWrapper({
            id: 123,
            is_expanded: false,
            are_test_definitions_loaded: false,
        } as BacklogItem);

        expect(wrapper.findComponent(BacklogItemCard).exists()).toBe(true);
    });

    it("Displays the corresponding test definitions if backlog item is expanded", async () => {
        const wrapper = await createWrapper({
            id: 123,
            is_expanded: true,
            are_test_definitions_loaded: false,
        } as BacklogItem);

        expect(wrapper.find("[data-test=async-list-test-defs]").exists()).toBe(true);
    });

    it("Hides the corresponding test definitions if backlog item is collapsed", async () => {
        const wrapper = await createWrapper({
            id: 123,
            is_expanded: false,
            are_test_definitions_loaded: false,
        } as BacklogItem);

        expect(wrapper.find("list-of-test-definitions-stub").exists()).toBe(false);
    });

    it("Automatically loads the test coverage of the backlog item", async () => {
        const backlog_item = {
            id: 123,
            is_expanded: false,
            are_test_definitions_loaded: false,
        } as BacklogItem;
        await createWrapper(backlog_item);

        expect(load_test_defs).toHaveBeenCalledWith(expect.any(Object), backlog_item);
    });
});

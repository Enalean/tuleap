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

import { shallowMount, Wrapper } from "@vue/test-utils";
import BacklogItemContainer from "./BacklogItemContainer.vue";
import BacklogItemCard from "./BacklogItemCard.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";
import { BacklogItem } from "../../type";

describe("BacklogItemContainer", () => {
    function createWrapper(backlog_item: BacklogItem): Wrapper<BacklogItemContainer> {
        return shallowMount(BacklogItemContainer, {
            propsData: {
                backlog_item,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        backlog_item: {},
                    } as RootState,
                }),
            },
            stubs: {
                "list-of-test-definitions": true,
            },
        });
    }

    it("Displays the backlog item as a card", () => {
        const wrapper = createWrapper({
            id: 123,
            is_expanded: false,
            are_test_definitions_loaded: false,
        } as BacklogItem);

        expect(wrapper.findComponent(BacklogItemCard).exists()).toBe(true);
    });

    it("Displays the corresponding test definitions if backlog item is expanded", () => {
        const wrapper = createWrapper({
            id: 123,
            is_expanded: true,
            are_test_definitions_loaded: false,
        } as BacklogItem);

        expect(wrapper.find("list-of-test-definitions-stub").exists()).toBe(true);
    });

    it("Hides the corresponding test definitions if backlog item is collapsed", () => {
        const wrapper = createWrapper({
            id: 123,
            is_expanded: false,
            are_test_definitions_loaded: false,
        } as BacklogItem);

        expect(wrapper.find("list-of-test-definitions-stub").exists()).toBe(false);
    });

    it("Automatically loads the test coverage of the backlog item", () => {
        const backlog_item = {
            id: 123,
            is_expanded: false,
            are_test_definitions_loaded: false,
        } as BacklogItem;
        const wrapper = createWrapper(backlog_item);

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "backlog_item/loadTestDefinitions",
            backlog_item
        );
    });
});

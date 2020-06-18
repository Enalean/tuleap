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
import AddTestButton from "./AddTestButton.vue";
import { createTestPlanLocalVue } from "../../../helpers/local-vue-for-test";
import { RootState } from "../../../store/type";
import { BacklogItem } from "../../../type";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("AddTestButton", () => {
    async function createWrapper(
        state: RootState,
        backlog_item: BacklogItem,
        should_empty_state_be_displayed: boolean
    ): Promise<Wrapper<AddTestButton>> {
        return shallowMount(AddTestButton, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                backlog_item,
                should_empty_state_be_displayed,
            },
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
        });
    }

    it("Does not display the button if there is no test definition tracker id", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: null } as RootState,
            { id: 123 } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Does not display the button if the test definitions are still loading", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            { id: 123, is_loading_test_definitions: true } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Does not display the button if an error occurred during the load of the test definitions", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            {
                id: 123,
                is_loading_test_definitions: false,
                has_test_definitions_loading_error: true,
            } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Displays the button if conditions are ok", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            {
                id: 123,
                is_loading_test_definitions: false,
                has_test_definitions_loading_error: false,
            } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Adds a dedicated class when we are in empty state so that we don't have too much margin", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            {
                id: 123,
                is_loading_test_definitions: false,
                has_test_definitions_loading_error: false,
            } as BacklogItem,
            true
        );

        expect(wrapper.classes("test-plan-add-test-button-with-empty-state")).toBe(true);
    });
});

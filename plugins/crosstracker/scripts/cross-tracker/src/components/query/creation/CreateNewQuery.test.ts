/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { expect, describe, it, vi } from "vitest";
import CreateNewQuery from "./CreateNewQuery.vue";
import TitleInput from "../TitleInput.vue";
import QueryEditorForCreation from "./QueryEditorForCreation.vue";

vi.useFakeTimers();

describe("CreateNewQuery", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof CreateNewQuery>> {
        return shallowMount(CreateNewQuery, {
            global: { ...getGlobalTestOptions() },
        });
    }

    it("cancels the query creation by emitting an event", async () => {
        const wrapper = getWrapper();
        await wrapper.find("[data-test=query-creation-cancel-button]").trigger("click");
        expect(wrapper.emitted()).toHaveProperty("return-to-active-query-pane");
    });

    describe("'Save' and 'Search' buttons", () => {
        it("Does not display the Save button and the 'Search' button is disabled by default", () => {
            const wrapper = getWrapper();
            expect(wrapper.find("[data-test=query-creation-save-button]").exists()).toBe(false);
            expect(
                wrapper
                    .find("[data-test=query-creation-search-button]")
                    .element.attributes.getNamedItem("disabled"),
            ).not.toBeNull();
        });
        it("Displays the disabled 'Save' button if the `Title` and `Query` fields are not empty, the 'Search' button is enabled", async () => {
            const wrapper = getWrapper();
            wrapper.findComponent(TitleInput).vm.$emit("update:title", "Some title");
            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("update:tql_query", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");
            await vi.runOnlyPendingTimersAsync();

            const saved_button = wrapper.find("[data-test=query-creation-save-button]");
            expect(saved_button.exists()).toBe(true);
            expect(saved_button.element.attributes.getNamedItem("disabled")).not.toBeNull();

            const search_button = wrapper.find("[data-test=query-creation-search-button]");
            expect(search_button.element.attributes.getNamedItem("disabled")).toBeNull();
        });
        it("Displays an enabled 'Save' button when the user search for query result, the 'Search' button becomes disabled", async () => {
            const wrapper = getWrapper();
            wrapper.findComponent(TitleInput).vm.$emit("update:title", "Some title");
            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("update:tql_query", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");

            await vi.runOnlyPendingTimersAsync();

            const search_button = wrapper.find("[data-test=query-creation-search-button]");
            await search_button.trigger("click");
            expect(search_button.element.attributes.getNamedItem("disabled")).not.toBeNull();

            const saved_button = wrapper.find("[data-test=query-creation-save-button]");
            expect(saved_button.exists()).toBe(true);
            expect(saved_button.element.attributes.getNamedItem("disabled")).toBeNull();
        });
    });
});

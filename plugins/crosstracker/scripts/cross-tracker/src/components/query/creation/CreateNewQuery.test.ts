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
import { beforeEach, describe, expect, it, vi } from "vitest";
import CreateNewQuery from "./CreateNewQuery.vue";
import QuerySuggested from "../QuerySuggested.vue";
import TitleInput from "../TitleInput.vue";
import { EMITTER, NEW_QUERY_CREATOR, WIDGET_ID } from "../../../injection-symbols";
import { Fault } from "@tuleap/fault";
import type {
    EmitterProvider,
    Events,
    NotifyFaultEvent,
    NotifySuccessEvent,
} from "../../../helpers/emitter-provider";
import {
    CLEAR_FEEDBACK_EVENT,
    NOTIFY_FAULT_EVENT,
    NOTIFY_SUCCESS_EVENT,
    SEARCH_ARTIFACTS_EVENT,
} from "../../../helpers/emitter-provider";
import QuerySelectableTable from "../QuerySelectableTable.vue";
import { PostNewQueryStub } from "../../../../tests/stubs/PostNewQueryStub";
import type { PostNewQuery } from "../../../domain/PostNewQuery";
import mitt from "mitt";

vi.useFakeTimers();

describe("CreateNewQuery", () => {
    let dispatched_clear_feedback_events: true[];
    let dispatched_fault_events: NotifyFaultEvent[];
    let dispatched_success_events: NotifySuccessEvent[];
    let dispatched_search_events: true[];
    let emitter: EmitterProvider;

    const QueryEditorForCreation = {
        name: "QueryEditorForCreation",
        template: "<div>custom query editor</div>",
        methods: {
            updateEditor: (tql_query: string): string => tql_query,
        },
    };

    beforeEach(() => {
        emitter = mitt<Events>();
        dispatched_clear_feedback_events = [];
        dispatched_fault_events = [];
        dispatched_success_events = [];
        dispatched_search_events = [];
        emitter.on(CLEAR_FEEDBACK_EVENT, () => {
            dispatched_clear_feedback_events.push(true);
        });
        emitter.on(NOTIFY_FAULT_EVENT, (event) => {
            dispatched_fault_events.push(event);
        });
        emitter.on(NOTIFY_SUCCESS_EVENT, (event) => {
            dispatched_success_events.push(event);
        });
        emitter.on(SEARCH_ARTIFACTS_EVENT, () => {
            dispatched_search_events.push(true);
        });
    });

    function getWrapper(
        new_query_creator: PostNewQuery = PostNewQueryStub.withDefaultContent(),
    ): VueWrapper<InstanceType<typeof CreateNewQuery>> {
        return shallowMount(CreateNewQuery, {
            global: {
                ...getGlobalTestOptions(),
                stubs: { QueryEditorForCreation },
                provide: {
                    [WIDGET_ID.valueOf()]: 96,
                    [EMITTER.valueOf()]: emitter,
                    [NEW_QUERY_CREATOR.valueOf()]: true,
                    [NEW_QUERY_CREATOR.valueOf()]: new_query_creator,
                },
            },
        });
    }

    it("cancels the query creation by emitting an event and clearing the feedback", async () => {
        const wrapper = getWrapper();
        await wrapper.find("[data-test=query-creation-cancel-button]").trigger("click");
        expect(wrapper.emitted()).toHaveProperty("return-to-active-query-pane");
        expect(dispatched_clear_feedback_events).toHaveLength(1);
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
    it("update the fields and the editor when the query has been chosen", () => {
        const tql_query = "SELECT @id FROM @project='self' WHERE @id > 1";
        const wrapper = getWrapper();
        const editor_component = wrapper.getComponent(QueryEditorForCreation);
        const editor_spy = vi.spyOn(editor_component.vm, "updateEditor");
        wrapper.findComponent(QuerySuggested).vm.$emit("query-chosen", {
            title: "Original title",
            description: "",
            tql_query,
        });
        expect(editor_spy).toHaveBeenCalledWith(tql_query);
    });
    describe("Searching artifact result", () => {
        it("does not display the result table when no search has been performed", () => {
            const wrapper = getWrapper();

            expect(wrapper.findComponent(QuerySelectableTable).exists()).toBe(false);
        });
        it("Search a tql query result by emitting an event when the Search button is clicked", async () => {
            const wrapper = getWrapper();

            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("update:tql_query", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");

            await vi.runOnlyPendingTimersAsync();

            await wrapper.find("[data-test=query-creation-search-button]").trigger("click");

            expect(dispatched_search_events).toHaveLength(1);
        });
        it("Search a tql query result by emitting an event when the shortcut (ctrl+enter) is pressed", async () => {
            const wrapper = getWrapper();

            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("trigger-search", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");

            await wrapper.find("[data-test=query-creation-search-button]").trigger("click");

            expect(dispatched_search_events).toHaveLength(1);
        });

        it("displays the right icon according to the loading state", async () => {
            const wrapper = getWrapper();

            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("update:tql_query", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");
            wrapper.findComponent(TitleInput).vm.$emit("update:title", "Some title");

            await vi.runOnlyPendingTimersAsync();

            // Need to trigger the search before saving
            const search_button = wrapper.find("[data-test=query-creation-search-button]");
            await search_button.trigger("click");

            const query_selectable_component = wrapper.findComponent(QuerySelectableTable);
            query_selectable_component.vm.$emit("search-started");

            await vi.runOnlyPendingTimersAsync();

            expect(
                wrapper.find("[data-test=query-creation-search-button-spin-icon]").exists(),
            ).toBe(true);
            expect(
                wrapper.find("[data-test=query-creation-search-button-search-icon]").exists(),
            ).toBe(false);

            query_selectable_component.vm.$emit("search-finished");

            await vi.runOnlyPendingTimersAsync();

            expect(
                wrapper.find("[data-test=query-creation-search-button-spin-icon]").exists(),
            ).toBe(false);
            expect(
                wrapper.find("[data-test=query-creation-search-button-search-icon]").exists(),
            ).toBe(true);
        });
    });
    describe("Saving a new query", () => {
        it("saves a new query result when the save button is clicked", async () => {
            const wrapper = getWrapper(PostNewQueryStub.withDefaultContent());

            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("update:tql_query", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");
            wrapper.findComponent(TitleInput).vm.$emit("update:title", "Some title");

            await vi.runOnlyPendingTimersAsync();

            // Need to trigger the search before saving
            const search_button = wrapper.find("[data-test=query-creation-search-button]");
            await search_button.trigger("click");

            await wrapper.find("[data-test=query-creation-save-button]").trigger("click");

            expect(dispatched_fault_events).toHaveLength(0);
            expect(dispatched_clear_feedback_events).toHaveLength(2);
            expect(dispatched_success_events).toHaveLength(1);
            expect(wrapper.emitted()).toHaveProperty("return-to-active-query-pane");
        });
        it("show an error if the save failed", async () => {
            const wrapper = getWrapper(PostNewQueryStub.withFault(Fault.fromMessage("Error")));

            wrapper
                .findComponent(QueryEditorForCreation)
                .vm.$emit("update:tql_query", "SELECT @id FROM @project = 'self' WHERE @id > 1 ");
            wrapper.findComponent(TitleInput).vm.$emit("update:title", "Some title");

            await vi.runOnlyPendingTimersAsync();

            // Need to trigger the search before saving
            const search_button = wrapper.find("[data-test=query-creation-search-button]");
            await search_button.trigger("click");

            await wrapper.find("[data-test=query-creation-save-button]").trigger("click");

            expect(dispatched_fault_events).toHaveLength(1);
            expect(dispatched_clear_feedback_events).toHaveLength(2);
            expect(dispatched_success_events).toHaveLength(0);
            expect(wrapper.emitted()).not.toHaveProperty("return-to-active-query-pane");
        });
    });
});

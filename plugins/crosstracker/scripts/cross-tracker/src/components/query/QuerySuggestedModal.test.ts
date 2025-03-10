/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import QuerySuggestedModal from "./QuerySuggestedModal.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { EMITTER } from "../../injection-symbols";
import mitt from "mitt";
import type { EmitterProvider, Events } from "../../helpers/emitter-provider";
import { DISPLAY_QUERY_PREVIEW_EVENT } from "../../helpers/emitter-provider";
import type { QuerySuggestion } from "../../domain/SuggestedQueriesGetter";

vi.useFakeTimers();

describe("QuerySuggestedModal", () => {
    let emitter: EmitterProvider;
    const query: QuerySuggestion = {
        title: "My query",
        description: "Some query description",
        tql_query: "SELECT @pretty_title FROM @project = 'self' WHERE @id >= 1",
    };

    beforeEach(() => {
        emitter = mitt<Events>();
    });

    const getWrapper = (): VueWrapper<InstanceType<typeof QuerySuggestedModal>> => {
        return shallowMount(QuerySuggestedModal, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [EMITTER.valueOf()]: emitter,
                },
            },
        });
    };

    it("Shows the modal and then send the query", async () => {
        const wrapper = getWrapper();
        // FIXME: expect(wrapper.find("[data-test=modal-query-title]").isVisible()).toBe(false);
        emitter.emit(DISPLAY_QUERY_PREVIEW_EVENT, { query });
        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=modal-query-title]").isVisible()).toBe(true);
        expect(wrapper.find("[data-test=modal-query-title]").text()).toContain("My query");
        expect(wrapper.find("[data-test=modal-query-description]").text()).toContain(
            "Some query description",
        );
        expect(wrapper.find("[data-test=modal-query-tql]").text()).toContain(
            "SELECT @pretty_title FROM @project = 'self' WHERE @id >= 1",
        );

        await wrapper.find("[data-test=modal-action-button]").trigger("click");
        const query_event = wrapper.emitted("query-chosen");
        if (query_event === undefined) {
            throw Error("event should exists");
        }
        expect(query_event[0][0]).toEqual(query);
    });

    it("Shows the modal and then do nothing", async () => {
        const wrapper = getWrapper();
        // FIXME: expect(wrapper.find("[data-test=modal-query-title]").isVisible()).toBe(false);
        emitter.emit(DISPLAY_QUERY_PREVIEW_EVENT, { query });
        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=modal-query-title]").isVisible()).toBe(true);
        expect(wrapper.find("[data-test=modal-query-title]").text()).toContain("My query");
        expect(wrapper.find("[data-test=modal-query-description]").text()).toContain(
            "Some query description",
        );
        expect(wrapper.find("[data-test=modal-query-tql]").text()).toContain(
            "SELECT @pretty_title FROM @project = 'self' WHERE @id >= 1",
        );

        await wrapper.find("[data-test=modal-cancel-button]").trigger("click");
        expect(wrapper.emitted()).not.toHaveProperty("query-chosen");
    });
});

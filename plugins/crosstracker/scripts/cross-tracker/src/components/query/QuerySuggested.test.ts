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

import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { DASHBOARD_TYPE, EMITTER, GET_SUGGESTED_QUERIES } from "../../injection-symbols";
import QuerySuggested from "./QuerySuggested.vue";
import { PROJECT_DASHBOARD, USER_DASHBOARD } from "../../domain/DashboardType";
import { SuggestedQueries } from "../../domain/SuggestedQueriesGetter";
import { createVueGettextProviderPassThrough } from "../../helpers/vue-gettext-provider-for-test";
import mitt from "mitt";
import type {
    DisplayQueryPreviewEvent,
    EmitterProvider,
    Events,
} from "../../helpers/emitter-provider";
import { DISPLAY_QUERY_PREVIEW_EVENT } from "../../helpers/emitter-provider";

describe("QuerySuggested", () => {
    const suggest_queries = SuggestedQueries(createVueGettextProviderPassThrough());
    let is_modal_should_be_displayed: boolean;
    let emitter: EmitterProvider;
    let dispatched_query_preview_event: DisplayQueryPreviewEvent[];
    const getWrapper = (
        dashboard_type: string,
    ): VueWrapper<InstanceType<typeof QuerySuggested>> => {
        return shallowMount(QuerySuggested, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [DASHBOARD_TYPE.valueOf()]: dashboard_type,
                    [GET_SUGGESTED_QUERIES.valueOf()]: suggest_queries,
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                is_modal_should_be_displayed,
            },
        });
    };
    beforeEach(() => {
        is_modal_should_be_displayed = false;
        dispatched_query_preview_event = [];
        emitter = mitt<Events>();
        emitter.on(DISPLAY_QUERY_PREVIEW_EVENT, (event) => {
            dispatched_query_preview_event.push(event);
        });
    });
    it.each([
        ["personal", USER_DASHBOARD, suggest_queries.getTranslatedPersonalSuggestedQueries()],
        ["project", PROJECT_DASHBOARD, suggest_queries.getTranslatedProjectSuggestedQueries()],
    ])(
        "should displays the queries from a %s dashboard",
        (_dashboard, dashboard_type, suggested_queries) => {
            const wrapper = getWrapper(dashboard_type);

            const suggested_query_buttons = wrapper.findAll("[data-test=query-suggested-button]");

            const expected_queries_title = suggested_queries.map((query): string => query.title);
            expect(suggested_query_buttons.length).toBe(expected_queries_title.length);

            suggested_query_buttons.forEach((button_wrapper) => {
                if (button_wrapper.element.textContent === null) {
                    throw new Error("Button title should not be null");
                }
                expect(expected_queries_title.includes(button_wrapper.element.textContent));
            });
        },
    );
    it("should display the modal if one of the query field is filled", async () => {
        is_modal_should_be_displayed = true;
        const wrapper = getWrapper(USER_DASHBOARD);

        await wrapper.find("[data-test=query-suggested-button]").trigger("click");

        expect(dispatched_query_preview_event[0].query).toStrictEqual(
            suggest_queries.getTranslatedPersonalSuggestedQueries()[0],
        );
        expect(wrapper.emitted()).not.toHaveProperty("query-chosen");
    });
    it("should does not display the modal if any of the query field is filled", async () => {
        is_modal_should_be_displayed = false;
        const wrapper = getWrapper(USER_DASHBOARD);

        await wrapper.find("[data-test=query-suggested-button]").trigger("click");

        expect(dispatched_query_preview_event.length).toBe(0);
        expect(wrapper.emitted()).toHaveProperty("query-chosen");
        expect(wrapper.emitted()["query-chosen"][0]).toStrictEqual(
            suggest_queries.getTranslatedPersonalSuggestedQueries(),
        );
    });
});

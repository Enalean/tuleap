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

import { expect, describe, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { DASHBOARD_TYPE, GET_SUGGESTED_QUERIES } from "../../injection-symbols";
import QuerySuggested from "./QuerySuggested.vue";
import { PROJECT_DASHBOARD, USER_DASHBOARD } from "../../domain/DashboardType";
import { SuggestedQueries } from "../../domain/SuggestedQueriesGetter";
import { createVueGettextProviderPassThrough } from "../../helpers/vue-gettext-provider-for-test";

describe("QuerySuggested", () => {
    const suggest_queries = SuggestedQueries(createVueGettextProviderPassThrough());

    const getWrapper = (
        dashboard_type: string,
    ): VueWrapper<InstanceType<typeof QuerySuggested>> => {
        return shallowMount(QuerySuggested, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [DASHBOARD_TYPE.valueOf()]: dashboard_type,
                    [GET_SUGGESTED_QUERIES.valueOf()]: suggest_queries,
                },
            },
        });
    };

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
});

/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import QueryResults from "./QueryResults.vue";
import * as rest_querier from "../api/rest-querier";
import QueryResultsNoUsers from "./QueryResultsNoUsers.vue";
import QueryResultsEmptyState from "./QueryResultsEmptyState.vue";
import QueryResultsErrorState from "./QueryResultsErrorState.vue";
import QueryResultsLoadingState from "./QueryResultsLoadingState.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { QueryStub } from "../../tests/stubs/QueryStub";
import type { User } from "@tuleap/core-rest-api-types";

const mireillelabeille: User = {
    id: 101,
    avatar_url: "https://example.com/users/mireillelabeille/avatar-mireillelabeille.png",
    display_name: "Mireille L'Abeille (mireillelabeille)",
    user_url: "/users/mireillelabeille",
};

describe("QueryResults", () => {
    describe("Given there is no users in the query", () => {
        it("Then it should warn the user and not call the request", () => {
            const rest = vi.spyOn(rest_querier, "getTimes");

            const wrapper = shallowMount(QueryResults, {
                props: {
                    query: QueryStub.withDefaults([]),
                    widget_id: 42,
                },
                global: {
                    ...getGlobalTestOptions(),
                },
            });

            expect(rest).not.toHaveBeenCalled();
            expect(wrapper.findComponent(QueryResultsNoUsers).exists()).toBe(true);
            expect(wrapper.findComponent(QueryResultsLoadingState).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsErrorState).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsEmptyState).exists()).toBe(false);
        });
    });

    describe("Given there is at least one user in the query", () => {
        it("Then it should display a loading state while calling the request", () => {
            const rest = vi.spyOn(rest_querier, "getTimes");

            const wrapper = shallowMount(QueryResults, {
                props: {
                    query: QueryStub.withDefaults([mireillelabeille]),
                    widget_id: 42,
                },
                global: {
                    ...getGlobalTestOptions(),
                },
            });

            expect(rest).toHaveBeenCalled();
            expect(wrapper.findComponent(QueryResultsNoUsers).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsLoadingState).exists()).toBe(true);
            expect(wrapper.findComponent(QueryResultsErrorState).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsEmptyState).exists()).toBe(false);
        });

        it("Then it should display an error state when the request ends up in error", async () => {
            const rest = vi
                .spyOn(rest_querier, "getTimes")
                .mockReturnValue(errAsync(Fault.fromMessage("Bad request")));

            const wrapper = shallowMount(QueryResults, {
                props: {
                    query: QueryStub.withDefaults([mireillelabeille]),
                    widget_id: 42,
                },
                global: {
                    ...getGlobalTestOptions(),
                },
            });

            await new Promise(process.nextTick);

            expect(rest).toHaveBeenCalled();
            expect(wrapper.findComponent(QueryResultsNoUsers).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsLoadingState).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsErrorState).exists()).toBe(true);
            expect(wrapper.findComponent(QueryResultsEmptyState).exists()).toBe(false);

            expect(wrapper.findComponent(QueryResultsErrorState).props("error_message")).toBe(
                "Error while retrieving user times: Bad request",
            );
        });

        it("Then it should display an empty state when there is no results", async () => {
            const rest = vi.spyOn(rest_querier, "getTimes").mockReturnValue(okAsync([]));

            const wrapper = shallowMount(QueryResults, {
                props: {
                    query: QueryStub.withDefaults([mireillelabeille]),
                    widget_id: 42,
                },
                global: {
                    ...getGlobalTestOptions(),
                },
            });

            await new Promise(process.nextTick);

            expect(rest).toHaveBeenCalled();
            expect(wrapper.findComponent(QueryResultsNoUsers).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsLoadingState).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsErrorState).exists()).toBe(false);
            expect(wrapper.findComponent(QueryResultsEmptyState).exists()).toBe(true);
        });
    });
});

/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { ref } from "vue";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import {
    injected_current_user_id,
    injected_pull_requests_sort_order,
    injected_repository_id,
    injected_show_closed_pull_requests,
    injected_show_pull_requests_related_to_me,
    StubInjectionSymbols,
} from "../../../tests/injection-symbols-stub";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import PullRequestsList from "./PullRequestsList.vue";
import type { StoreListFilters } from "../Filters/ListFiltersStore";
import { ListFiltersStore } from "../Filters/ListFiltersStore";
import { AuthorFilterStub } from "../../../tests/stubs/AuthorFilterStub";
import { UserStub } from "../../../tests/stubs/UserStub";
import { SORT_ASCENDANT, SORT_DESCENDANT } from "../../injection-symbols";

vi.useFakeTimers();

describe("PullRequestsList", () => {
    let filters_store: StoreListFilters;

    beforeEach(() => {
        filters_store = ListFiltersStore(ref([]));
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withDefaults(),
        );

        return shallowMount(PullRequestsList, {
            props: {
                filters_store,
            },
        });
    };

    it("should load all the pull-requests of the repository and display them", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(
            okAsync([
                PullRequestStub.buildOpenPullRequest({ id: 6 }),
                PullRequestStub.buildOpenPullRequest({ id: 5 }),
                PullRequestStub.buildOpenPullRequest({ id: 3 }),
            ]),
        );

        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findAll("[data-test=pull-request-card]").length).toBe(3);
    });

    it("When filters are added/removed of the filter store, then it should reload the list of pull-requests with the current filters list", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledOnce();
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            injected_show_closed_pull_requests.value,
            injected_show_pull_requests_related_to_me.value,
            injected_pull_requests_sort_order.value,
        );

        const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(102, "John doe"));
        filters_store.storeFilter(filter);
        await wrapper.vm.$nextTick();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledTimes(2);
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenLastCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [filter],
            injected_show_closed_pull_requests.value,
            injected_show_pull_requests_related_to_me.value,
            injected_pull_requests_sort_order.value,
        );

        filters_store.deleteFilter(filter);
        await vi.runOnlyPendingTimersAsync();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledTimes(3);
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenLastCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            injected_show_closed_pull_requests.value,
            injected_show_pull_requests_related_to_me.value,
            injected_pull_requests_sort_order.value,
        );
    });

    it("When SHOW_CLOSED_PULL_REQUESTS value changes, then it should reload the list of pull-requests with or without closed pull-requests", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledOnce();
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            false,
            injected_show_pull_requests_related_to_me.value,
            injected_pull_requests_sort_order.value,
        );

        injected_show_closed_pull_requests.value = true;
        await wrapper.vm.$nextTick();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledTimes(2);
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenLastCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            true,
            injected_show_pull_requests_related_to_me.value,
            injected_pull_requests_sort_order.value,
        );
    });

    it("When SHOW_PULL_REQUESTS_ASSIGNED_TO_ME value changes, then it should reload the list of pull-requests", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledOnce();
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            injected_show_closed_pull_requests.value,
            false,
            injected_pull_requests_sort_order.value,
        );

        injected_show_pull_requests_related_to_me.value = true;
        await wrapper.vm.$nextTick();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledTimes(2);
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenLastCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            injected_show_closed_pull_requests.value,
            true,
            injected_pull_requests_sort_order.value,
        );
    });

    it("When PULL_REQUEST_SORT_ORDER value changes, then it should reload the list of pull-requests with the current sort order", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledOnce();
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            injected_show_closed_pull_requests.value,
            injected_show_pull_requests_related_to_me.value,
            SORT_DESCENDANT,
        );

        injected_pull_requests_sort_order.value = SORT_ASCENDANT;
        await wrapper.vm.$nextTick();

        expect(tuleap_api.fetchAllPullRequests).toHaveBeenCalledTimes(2);
        expect(tuleap_api.fetchAllPullRequests).toHaveBeenLastCalledWith(
            injected_repository_id,
            injected_current_user_id,
            [],
            injected_show_closed_pull_requests.value,
            injected_show_pull_requests_related_to_me.value,
            SORT_ASCENDANT,
        );
    });

    it("When no pull-request matches the filters, then it should display an empty state", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("should call the tuleap_api_error_callback when an error occurres while the pull-requests are being retrieved", async () => {
        const tuleap_api_error_callback = vi.fn();

        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withTuleapApiErrorCallback(tuleap_api_error_callback),
        );

        const tuleap_ap_fault = Fault.fromMessage("Nope");

        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(errAsync(tuleap_ap_fault));

        getWrapper();
        await vi.runOnlyPendingTimersAsync();

        expect(tuleap_api_error_callback).toHaveBeenCalledOnce();
        expect(tuleap_api_error_callback).toHaveBeenCalledWith(tuleap_ap_fault);
    });
});

/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import type { RouteLocationNormalizedLoaded, Router } from "vue-router";

const searchInFolderMock = jest.fn();
jest.mock("../../api/rest-querier", () => {
    return {
        searchInFolder: searchInFolderMock,
    };
});

import SearchResultError from "./SearchResult/SearchResultError.vue";
import SearchContainer from "./SearchContainer.vue";
import SearchResultTable from "./SearchResult/SearchResultTable.vue";
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import type { AdvancedSearchParams } from "../../type";
import { buildAdvancedSearchParams } from "../../helpers/build-advanced-search-params";
import type { Events } from "../../helpers/emitter";
import emitter from "../../helpers/emitter";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { nextTick } from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import * as router from "../../helpers/use-router";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("SearchContainer", () => {
    let push_route_spy: jest.Mock;
    beforeEach(() => {
        searchInFolderMock.mockReset();
        push_route_spy = jest.fn();

        jest.spyOn(router, "useRouter").mockImplementation(() => {
            return { push: push_route_spy } as unknown as Router;
        });
        jest.spyOn(router, "useRoute").mockReturnValue({
            query: { q: "Lorem ipsum" },
        } as unknown as RouteLocationNormalizedLoaded);
    });

    const load_folder = jest.fn();

    function getWrapper(
        query: string,
        search_params: AdvancedSearchParams
    ): VueWrapper<InstanceType<typeof SearchContainer>> {
        return shallowMount(SearchContainer, {
            props: {
                query: search_params,
                folder_id: 101,
                offset: 0,
            },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadFolder: load_folder,
                    },
                }),
                stubs: ["router-link", "router-view"],
            },
        });
    }

    it("should automatically load the current folder so that breadcrumb is accurate when user refresh the page", () => {
        searchInFolderMock.mockResolvedValue([]);

        shallowMount(SearchContainer, {
            props: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadFolder: load_folder,
                    },
                }),
            },
        });

        expect(load_folder).toHaveBeenCalled();
    });

    it("should route to a new search if user changes global search", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams());

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit(
            "advanced-search",
            buildAdvancedSearchParams({ global_search: "Lorem ipsum do" })
        );

        expect(push_route_spy).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum do",
            },
            params: {
                folder_id: "101",
            },
        });
    });

    it("should route to a new search if user changes type", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams());

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit("advanced-search", buildAdvancedSearchParams({ type: "folder" }));

        expect(push_route_spy).toHaveBeenCalledWith({
            name: "search",
            query: {
                type: "folder",
            },
            params: {
                folder_id: "101",
            },
        });
    });

    it("should route to a new search if user changes title", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams());

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit("advanced-search", buildAdvancedSearchParams({ title: "doloret" }));

        expect(push_route_spy).toHaveBeenCalledWith({
            name: "search",
            query: {
                title: "doloret",
            },
            params: {
                folder_id: "101",
            },
        });
    });

    it("should route to a new search if user changes description", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams());

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit("advanced-search", buildAdvancedSearchParams({ description: "doloret" }));

        expect(push_route_spy).toHaveBeenCalledWith({
            name: "search",
            query: {
                description: "doloret",
            },
            params: {
                folder_id: "101",
            },
        });
    });

    it("should not route to a new search if user didn't change the criteria but still perform the search to make sure that results are accurate", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("Lorem ipsum", buildAdvancedSearchParams());

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit(
            "advanced-search",
            buildAdvancedSearchParams({ global_search: "Lorem ipsum" })
        );

        expect(push_route_spy).not.toHaveBeenCalled();
        expect(searchInFolderMock).toHaveBeenCalledTimes(1);
    });

    it("should perform a new search if user paginates through results", async () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams({ global_search: "Lorem ipsum" }));

        const expected_params: AdvancedSearchParams = buildAdvancedSearchParams({
            global_search: "Lorem ipsum",
        });
        expect(searchInFolderMock).toHaveBeenCalledWith(101, expected_params, 0);

        wrapper.setProps({ offset: 10 });
        await nextTick();

        expect(push_route_spy).not.toHaveBeenCalled();
        expect(searchInFolderMock).toHaveBeenCalledWith(101, expected_params, 10);
    });

    it("should perform a new search if user select another folder in the breadcrumb", async () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams({ global_search: "Lorem ipsum" }));

        const expected_params: AdvancedSearchParams = buildAdvancedSearchParams({
            global_search: "Lorem ipsum",
        });
        expect(searchInFolderMock).toHaveBeenCalledWith(101, expected_params, 0);

        wrapper.setProps({ folder_id: 102 });
        await nextTick();

        expect(push_route_spy).not.toHaveBeenCalled();
        expect(searchInFolderMock).toHaveBeenCalledWith(102, expected_params, 0);
        expect(load_folder).toHaveBeenCalled();
    });

    it("should search for items based on criteria", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = getWrapper("", buildAdvancedSearchParams({ global_search: "Lorem ipsum" }));

        expect(searchInFolderMock).toHaveBeenCalledWith(
            101,
            buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
            0
        );
        expect(wrapper.findComponent(SearchResultTable).exists()).toBe(true);
        expect(wrapper.findComponent(SearchResultError).exists()).toBe(false);
    });

    it("should display an error state if the query failed", async () => {
        searchInFolderMock.mockRejectedValue(
            new FetchWrapperError("Not Found", {
                status: 404,
                json: (): Promise<{ error: { code: number; message: string } }> =>
                    Promise.reject({ error: { code: 404, message: "Error on server" } }),
            } as Response)
        );

        const wrapper = getWrapper("", buildAdvancedSearchParams());

        await nextTick();
        await nextTick();

        expect(wrapper.findComponent("search-result-table-stub").exists()).toBe(false);
        expect(wrapper.findComponent("search-result-error-stub").exists()).toBe(true);
    });

    it.each<[keyof Events]>([
        ["new-item-has-just-been-created"],
        ["item-properties-have-just-been-updated"],
        ["item-permissions-have-just-been-updated"],
        ["item-has-just-been-deleted"],
        ["item-has-just-been-updated"],
    ])("should reload the page whenever %s", (event) => {
        searchInFolderMock.mockResolvedValue([]);

        const reload = jest.fn();
        Object.defineProperty(window, "location", {
            value: {
                reload,
            },
        });

        getWrapper("", buildAdvancedSearchParams({ global_search: "Lorem ipsum" }));

        emitter.emit(event);

        expect(location.reload).toHaveBeenCalled();
    });
});

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
import SearchResultError from "./SearchResult/SearchResultError.vue";

const searchInFolderMock = jest.fn();
jest.mock("../../api/rest-querier", () => {
    return {
        searchInFolder: searchInFolderMock,
    };
});

import { createLocalVue, shallowMount } from "@vue/test-utils";
import SearchContainer from "./SearchContainer.vue";
import SearchResultTable from "./SearchResult/SearchResultTable.vue";
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import type { AdvancedSearchParams } from "../../type";
import VueRouter from "vue-router";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { buildAdvancedSearchParams } from "../../helpers/build-advanced-search-params";
import type { Events } from "../../helpers/emitter";
import emitter from "../../helpers/emitter";

describe("SearchContainer", () => {
    beforeEach(() => {
        searchInFolderMock.mockReset();
    });

    it("should automatically load the current folder so that breadcrumb is accurate when user refresh the page", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = shallowMount(SearchContainer, {
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        });

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("loadFolder", 101);
    });

    it("should route to a new search if user changes global search", () => {
        searchInFolderMock.mockResolvedValue([]);

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit(
            "advanced-search",
            buildAdvancedSearchParams({ global_search: "Lorem ipsum" })
        );

        expect(router.push).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum",
            },
            params: {
                folder_id: "101",
            },
        });
    });

    it("should route to a new search if user changes type", () => {
        searchInFolderMock.mockResolvedValue([]);

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit("advanced-search", buildAdvancedSearchParams({ type: "folder" }));

        expect(router.push).toHaveBeenCalledWith({
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

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit("advanced-search", buildAdvancedSearchParams({ title: "doloret" }));

        expect(router.push).toHaveBeenCalledWith({
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

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit("advanced-search", buildAdvancedSearchParams({ description: "doloret" }));

        expect(router.push).toHaveBeenCalledWith({
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

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "Lorem ipsum" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        criteria.vm.$emit(
            "advanced-search",
            buildAdvancedSearchParams({ global_search: "Lorem ipsum" })
        );

        expect(router.push).not.toHaveBeenCalled();
        expect(searchInFolderMock).toHaveBeenCalledTimes(2);
    });

    it("should perform a new search if user paginates through results", async () => {
        searchInFolderMock.mockResolvedValue([]);

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const expected_params: AdvancedSearchParams = buildAdvancedSearchParams({
            global_search: "Lorem ipsum",
        });
        expect(searchInFolderMock).toHaveBeenCalledWith(101, expected_params, 0);

        wrapper.setProps({ offset: 10 });
        await wrapper.vm.$nextTick();

        expect(router.push).not.toHaveBeenCalled();
        expect(searchInFolderMock).toHaveBeenCalledWith(101, expected_params, 10);
    });

    it("should perform a new search if user select another folder in the breadcrumb", async () => {
        searchInFolderMock.mockResolvedValue([]);

        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
                $route: {
                    query: { q: "" },
                    params: { folder_id: "101" },
                },
                $router: router,
            },
        });

        const expected_params: AdvancedSearchParams = buildAdvancedSearchParams({
            global_search: "Lorem ipsum",
        });
        expect(searchInFolderMock).toHaveBeenCalledWith(101, expected_params, 0);

        wrapper.setProps({ folder_id: 102 });
        await wrapper.vm.$nextTick();

        expect(router.push).not.toHaveBeenCalled();
        expect(searchInFolderMock).toHaveBeenCalledWith(102, expected_params, 0);
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("loadFolder", 102);
    });

    it("should search for items based on criteria", () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        });

        expect(searchInFolderMock).toHaveBeenCalledWith(
            101,
            buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
            0
        );
        expect(wrapper.findComponent(SearchResultTable).exists()).toBe(true);
        expect(wrapper.findComponent(SearchResultError).exists()).toBe(false);
    });

    it("should display an error state if the query failed", async () => {
        searchInFolderMock.mockResolvedValue([]);

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        });

        // Due to an obscure combination of async events, and rethrow of error,
        // it is pretty difficult to assert that our Error component is displayed
        // in case of REST error (we didn't manage to catch the rethrow in the middle
        // $nextTick() call). Therefore we bypass the REST call to set manually
        // the error.
        wrapper.setData({
            error: new Error("Oups"),
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(SearchResultTable).exists()).toBe(false);
        expect(wrapper.findComponent(SearchResultError).exists()).toBe(true);
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

        shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: buildAdvancedSearchParams(),
                folder_id: 101,
                offset: 0,
            },
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        });

        emitter.emit(event);

        expect(location.reload).toHaveBeenCalled();
    });
});

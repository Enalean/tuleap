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

import { shallowMount } from "@vue/test-utils";
import SearchResultTable from "./SearchResultTable.vue";
import localVue from "../../../helpers/local-vue";
import TableBodySkeleton from "./TableBodySkeleton.vue";
import TableBodyEmpty from "./TableBodyEmpty.vue";
import TableBodyResults from "./TableBodyResults.vue";
import type { ItemSearchResult, SearchResult, SearchResultColumnDefinition } from "../../../type";
import SearchResultPagination from "./SearchResultPagination.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ConfigurationState } from "../../../store/configuration";
import VueRouter from "vue-router";
import * as route from "../../../helpers/use-router";
import type { Route } from "vue-router/types/router";

describe("SearchResultTable", () => {
    it("should display skeleton while loading", () => {
        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: true,
                results: null,
                sort: null,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            columns: [],
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(true);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyResults).exists()).toBe(false);
        expect(wrapper.findComponent(SearchResultPagination).exists()).toBe(false);
    });

    it("should display empty state when no results to display", () => {
        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 0,
                    total: 0,
                    items: [],
                } as SearchResult,
                sort: null,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            columns: [],
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(true);
        expect(wrapper.findComponent(TableBodyResults).exists()).toBe(false);
        expect(wrapper.findComponent(SearchResultPagination).exists()).toBe(false);
    });

    it("should display results", () => {
        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 1,
                    total: 172,
                    items: [{} as ItemSearchResult],
                } as SearchResult,
                sort: null,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            columns: [],
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyResults).exists()).toBe(true);
        expect(wrapper.findComponent(SearchResultPagination).exists()).toBe(true);
    });

    it("should sort title ascending", () => {
        const mocked_route = jest.spyOn(route, "useRoute");

        const query_route = {
            params: {},
            query: { q: "Lorem ipsum", sort: null },
        } as unknown as Route;

        mocked_route.mockReturnValue(query_route);

        const router = new VueRouter();
        jest.spyOn(router, "replace").mockImplementation();
        jest.spyOn(router, "push").mockImplementation();
        const mocked_router = jest.spyOn(route, "useRouter");
        mocked_router.mockReturnValue(router);

        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 1,
                    total: 172,
                    items: [{} as ItemSearchResult],
                } as SearchResult,
                sort: null,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            columns: [
                                { name: "title", label: "Label" } as SearchResultColumnDefinition,
                            ],
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        wrapper.get("[data-test=sort-title]").trigger("click");
        expect(router.replace).toHaveBeenCalledWith({
            name: "search",
        });
        expect(router.push).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum",
                sort: "title",
            },
        });
    });

    it("should sort title descending", () => {
        const mocked_route = jest.spyOn(route, "useRoute");

        const query_route = {
            params: {},
            query: { q: "Lorem ipsum", sort: null },
        } as unknown as Route;

        mocked_route.mockReturnValue(query_route);

        const router = new VueRouter();
        jest.spyOn(router, "replace").mockImplementation();
        jest.spyOn(router, "push").mockImplementation();
        const mocked_router = jest.spyOn(route, "useRouter");
        mocked_router.mockReturnValue(router);

        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 1,
                    total: 172,
                    items: [{} as ItemSearchResult],
                } as SearchResult,
                sort: { name: "title", order: "asc" },
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            columns: [
                                { name: "title", label: "Label" } as SearchResultColumnDefinition,
                            ],
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        const title_element = wrapper.get("[data-test=sort-title]");
        expect(title_element.classes()).toContain("document-search-column-is-sortable");
        title_element.trigger("click");
        expect(router.replace).toHaveBeenCalledWith({
            name: "search",
        });
        expect(router.push).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum",
                sort: "title:desc",
            },
        });
    });

    it.each([
        [
            "Custom Vroom Multi List Metadata",
            {
                name: "field_18",
                label: "Custom Vroom Multi List Metadata",
                is_multiple_value_allowed: true,
            } as SearchResultColumnDefinition,
        ],
        [
            "Location",
            {
                name: "location",
                label: "Location",
                is_multiple_value_allowed: false,
            } as SearchResultColumnDefinition,
        ],
    ])(
        "should not sort the %s column",
        (column_name: string, column: SearchResultColumnDefinition) => {
            const mocked_route = jest.spyOn(route, "useRoute");

            const query_route = {
                params: {},
                query: { q: "Lorem ipsum", sort: null },
            } as unknown as Route;

            mocked_route.mockReturnValue(query_route);

            const router = new VueRouter();
            jest.spyOn(router, "replace").mockImplementation();
            jest.spyOn(router, "push").mockImplementation();
            const mocked_router = jest.spyOn(route, "useRouter");
            mocked_router.mockReturnValue(router);

            const wrapper = shallowMount(SearchResultTable, {
                localVue,
                propsData: {
                    is_loading: false,
                    results: {
                        from: 0,
                        to: 1,
                        total: 172,
                        items: [{} as ItemSearchResult],
                    } as SearchResult,
                    sort: { name: "title", order: "asc" },
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: {
                                columns: [
                                    {
                                        name: "title",
                                        label: "Label",
                                    } as SearchResultColumnDefinition,
                                    column,
                                ],
                            } as unknown as ConfigurationState,
                        },
                    }),
                },
            });

            const column_element = wrapper.get(`[data-test=sort-${column.name}]`);
            expect(column_element.classes()).not.toContain("document-search-column-is-sortable");

            column_element.trigger("click");

            expect(router.replace).not.toHaveBeenCalledWith({
                name: "search",
            });
            expect(router.push).not.toHaveBeenCalledWith({
                name: "search",
                query: {
                    q: "Lorem ipsum",
                    sort: "title:desc",
                },
            });
        }
    );
});

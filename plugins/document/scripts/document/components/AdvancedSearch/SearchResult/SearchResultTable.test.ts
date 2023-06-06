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
import TableBodySkeleton from "./TableBodySkeleton.vue";
import TableBodyEmpty from "./TableBodyEmpty.vue";
import TableBodyResults from "./TableBodyResults.vue";
import type {
    ItemSearchResult,
    SearchResult,
    SearchResultColumnDefinition,
    State,
    Folder,
    RootState,
} from "../../../type";
import SearchResultPagination from "./SearchResultPagination.vue";
import type { ConfigurationState } from "../../../store/configuration";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import * as router from "../../../helpers/use-router";
import type { Router } from "vue-router";

describe("SearchResultTable", () => {
    let mock_push: jest.Mock;
    let mock_replace: jest.Mock;
    beforeEach(() => {
        mock_push = jest.fn();
        mock_replace = jest.fn();
        jest.spyOn(router, "useRouter").mockImplementation(() => {
            return { push: mock_push, replace: mock_replace } as unknown as Router;
        });
    });

    it("should display skeleton while loading", () => {
        const wrapper = shallowMount(SearchResultTable, {
            props: {
                is_loading: true,
                results: null,
                query: null,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                columns: [],
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: ["router-link", "router-view"],
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(true);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyResults).exists()).toBe(false);
        expect(wrapper.findComponent(SearchResultPagination).exists()).toBe(false);
    });

    it("should display empty state when no results to display", () => {
        const wrapper = shallowMount(SearchResultTable, {
            props: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 0,
                    total: 0,
                    items: [],
                } as SearchResult,
                query: null,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                columns: [],
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: ["router-link", "router-view"],
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(true);
        expect(wrapper.findComponent(TableBodyResults).exists()).toBe(false);
        expect(wrapper.findComponent(SearchResultPagination).exists()).toBe(false);
    });

    it("should display results", () => {
        const wrapper = shallowMount(SearchResultTable, {
            props: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 1,
                    total: 172,
                    items: [{} as ItemSearchResult],
                } as SearchResult,
                query: null,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                columns: [],
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: ["router-link", "router-view"],
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyResults).exists()).toBe(true);
        expect(wrapper.findComponent(SearchResultPagination).exists()).toBe(true);
    });

    it("should sort title ascending", () => {
        const wrapper = shallowMount(SearchResultTable, {
            props: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 1,
                    total: 172,
                    items: [{} as ItemSearchResult],
                } as SearchResult,
                query: {
                    global_search: "Lorem ipsum",
                    id: "",
                    filename: "",
                    type: "",
                    title: "",
                    description: "",
                    owner: "",
                    create_date: null,
                    update_date: null,
                    obsolescence_date: null,
                    status: "",
                    sort: null,
                },
            },
            global: {
                stubs: ["router-link", "router-view"],
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                columns: [
                                    {
                                        name: "title",
                                        label: "Label",
                                    } as SearchResultColumnDefinition,
                                ],
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                    state: {
                        current_folder: { id: 10 } as Folder,
                    } as State,
                }),
            },
        });

        wrapper.get("[data-test=sort-title]").trigger("click");
        expect(mock_replace).toHaveBeenCalledWith({
            name: "search",
        });
        expect(mock_push).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum",
                sort: "title",
            },
            params: { folder_id: "10" },
        });
    });

    it("should sort title descending", () => {
        const wrapper = shallowMount(SearchResultTable, {
            props: {
                is_loading: false,
                results: {
                    from: 0,
                    to: 1,
                    total: 172,
                    items: [{} as ItemSearchResult],
                } as SearchResult,
                query: {
                    global_search: "Lorem ipsum",
                    id: "",
                    filename: "",
                    type: "",
                    title: "",
                    description: "",
                    owner: "",
                    create_date: null,
                    update_date: null,
                    obsolescence_date: null,
                    status: "",
                    sort: { name: "title", order: "asc" },
                },
            },
            global: {
                stubs: ["router-link", "router-view"],
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                columns: [
                                    {
                                        name: "title",
                                        label: "Label",
                                    } as SearchResultColumnDefinition,
                                ],
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                    state: {
                        current_folder: { id: 10 } as Folder,
                    } as State,
                }),
            },
        });

        const title_element = wrapper.get("[data-test=sort-title]");
        expect(title_element.classes()).toContain("document-search-column-is-sortable");
        title_element.trigger("click");
        expect(mock_replace).toHaveBeenCalledWith({
            name: "search",
        });
        expect(mock_push).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum",
                sort: "title:desc",
            },
            params: { folder_id: "10" },
        });
    });

    it.each([
        [
            "Custom Vroom Multi List Metadata",
            {
                name: "field_18",
            } as SearchResultColumnDefinition,
        ],
        [
            "Location",
            {
                name: "location",
            } as SearchResultColumnDefinition,
        ],
    ])(
        "should not sort the %s column",
        (column_name: string, column: SearchResultColumnDefinition) => {
            const wrapper = shallowMount(SearchResultTable, {
                props: {
                    is_loading: false,
                    results: {
                        from: 0,
                        to: 1,
                        total: 172,
                        items: [{} as ItemSearchResult],
                    } as SearchResult,
                    query: {
                        global_search: "Lorem ipsum",
                        id: "",
                        filename: "",
                        type: "",
                        title: "",
                        description: "",
                        owner: "",
                        create_date: null,
                        update_date: null,
                        obsolescence_date: null,
                        status: "",
                        sort: { name: "title", order: "asc" },
                    },
                },
                global: {
                    stubs: ["router-link", "router-view"],
                    ...getGlobalTestOptions({
                        modules: {
                            configuration: {
                                state: {
                                    columns: [
                                        {
                                            name: "title",
                                            label: "Label",
                                        } as SearchResultColumnDefinition,
                                        {
                                            name: "field_18",
                                            label: "Custom Vroom Multi List Metadata",
                                            is_multiple_value_allowed: true,
                                        } as SearchResultColumnDefinition,
                                        {
                                            name: "location",
                                            label: "Location",
                                            is_multiple_value_allowed: false,
                                        } as SearchResultColumnDefinition,
                                    ],
                                } as unknown as ConfigurationState,
                                namespaced: true,
                            },
                        },
                        state: {
                            current_folder: { id: 42 } as Folder,
                        } as RootState,
                    }),
                },
            });

            const column_element = wrapper.get(`[data-test=sort-${column.name}]`);
            expect(column_element.classes()).not.toContain("document-search-column-is-sortable");

            column_element.trigger("click");

            expect(mock_replace).not.toHaveBeenCalled();
            expect(mock_push).not.toHaveBeenCalled();
        }
    );
});

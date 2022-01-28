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

import { createLocalVue, RouterLinkStub, shallowMount } from "@vue/test-utils";
import SearchCriteriaBreadcrumb from "./SearchCriteriaBreadcrumb.vue";
import type { Folder } from "../../type";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GettextPlugin from "vue-gettext";

describe("SearchCriteriaBreadcrumb", () => {
    it("should display a spinner while ascendant hierarchy is loading", () => {
        // We don't use localVue from helpers since the inclusion of VueRouter via localVue.use()
        // prevents us to properly test and mock stuff here.
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GettextPlugin, {
            translations: {},
            silent: true,
        });

        const wrapper = shallowMount(SearchCriteriaBreadcrumb, {
            localVue,
            mocks: {
                $store: createStoreMock({
                    state: {
                        current_folder_ascendant_hierarchy: [],
                        is_loading_ascendant_hierarchy: true,
                    },
                }),
                $route: {
                    params: {
                        folder_id: 101,
                    },
                    query: {
                        q: "Lorem ipsum",
                    },
                },
            },
            stubs: {
                RouterLink: RouterLinkStub,
            },
        });

        expect(wrapper).toMatchSnapshot();
    });

    it("should display a link to each ascendant folder", () => {
        // We don't use localVue from helpers since the inclusion of VueRouter via localVue.use()
        // prevents us to properly test and mock stuff here.
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GettextPlugin, {
            translations: {},
            silent: true,
        });

        const wrapper = shallowMount(SearchCriteriaBreadcrumb, {
            localVue,
            mocks: {
                $store: createStoreMock({
                    state: {
                        current_folder_ascendant_hierarchy: [
                            { id: 123, title: "Foo" } as Folder,
                            { id: 124, title: "Bar" } as Folder,
                        ],
                        is_loading_ascendant_hierarchy: false,
                    },
                }),
                $route: {
                    params: {
                        folder_id: 101,
                    },
                    query: {
                        q: "Lorem ipsum",
                    },
                },
            },
            stubs: {
                RouterLink: RouterLinkStub,
            },
        });

        expect(wrapper).toMatchSnapshot();
    });
});

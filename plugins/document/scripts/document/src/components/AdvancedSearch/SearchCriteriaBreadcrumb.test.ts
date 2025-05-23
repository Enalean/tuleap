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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import SearchCriteriaBreadcrumb from "./SearchCriteriaBreadcrumb.vue";
import type { Folder, RootState } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import * as router from "vue-router";

vi.mock("vue-router");

describe("SearchCriteriaBreadcrumb", () => {
    beforeEach(() => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            params: { item_id: "101" },
        } as unknown as RouteLocationNormalizedLoaded);
    });

    it("should display a spinner while ascendant hierarchy is loading", () => {
        const wrapper = shallowMount(SearchCriteriaBreadcrumb, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_folder_ascendant_hierarchy: [],
                        is_loading_ascendant_hierarchy: true,
                    } as unknown as RootState,
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should display a link to each ascendant folder plus a link to search in root folder", () => {
        const wrapper = shallowMount(SearchCriteriaBreadcrumb, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_folder_ascendant_hierarchy: [
                            { id: 123, title: "Foo" } as Folder,
                            { id: 124, title: "Bar" } as Folder,
                        ],
                        is_loading_ascendant_hierarchy: false,
                    } as unknown as RootState,
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});

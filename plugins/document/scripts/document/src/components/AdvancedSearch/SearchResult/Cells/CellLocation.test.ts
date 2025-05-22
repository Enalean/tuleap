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

import { describe, expect, it, vi } from "vitest";
import type { ItemSearchResult } from "../../../../type";
import { shallowMount } from "@vue/test-utils";
import CellLocation from "./CellLocation.vue";
import { createRouter, createWebHistory } from "vue-router";

import { routes } from "../../../../router/router";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

vi.mock("tlp", () => {
    return { datePicker: vi.fn() };
});
vi.mock("@tuleap/autocomplete-for-select2", () => {
    return { autocomplete_users_for_select2: vi.fn() };
});

describe("CellLocation", () => {
    it("should display path via the router link and the folder separators", () => {
        const wrapper = shallowMount(CellLocation, {
            props: {
                item: {
                    parents: [
                        {
                            id: 120,
                            title: "Path",
                        },
                        {
                            id: 121,
                            title: "To",
                        },
                        {
                            id: 122,
                            title: "Folder",
                        },
                    ],
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({}),
                plugins: [router],
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});

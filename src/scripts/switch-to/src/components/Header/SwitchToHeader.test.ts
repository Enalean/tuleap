/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import { createTestingPinia } from "@pinia/testing";
import SwitchToHeader from "./SwitchToHeader.vue";
import type { SearchForm } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("SwitchToHeader", () => {
    it("Does not display the button if search is not available (user is restricted)", () => {
        const wrapper = shallowMount(SwitchToHeader, {
            props: {
                modal: null,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "abc",
                            is_search_available: false,
                        },
                    },
                })
            ),
        });

        expect(wrapper.find("[data-test=legacy-search-button]").exists()).toBe(false);
    });

    it("Does not display the button if user didn't type anything", () => {
        const wrapper = shallowMount(SwitchToHeader, {
            props: {
                modal: null,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "",
                            is_search_available: true,
                        },
                    },
                })
            ),
        });

        expect(wrapper.find("[data-test=legacy-search-button]").exists()).toBe(false);
    });

    it("Displays the button", () => {
        const wrapper = shallowMount(SwitchToHeader, {
            props: {
                modal: null,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "abc",
                            is_search_available: true,
                            search_form: {
                                type_of_search: "soft",
                                hidden_fields: [],
                            } as SearchForm,
                        },
                    },
                })
            ),
        });

        expect(wrapper.find("[data-test=legacy-search-button]").exists()).toBe(true);
    });
});

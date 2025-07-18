/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import GlobalAppError from "./GlobalAppError.vue";

describe("GlobalAppError", () => {
    it("warns user that something is wrong with a button to show details", () => {
        const wrapper = shallowMount(GlobalAppError, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: {
                                global_error_message: "Full error message with details",
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("display more details when user click on show error", async () => {
        const error_message = "Full error message with details";
        const wrapper = shallowMount(GlobalAppError, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: {
                                global_error_message: error_message,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });

        await wrapper.get("[data-test=show-details]").trigger("click");

        expect(wrapper.text()).toMatch(error_message);
    });

    it("warns user that something is wrong without any details", () => {
        const wrapper = shallowMount(GlobalAppError, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: {
                                global_error_message: "",
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
        expect(wrapper.find("[data-test=show-details]").exists()).toBe(false);
        expect(wrapper.find("[data-test=details]").exists()).toBe(false);
    });
});

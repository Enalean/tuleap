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
import TroveCatLink from "./TroveCatLink.vue";
import { createTestingPinia } from "@pinia/testing";
import { createSwitchToLocalVue } from "../../helpers/local-vue-for-test";

describe("TroveCatLink", () => {
    it("Display the link", async () => {
        const wrapper = shallowMount(TroveCatLink, {
            localVue: await createSwitchToLocalVue(),
            pinia: createTestingPinia({
                initialState: {
                    root: {
                        is_trove_cat_enabled: true,
                    },
                },
            }),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Does net display the link to trove cat if it is deactivated", async () => {
        const wrapper = shallowMount(TroveCatLink, {
            localVue: await createSwitchToLocalVue(),
            pinia: createTestingPinia({
                initialState: {
                    root: {
                        is_trove_cat_enabled: false,
                    },
                },
            }),
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});

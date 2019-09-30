/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import App from "./App.vue";
import { createProjectAdminBannerLocalVue } from "../helpers/local-vue-for-tests";

describe("App", () => {
    it("displays something when no banner is set", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: ""
            }
        });

        expect(wrapper.text()).not.toBe("");
    });

    it("displays raw banner content when the banner is set", async () => {
        const banner_message = "<b>My banner content</b>";

        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: banner_message
            }
        });

        expect(wrapper.text()).toBe(banner_message);
    });
});

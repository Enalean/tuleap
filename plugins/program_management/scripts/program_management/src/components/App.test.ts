/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { ShallowMountOptions } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import { createProgramManagementLocalVue } from "../helpers/local-vue-for-test";

describe("App", () => {
    let component_options: ShallowMountOptions<App>;

    it("Displays the backlog section", async () => {
        component_options = {
            propsData: {
                project_public_name: "Public name",
                project_short_name: "short-name",
            },
            localVue: await createProgramManagementLocalVue(),
        };

        const wrapper = shallowMount(App, component_options);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(true);
    });
});

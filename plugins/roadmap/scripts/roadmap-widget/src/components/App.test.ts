/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { createRoadmapLocalVue } from "../helpers/local-vue-for-test";
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";

describe("App", () => {
    it("Displays an empty state", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createRoadmapLocalVue(),
        });

        expect(wrapper.findComponent(NoDataToShowEmptyState)).toBeTruthy();
    });
});

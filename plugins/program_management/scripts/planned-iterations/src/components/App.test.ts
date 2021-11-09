/**
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

import type { Wrapper } from "@vue/test-utils";

import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import { createPlanIterationsLocalVue } from "../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("App", () => {
    async function createWrapper(): Promise<Wrapper<App>> {
        return shallowMount(App, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        program_increment: {
                            id: 666,
                            title: "Mating",
                        },
                    },
                }),
            },
            localVue: await createPlanIterationsLocalVue(),
        });
    }

    it("Displays nothing for the moment but the breadcrumbs, the increment title and an empty state", async () => {
        const wrapper = await createWrapper();
        const header_title = wrapper.find("[data-test=app-header-title]");
        expect(wrapper.find("[data-test=app-breadcrumbs]").exists()).toBe(true);
        expect(wrapper.find("[data-test=app-tmp-empty-state]").exists()).toBe(true);
        expect(header_title.exists()).toBe(true);
        expect(header_title.text()).toBe("Mating");
    });
});

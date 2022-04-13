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
import EmptyState from "./EmptyState.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("EmptyState", () => {
    let component_options: ShallowMountOptions<EmptyState>;

    it("Displays the empty state for Program Increment", async () => {
        component_options = {
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            can_create_program_increment: false,
                            tracker_program_increment_id: 1,
                        },
                    },
                }),
            },
        };

        const wrapper = shallowMount(EmptyState, component_options);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Display the create new program increment button", async () => {
        component_options = {
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            can_create_program_increment: true,
                            tracker_program_increment_sub_label: "Foo",
                        },
                    },
                }),
            },
        };

        const wrapper = shallowMount(EmptyState, component_options);

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(true);
        expect(wrapper.get("[data-test=create-program-increment-button]").html()).toContain("Foo");
    });

    it("No button is displayed when user can not add program increments", async () => {
        component_options = {
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: { configuration: { can_create_program_increment: false } },
                }),
            },
        };

        const wrapper = shallowMount(EmptyState, component_options);

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(false);
    });
});

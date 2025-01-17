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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import EmptyState from "./EmptyState.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("EmptyState", () => {
    async function getWrapper(can_create_program_increment: boolean): Promise<Wrapper<Vue>> {
        return shallowMount(EmptyState, {
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            can_create_program_increment,
                            tracker_program_increment_sub_label: "Foo",
                        },
                    },
                }),
            },
        });
    }

    it("Display the create new program increment button", async () => {
        const wrapper = await getWrapper(true);

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(true);
        expect(wrapper.get("[data-test=create-program-increment-button]").html()).toContain("Foo");
    });

    it("No button is displayed when user can not add program increments", async () => {
        const wrapper = await getWrapper(false);

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(false);
    });
});

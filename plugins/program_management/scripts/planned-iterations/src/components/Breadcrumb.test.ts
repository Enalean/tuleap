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
import Breadcrumb from "./Breadcrumb.vue";
import { createPlanIterationsLocalVue } from "../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("Breadcrumb", () => {
    async function getWrapper(is_program_admin: boolean): Promise<Wrapper<Breadcrumb>> {
        return shallowMount(Breadcrumb, {
            localVue: await createPlanIterationsLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            program: {
                                program_label: "Guinea pig",
                                program_shortname: "guinea-pig",
                                program_icon: "🐹",
                            },
                            program_increment: {
                                id: 666,
                                title: "Mating",
                            },
                            program_privacy: {},
                            program_flags: [],
                            is_program_admin,
                        },
                    },
                }),
            },
        });
    }

    it("When user is not program admin, Then breadcrumb does not contain administration link", async () => {
        const wrapper = await getWrapper(false);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("When user is program admin, Then administration link is displayed", async () => {
        const wrapper = await getWrapper(true);
        expect(wrapper.find("[data-test=breadcrumb-item-switchable]").classes()).toContainEqual(
            "breadcrumb-switchable",
        );
        expect(wrapper.find("[data-test=breadcrumb-item-administration]").exists()).toBeTruthy();
    });
});

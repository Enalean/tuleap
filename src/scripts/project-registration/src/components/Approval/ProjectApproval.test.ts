/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import ProjectApproval from "./ProjectApproval.vue";
import VueRouter from "vue-router";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";

describe("ProjectApproval -", () => {
    let router: VueRouter;
    let is_template_selected = true;
    beforeEach(() => {
        router = new VueRouter({
            routes: [
                {
                    path: "/new",
                    name: "template",
                },
            ],
        });
    });

    async function getWrapper(): Promise<Wrapper<ProjectApproval>> {
        const useStore = defineStore("root", {
            getters: {
                has_error: () => false,
                is_template_selected: () => {
                    return is_template_selected;
                },
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectApproval, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
            router,
        });
    }

    it("Spawns the ProjectApproval component", async () => {
        is_template_selected = true;
        const wrapper = await getWrapper();

        expect(wrapper).toMatchSnapshot();
    });

    it("redirects user on /new when he does not have all needed information to start his project creation", async () => {
        is_template_selected = false;
        const wrapper = await getWrapper();

        expect(wrapper.vm.$route.name).toBe("template");
    });
});

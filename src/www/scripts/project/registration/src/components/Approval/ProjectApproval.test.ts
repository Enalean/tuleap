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

import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import ProjectApproval from "./ProjectApproval.vue";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import VueRouter from "vue-router";

describe("ProjectApproval -", () => {
    let router: VueRouter;
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
    it("Spawns the ProjectApproval component", async () => {
        const getters = {
            has_error: false,
            is_template_selected: true,
        };

        const store_options = {
            getters,
        };

        const store = createStoreMock(store_options);

        const wrapper = shallowMount(ProjectApproval, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
            router,
        });

        expect(wrapper).toMatchSnapshot();
    });

    it("redirects user on /new when he does not have all needed information to start his project creation", async () => {
        const getters = {
            has_error: false,
            is_template_selected: false,
        };

        const store = createStoreMock({ getters });
        const wrapper = shallowMount(ProjectApproval, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
            router,
        });
        expect(wrapper.vm.$route.name).toBe("template");
    });
});

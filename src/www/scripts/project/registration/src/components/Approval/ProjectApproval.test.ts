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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import ProjectApproval from "./ProjectApproval.vue";
import { State } from "../../store/type";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import VueRouter from "vue-router";

describe("ProjectApproval - ", () => {
    let factory: Wrapper<ProjectApproval>, router: VueRouter;
    beforeEach(async () => {
        const state: State = {
            selected_template: {
                title: "string",
                description: "string",
                name: "string",
                svg: "string"
            },
            tuleap_templates: [],
            are_restricted_users_allowed: false,
            project_default_visibility: "",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            trove_categories: [],
            is_description_required: false,
            project_fields: []
        };

        router = new VueRouter({
            routes: [
                {
                    path: "/",
                    name: "template"
                },
                {
                    path: "/information",
                    name: "information"
                }
            ]
        });

        const getters = {
            has_error: false,
            is_template_selected: false
        };

        const store_options = {
            state,
            getters
        };

        const store = createStoreMock(store_options);

        factory = shallowMount(ProjectApproval, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
            router
        });
    });
    it("Spawns the ProjectApproval component", () => {
        const wrapper = factory;

        expect(wrapper).toMatchSnapshot();
    });

    it("redirects user on /template when he does not have all needed information to start his project creation", () => {
        const wrapper = factory;
        wrapper.vm.$store.state.selected_template = null;

        expect(wrapper.vm.$route.name).toBe("template");
    });
});

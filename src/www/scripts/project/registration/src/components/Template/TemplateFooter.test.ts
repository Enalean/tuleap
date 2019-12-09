/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import VueRouter from "vue-router";
import { Store } from "vuex-mock-store";
import { State } from "../../store/type";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import TemplateFooter from "./TemplateFooter.vue";

describe("TemplateFooter", () => {
    let factory: Wrapper<TemplateFooter>, router: VueRouter, store: Store, state: State;
    beforeEach(async () => {
        state = {
            selected_template: null,
            tuleap_templates: [],
            are_restricted_users_allowed: false,
            project_default_visibility: "public",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            trove_categories: [],
            is_description_required: false,
            project_fields: []
        };
        const getters = {
            is_template_selected: false
        };

        const store_options = {
            state,
            getters
        };

        store = createStoreMock(store_options);

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

        factory = shallowMount(TemplateFooter, {
            localVue: await createProjectRegistrationLocalVue(),
            router,
            mocks: { $store: store }
        });
    });
    it(`Enables the 'Next' button when template is selected`, () => {
        const wrapper = factory;

        const next_button: HTMLButtonElement = wrapper.find(
            "[data-test=project-registration-next-button]"
        ).element as HTMLButtonElement;

        expect(next_button.getAttribute("disabled")).toBe("disabled");

        wrapper.vm.$store.getters.is_template_selected = true;

        expect(next_button.getAttribute("disabled")).toBeNull();
    });

    it(`Go to 'Project information' step when the 'Next' button is clicked`, () => {
        const wrapper = factory;

        wrapper.vm.$store.getters.is_template_selected = true;

        wrapper.find("[data-test=project-registration-next-button]").trigger("click");

        expect(wrapper.vm.$route.name).toBe("information");
    });
});

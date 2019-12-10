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
import { Store } from "vuex-mock-store";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { State } from "../../store/type";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";

describe("ProjectInformationFooter", () => {
    let factory: Wrapper<ProjectInformationFooter>, store: Store;

    beforeEach(async () => {
        const state: State = {
            selected_template: {
                title: "scrum",
                description: "scrum desc",
                name: "scrum",
                svg: "<svg></svg>"
            },
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

        const store_options = {
            state
        };
        store = createStoreMock(store_options);

        factory = shallowMount(ProjectInformationFooter, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store }
        });
    });

    it(`reset the selected template when the 'Back' button is clicked`, () => {
        factory.find("[data-test=project-registration-back-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("setSelectedTemplate", null);
    });

    it(`Displays spinner when project is creating`, () => {
        factory.vm.$store.getters.has_error = false;
        factory.vm.$store.state.is_creating_project = true;

        expect(factory.find("[data-test=project-submission-icon]").classes()).toEqual([
            "fa",
            "tlp-button-icon-right",
            "fa-spin",
            "fa-circle-o-notch"
        ]);
    });

    it(`Does not display spinner by default`, () => {
        factory.vm.$store.getters.has_error = false;
        factory.vm.$store.state.is_creating_project = false;

        expect(factory.find("[data-test=project-submission-icon]").classes()).toEqual([
            "fa",
            "tlp-button-icon-right",
            "fa-arrow-circle-o-right"
        ]);
    });
});

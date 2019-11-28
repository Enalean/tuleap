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
import * as location_helper from "../../helpers/location-helper";
import { Store } from "vuex-mock-store";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { ProjectNameProperties, ProjectProperties } from "../../type";
import { State } from "../../store/type";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";

describe("ProjectInformationFooter", () => {
    let factory: Wrapper<ProjectInformationFooter>,
        store: Store,
        project_properties: ProjectProperties;
    beforeEach(async () => {
        const state: State = {
            selected_template: {
                title: "scrum",
                description: "scrum desc",
                name: "scrum",
                svg: "<svg></svg>"
            },
            tuleap_templates: []
        };

        const store_options = {
            state
        };
        store = createStoreMock(store_options);

        project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            allow_restricted: true,
            xml_template_name: "scrum"
        };

        const project_name_properties: ProjectNameProperties = {
            slugified_name: "this-is-a-test",
            name: "this is a test",
            is_valid: true
        };

        factory = shallowMount(ProjectInformationFooter, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: {
                project_name_properties: project_name_properties,
                is_public: true
            },
            mocks: { $store: store }
        });
    });

    it(`reset the selected template when the 'Back' button is clicked`, () => {
        factory.find("[data-test=project-registration-back-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("setSelectedTemplate", null);
    });

    it(`create the new project and redirect user on his own personal dashboard`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.find("[data-test=project-registration-next-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith("/my");
    });
});

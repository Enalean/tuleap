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
import { ProjectNameProperties } from "../../type";
import { State } from "../../store/type";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import VueRouter from "vue-router";

describe("ProjectInformationFooter", () => {
    let factory: Wrapper<ProjectInformationFooter>, store: Store, router: VueRouter;

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
            trove_categories: []
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
                },
                {
                    path: "/approval",
                    name: "approval"
                }
            ]
        });

        const store_options = {
            state
        };
        store = createStoreMock(store_options);

        const project_name_properties: ProjectNameProperties = {
            slugified_name: "this-is-a-test",
            name: "this is a test"
        };

        factory = shallowMount(ProjectInformationFooter, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: {
                project_name_properties: project_name_properties,
                is_public: true,
                privacy: "public",
                trove_cats: []
            },
            mocks: { $store: store },
            router
        });
    });

    it(`reset the selected template when the 'Back' button is clicked`, () => {
        factory.find("[data-test=project-registration-back-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("setSelectedTemplate", null);
    });

    it(`creates the new project and redirect user on his own personal dashboard`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            categories: [],
            xml_template_name: "scrum"
        };

        factory.find("[data-test=project-registration-next-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });
    it(`create the new private project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.setProps({ privacy: "private" });

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: false,
            allow_restricted: true,
            categories: [],
            xml_template_name: "scrum"
        };

        factory.find("[data-test=project-registration-next-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`creates the new private without restricted project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.setProps({ privacy: "private-wo-restr" });

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: false,
            allow_restricted: false,
            categories: [],
            xml_template_name: "scrum"
        };

        factory.find("[data-test=project-registration-next-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`creates the new public restricted project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.setProps({ privacy: "public" });

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            allow_restricted: false,
            categories: [],
            xml_template_name: "scrum"
        };

        factory.find("[data-test=project-registration-next-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`creates the new public including restricted restricted project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.setProps({ privacy: "unrestricted" });

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            allow_restricted: true,
            categories: [],
            xml_template_name: "scrum"
        };

        factory.find("[data-test=project-registration-next-button]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
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

    it(`Redirects user on waiting for validation when project needs a site administrator approval`, async () => {
        factory.vm.$store.state.is_project_approval_required = true;

        factory.find("[data-test=project-registration-next-button]").trigger("click");

        await factory.vm.$nextTick().then(() => {});

        expect(factory.vm.$route.name).toBe("approval");
    });
});

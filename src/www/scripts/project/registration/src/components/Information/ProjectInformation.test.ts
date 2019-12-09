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
import ProjectInformation from "./ProjectInformation.vue";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import UnderConstructionInformation from "../UnderConstructionInformation.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import ProjectInformationInputPrivacySwitch from "./Input/ProjectInformationInputPrivacySwitch.vue";
import ProjectName from "./Input/ProjectName.vue";
import ProjectInformationInputPrivacyList from "./Input/ProjectInformationInputPrivacyList.vue";
import { State } from "../../store/type";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import EventBus from "../../helpers/event-bus";
import VueRouter from "vue-router";
import * as location_helper from "../../helpers/location-helper";
import { Store } from "vuex-mock-store";

describe("ProjectInformation - ", () => {
    let factory: Wrapper<ProjectInformation>, router: VueRouter, store: Store;
    beforeEach(async () => {
        const state: State = {
            selected_template: {
                title: "string",
                description: "string",
                name: "scrum",
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
                },
                {
                    path: "/approval",
                    name: "approval"
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

        store = createStoreMock(store_options);

        factory = shallowMount(ProjectInformation, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
            router
        });
    });
    it("Spawns the ProjectInformation component", () => {
        const wrapper = factory;

        wrapper.vm.$store.getters.has_error = false;

        expect(wrapper.contains(ProjectInformationSvg)).toBe(true);
        expect(wrapper.contains(UnderConstructionInformation)).toBe(true);
        expect(wrapper.contains(ProjectInformationFooter)).toBe(true);
        expect(wrapper.contains(ProjectInformationInputPrivacySwitch)).toBe(true);
        expect(wrapper.contains(ProjectName)).toBe(true);

        expect(wrapper.contains("[data-test=project-creation-failed]")).toBe(false);
    });

    it("Displays error message", () => {
        const wrapper = factory;

        wrapper.vm.$store.getters.has_error = true;

        expect(wrapper.contains(ProjectInformationSvg)).toBe(true);
        expect(wrapper.contains(UnderConstructionInformation)).toBe(true);
        expect(wrapper.contains(ProjectInformationFooter)).toBe(true);
        expect(wrapper.contains(ProjectInformationInputPrivacySwitch)).toBe(true);
        expect(wrapper.contains(ProjectInformationInputPrivacyList)).toBe(false);
        expect(wrapper.contains(ProjectName)).toBe(true);

        expect(wrapper.contains("[data-test=project-creation-failed]")).toBe(true);
    });
    it("displays the switch when restricted users are NOT allowed in the plateform", () => {
        const wrapper = factory;

        wrapper.vm.$store.state.are_restricted_users_allowed = false;

        expect(wrapper.contains("[data-test=register-new-project-information-switch]")).toBe(true);

        const form = wrapper.find("[data-test=register-new-project-information-form]");
        expect(
            form.classes("register-new-project-information-form-container-restricted-allowed")
        ).toBe(false);
        expect(wrapper.contains("[data-test=register-new-project-information-list]")).toBe(false);
    });
    it("displays the privacy list when restricted users are allowed in the plateform", () => {
        const wrapper = factory;
        wrapper.vm.$store.state.are_restricted_users_allowed = true;

        expect(wrapper.contains("[data-test=register-new-project-information-switch]")).toBe(false);

        const form = wrapper.find("[data-test=register-new-project-information-form]");
        expect(
            form.classes("register-new-project-information-form-container-restricted-allowed")
        ).toBe(true);
        expect(wrapper.contains("[data-test=register-new-project-information-list]")).toBe(true);
    });

    it("redirects user on /template when he does not have all needed information to start his project creation", () => {
        const wrapper = factory;
        wrapper.vm.$store.state.selected_template = null;

        expect(wrapper.vm.$route.name).toBe("template");
    });

    describe("TroveCatProperties update - ", () => {
        it("build the trovecat object", () => {
            const wrapper = factory;
            expect(wrapper.vm.$data.trove_cats).toStrictEqual([]);

            EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 10 });
            expect(wrapper.vm.$data.trove_cats).toStrictEqual([{ category_id: 1, value_id: 10 }]);

            EventBus.$emit("choose-trove-cat", { category_id: 2, value_id: 20 });
            expect(wrapper.vm.$data.trove_cats).toStrictEqual([
                { category_id: 1, value_id: 10 },
                { category_id: 2, value_id: 20 }
            ]);

            EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 100 });
            expect(wrapper.vm.$data.trove_cats).toStrictEqual([
                { category_id: 1, value_id: 100 },
                { category_id: 2, value_id: 20 }
            ]);
        });
    });

    it(`creates the new project and redirect user on his own personal dashboard`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            description: "",
            categories: [],
            xml_template_name: "scrum",
            fields: []
        };

        factory.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test"
        };

        factory.find("[data-test=project-registration-form]").trigger("submit.prevent");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });
    it(`create the new private project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.vm.$data.selected_visibility = "private";

        factory.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test"
        };

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: false,
            description: "",
            allow_restricted: true,
            categories: [],
            xml_template_name: "scrum",
            fields: []
        };

        factory.find("[data-test=project-registration-form]").trigger("submit.prevent");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`creates the new private without restricted project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.vm.$data.selected_visibility = "private-wo-restr";
        factory.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test"
        };

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: false,
            description: "",
            allow_restricted: false,
            categories: [],
            xml_template_name: "scrum",
            fields: []
        };

        factory.find("[data-test=project-registration-form]").trigger("submit.prevent");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`creates the new public restricted project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.vm.$data.selected_visibility = "public";
        factory.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test"
        };

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            allow_restricted: false,
            description: "",
            categories: [],
            xml_template_name: "scrum",
            fields: []
        };

        factory.find("[data-test=project-registration-form]").trigger("submit.prevent");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`creates the new public including restricted restricted project`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        factory.vm.$store.state.are_restricted_users_allowed = true;
        factory.vm.$data.selected_visibility = "unrestricted";
        factory.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test"
        };

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            allow_restricted: true,
            description: "",
            categories: [],
            xml_template_name: "scrum",
            fields: []
        };

        factory.find("[data-test=project-registration-form]").trigger("submit.prevent");
        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await factory.vm.$nextTick().then(() => {});

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true"
        );
    });

    it(`Redirects user on waiting for validation when project needs a site administrator approval`, async () => {
        factory.vm.$store.state.is_project_approval_required = true;

        factory.find("[data-test=project-registration-form]").trigger("submit.prevent");

        await factory.vm.$nextTick().then(() => {});

        expect(factory.vm.$route.name).toBe("approval");
    });

    describe("Field list update - ", () => {
        it("build the field list object", () => {
            const wrapper = factory;
            expect(wrapper.vm.$data.field_list).toStrictEqual([]);

            EventBus.$emit("update-field-list", { field_id: 1, value: "test value" });
            expect(wrapper.vm.$data.field_list).toStrictEqual([
                { field_id: 1, value: "test value" }
            ]);

            EventBus.$emit("update-field-list", { field_id: 2, value: "other value" });
            expect(wrapper.vm.$data.field_list).toStrictEqual([
                { field_id: 1, value: "test value" },
                { field_id: 2, value: "other value" }
            ]);

            EventBus.$emit("update-field-list", { field_id: 1, value: "updated value" });
            expect(wrapper.vm.$data.field_list).toStrictEqual([
                { field_id: 1, value: "updated value" },
                { field_id: 2, value: "other value" }
            ]);
        });
    });
});

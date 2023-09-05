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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import ProjectInformation from "./ProjectInformation.vue";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import ProjectName from "./Input/ProjectName.vue";
import ProjectInformationInputPrivacyList from "./Input/ProjectInformationInputPrivacyList.vue";
import type { RootState } from "../../store/type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import EventBus from "../../helpers/event-bus";
import VueRouter from "vue-router";
import * as location_helper from "../../helpers/location-helper";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import type { ProjectProperties, TemplateData } from "../../type";
import type { ConfigurationState } from "../../store/configuration";

describe("ProjectInformation -", () => {
    let factory: Wrapper<ProjectInformation>,
        router: VueRouter,
        store: Store,
        root_state: RootState,
        configuration_state: ConfigurationState;
    beforeEach(() => {
        router = new VueRouter({
            routes: [
                {
                    path: "/new",
                    name: "template",
                },
                {
                    path: "/information",
                    name: "information",
                },
                {
                    path: "/approval",
                    name: "approval",
                },
            ],
        });
    });
    describe("User can choose visibility and restricted users are allowed -", () => {
        beforeEach(async () => {
            configuration_state = {
                are_restricted_users_allowed: true,
                can_user_choose_project_visibility: true,
            } as ConfigurationState;

            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            store = createStoreMock({
                state: {
                    selected_tuleap_template: {
                        title: "string",
                        description: "string",
                        id: "scrum",
                        glyph: "string",
                        is_built_in: true,
                    },
                    configuration: configuration_state,
                },
                getters,
            });

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });
        });

        it("Spawns the ProjectInformation component", () => {
            const wrapper = factory;

            wrapper.vm.$store.getters.has_error = false;

            expect(wrapper.findComponent(ProjectInformationSvg).exists()).toBe(true);
            expect(wrapper.findComponent(ProjectInformationFooter).exists()).toBe(true);
            expect(wrapper.findComponent(ProjectName).exists()).toBe(true);

            expect(wrapper.find("[data-test=project-creation-failed]").exists()).toBe(false);
        });

        it("Displays error message", async () => {
            const wrapper = factory;

            wrapper.vm.$store.getters.has_error = true;
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(ProjectInformationSvg).exists()).toBe(true);
            expect(wrapper.findComponent(ProjectInformationFooter).exists()).toBe(true);
            expect(wrapper.findComponent(ProjectInformationInputPrivacyList).exists()).toBe(true);
            expect(wrapper.findComponent(ProjectName).exists()).toBe(true);

            expect(wrapper.find("[data-test=project-creation-failed]").exists()).toBe(true);
        });

        it("redirects user on /new when he does not have all needed information to start his project creation", async () => {
            const getters = {
                has_error: false,
                is_template_selected: false,
            };

            store = createStoreMock({
                state: { root_state, configuration: configuration_state },
                getters,
            });

            const wrapper = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });

            expect(wrapper.vm.$route.name).toBe("template");
        });

        describe("TroveCatProperties update -", () => {
            it("build the trovecat object", () => {
                const wrapper = factory;
                expect(wrapper.vm.$data.trove_cats).toStrictEqual([]);

                EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 10 });
                expect(wrapper.vm.$data.trove_cats).toStrictEqual([
                    { category_id: 1, value_id: 10 },
                ]);

                EventBus.$emit("choose-trove-cat", { category_id: 2, value_id: 20 });
                expect(wrapper.vm.$data.trove_cats).toStrictEqual([
                    { category_id: 1, value_id: 10 },
                    { category_id: 2, value_id: 20 },
                ]);

                EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 100 });
                expect(wrapper.vm.$data.trove_cats).toStrictEqual([
                    { category_id: 1, value_id: 100 },
                    { category_id: 2, value_id: 20 },
                ]);
            });
        });

        it(`creates the new project and redirect user on his own personal dashboard`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                description: "",
                categories: [],
                xml_template_name: "scrum",
                fields: [],
                allow_restricted: false,
            };

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "public";

            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it(`create the new private project`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "private";

            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: false,
                description: "",
                allow_restricted: true,
                categories: [],
                xml_template_name: "scrum",
                fields: [],
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it(`creates the new private without restricted project`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "private-wo-restr";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: false,
                description: "",
                allow_restricted: false,
                categories: [],
                xml_template_name: "scrum",
                fields: [],
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it(`creates the new public restricted project`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "public";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                allow_restricted: false,
                description: "",
                categories: [],
                xml_template_name: "scrum",
                fields: [],
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it(`creates the new public including restricted restricted project`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "unrestricted";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                allow_restricted: true,
                description: "",
                categories: [],
                xml_template_name: "scrum",
                fields: [],
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it(`Redirects user on waiting for validation when project needs a site administrator approval`, async () => {
            factory.vm.$store.state.configuration.is_project_approval_required = true;
            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "private";

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");

            await factory.vm.$nextTick();

            expect(factory.vm.$route.name).toBe("approval");
        });
    });

    describe("User can choose visibility and restricted users are NOT allowed -", () => {
        beforeEach(async () => {
            const configuration_state: ConfigurationState = {
                are_restricted_users_allowed: false,
                can_user_choose_project_visibility: true,
            } as ConfigurationState;

            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            store = createStoreMock({
                state: {
                    selected_tuleap_template: {
                        title: "string",
                        description: "string",
                        id: "scrum",
                        glyph: "string",
                        is_built_in: true,
                    },
                    configuration: configuration_state,
                },
                getters,
            });

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });
        });

        it(`creates the new private without restricted project`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "private-wo-restr";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: false,
                description: "",
                allow_restricted: false,
                categories: [],
                xml_template_name: "scrum",
                fields: [],
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it(`creates the new public restricted project`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            factory.vm.$store.state.configuration.are_restricted_users_allowed = true;
            factory.vm.$data.selected_visibility = "public";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                allow_restricted: false,
                description: "",
                categories: [],
                xml_template_name: "scrum",
                fields: [],
            };

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });
    });

    describe("User can Not choose visibility -", () => {
        beforeEach(async () => {
            const configuration_state: ConfigurationState = {
                are_restricted_users_allowed: false,
                can_user_choose_project_visibility: false,
            } as ConfigurationState;

            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            store = createStoreMock({
                state: {
                    selected_tuleap_template: {
                        title: "string",
                        description: "string",
                        id: "scrum",
                        glyph: "string",
                        is_built_in: true,
                    },
                    configuration: configuration_state,
                },
                getters,
            });

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });
        });

        it("Does not display privacy list", () => {
            expect(
                factory.find("[data-test=project-information-input-privacy-list]").exists(),
            ).toBe(false);
        });
    });

    describe("Field list update -", () => {
        let factory: Wrapper<ProjectInformation>;
        beforeEach(async () => {
            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: {},
                        },
                        getters,
                    }),
                },
            });
        });
        it("build the field list object", () => {
            const wrapper = factory;
            expect(wrapper.vm.$data.field_list).toStrictEqual([]);

            EventBus.$emit("update-field-list", { field_id: 1, value: "test value" });
            expect(wrapper.vm.$data.field_list).toStrictEqual([
                { field_id: 1, value: "test value" },
            ]);

            EventBus.$emit("update-field-list", { field_id: 2, value: "other value" });
            expect(wrapper.vm.$data.field_list).toStrictEqual([
                { field_id: 1, value: "test value" },
                { field_id: 2, value: "other value" },
            ]);

            EventBus.$emit("update-field-list", { field_id: 1, value: "updated value" });
            expect(wrapper.vm.$data.field_list).toStrictEqual([
                { field_id: 1, value: "updated value" },
                { field_id: 2, value: "other value" },
            ]);
        });
    });
    describe("Build the project properties object -", () => {
        it(`Build the properties according to the scrum built in template
            and redirect user on the project dashboard
            with the name of the xml_template_name so that the success modal can be customised`, async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            const configuration_state: ConfigurationState = {
                company_name: "",
            } as ConfigurationState;

            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            store = createStoreMock({
                state: {
                    selected_tuleap_template: {
                        title: "string",
                        description: "string",
                        id: "scrum",
                        glyph: "string",
                        is_built_in: true,
                    } as TemplateData,
                    configuration: configuration_state,
                },
                getters,
            });

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });
            factory.vm.$data.selected_visibility = "unrestricted";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                description: "",
                categories: [],
                xml_template_name: "scrum",
                fields: [],
                allow_restricted: true,
            } as ProjectProperties;

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
            );
        });

        it("Build the properties according to the selected company template", async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();
            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            store = createStoreMock({
                state: {
                    selected_tuleap_template: null,
                    selected_company_template: {
                        title: "Company Template",
                        description: "desc",
                        id: "150",
                        glyph: "string",
                        is_built_in: true,
                    } as TemplateData,
                    configuration: {},
                },
                getters,
            });

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });
            factory.vm.$data.selected_visibility = "unrestricted";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                description: "",
                categories: [],
                template_id: 150,
                fields: [],
                allow_restricted: true,
            } as ProjectProperties;

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true",
            );
        });
        it("Build the properties according to the project template 100", async () => {
            const redirect_to_url = jest
                .spyOn(location_helper, "redirectToUrl")
                .mockImplementation();

            configuration_state = {
                are_restricted_users_allowed: true,
                can_user_choose_project_visibility: true,
            } as ConfigurationState;

            const getters = {
                has_error: false,
                is_template_selected: true,
            };

            store = createStoreMock({
                state: {
                    selected_tuleap_template: {
                        title: "Default Site Template",
                        description: "The default Tuleap template",
                        id: "100",
                        glyph: "string",
                        is_built_in: true,
                    } as TemplateData,
                    configuration: configuration_state,
                },
                getters,
            });

            factory = shallowMount(ProjectInformation, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
                router,
            });
            factory.vm.$data.selected_visibility = "unrestricted";
            factory.vm.$data.name_properties = {
                slugified_name: "this-is-a-test",
                name: "this is a test",
            };

            const expected_project_properties = {
                shortname: "this-is-a-test",
                label: "this is a test",
                is_public: true,
                description: "",
                categories: [],
                template_id: 100,
                fields: [],
                allow_restricted: true,
            } as ProjectProperties;

            factory.get("[data-test=project-registration-form]").trigger("submit.prevent");
            expect(store.dispatch).toHaveBeenCalledWith(
                "createProject",
                expected_project_properties,
            );

            await factory.vm.$nextTick();

            expect(redirect_to_url).toHaveBeenCalledWith(
                "/projects/this-is-a-test/?should-display-created-project-modal=true",
            );
        });
    });
});

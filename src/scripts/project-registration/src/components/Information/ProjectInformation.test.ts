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
import type { ConfigurationState } from "../../store/configuration";

describe("ProjectInformation -", () => {
    let router: VueRouter,
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
    });

    async function getWrapper(): Promise<Wrapper<ProjectInformation>> {
        return shallowMount(ProjectInformation, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
            router,
        });
    }

    it("Spawns the ProjectInformation component", async () => {
        const wrapper = await getWrapper();

        wrapper.vm.$store.getters.has_error = false;

        expect(wrapper.findComponent(ProjectInformationSvg).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectInformationFooter).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectName).exists()).toBe(true);

        expect(wrapper.find("[data-test=project-creation-failed]").exists()).toBe(false);
    });

    it("Displays error message", async () => {
        const wrapper = await getWrapper();

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

        const wrapper = await getWrapper();

        expect(wrapper.vm.$route.name).toBe("template");
    });

    it("build the trovecat object", async () => {
        const wrapper = await getWrapper();
        expect(wrapper.vm.$data.trove_cats).toStrictEqual([]);

        EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 10 });
        expect(wrapper.vm.$data.trove_cats).toStrictEqual([{ category_id: 1, value_id: 10 }]);

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

    it(`creates the new project and redirect user on his own personal dashboard`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        const wrapper = await getWrapper();

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

        wrapper.vm.$store.state.configuration.are_restricted_users_allowed = true;
        wrapper.vm.$data.selected_visibility = "public";

        wrapper.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test",
        };

        wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("createProject", expected_project_properties);

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
        );
    });

    it(`Redirects user on waiting for validation when project needs a site administrator approval`, async () => {
        const wrapper = await getWrapper();
        wrapper.vm.$store.state.configuration.is_project_approval_required = true;
        wrapper.vm.$store.state.configuration.are_restricted_users_allowed = true;
        wrapper.vm.$data.selected_visibility = "private";
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$route.name).toBe("approval");
    });

    it("build the field list object", async () => {
        const wrapper = await getWrapper();
        expect(wrapper.vm.$data.field_list).toStrictEqual([]);

        EventBus.$emit("update-field-list", { field_id: 1, value: "test value" });
        expect(wrapper.vm.$data.field_list).toStrictEqual([{ field_id: 1, value: "test value" }]);

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

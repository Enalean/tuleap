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
import VueRouter from "vue-router";
import * as location_helper from "../../helpers/location-helper";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import EventBus from "../../helpers/event-bus";
import { useStore } from "../../stores/root";
import type { ProjectArchiveTemplateData, TemplateData } from "../../type";

let has_error = false;
let are_restricted_users_allowed = false;
let is_project_approval_required = false;
let is_template_selected = true;
const create_project_mock = jest.fn();
const create_project_from_archive_mock = jest.fn();
describe("ProjectInformation -", () => {
    let router: VueRouter;
    beforeEach(() => {
        has_error = false;
        are_restricted_users_allowed = false;
        is_project_approval_required = false;
        is_template_selected = true;
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
                {
                    path: "/from-archive-creation",
                    name: "from-archive-creation",
                },
            ],
        });
    });

    async function getWrapper(
        selected_company_template: TemplateData | ProjectArchiveTemplateData | null = null,
    ): Promise<Wrapper<ProjectInformation>> {
        const useStore = defineStore("root", {
            state: () => ({
                is_template_selected,
                is_project_approval_required,
                are_restricted_users_allowed,
                can_user_choose_project_visibility: true,
                selected_tuleap_template: {
                    title: "string",
                    description: "string",
                    id: "scrum",
                    glyph: "string",
                    is_built_in: true,
                },
                selected_company_template,
            }),
            getters: {
                has_error: () => has_error,
            },
            actions: {
                createProject: create_project_mock,
                createProjectFromArchive: create_project_from_archive_mock,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectInformation, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
            router,
        });
    }

    it("Spawns the ProjectInformation component", async () => {
        const wrapper = await getWrapper();

        has_error = false;

        expect(wrapper.findComponent(ProjectInformationSvg).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectInformationFooter).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectName).exists()).toBe(true);

        expect(wrapper.find("[data-test=project-creation-failed]").exists()).toBe(false);
    });

    it("Displays error message", async () => {
        has_error = true;

        const wrapper = await getWrapper();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ProjectInformationSvg).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectInformationFooter).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectInformationInputPrivacyList).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectName).exists()).toBe(true);

        expect(wrapper.find("[data-test=project-creation-failed]").exists()).toBe(true);
    });

    it("redirects user on /new when he does not have all needed information to start his project creation", async () => {
        is_template_selected = false;
        const wrapper = await getWrapper();
        expect(wrapper.vm.$route.name).toBe("template");
    });

    it("build the trovecat object", async () => {
        const wrapper = await getWrapper();
        expect(wrapper.vm.$data.trove_cats).toStrictEqual([]);

        EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 10 });
        wrapper.vm.$nextTick();
        expect(wrapper.vm.$data.trove_cats).toStrictEqual([{ category_id: 1, value_id: 10 }]);

        EventBus.$emit("choose-trove-cat", { category_id: 2, value_id: 20 });
        wrapper.vm.$nextTick();
        expect(wrapper.vm.$data.trove_cats).toStrictEqual([
            { category_id: 1, value_id: 10 },
            { category_id: 2, value_id: 20 },
        ]);

        EventBus.$emit("choose-trove-cat", { category_id: 1, value_id: 100 });
        wrapper.vm.$nextTick();
        expect(wrapper.vm.$data.trove_cats).toStrictEqual([
            { category_id: 1, value_id: 100 },
            { category_id: 2, value_id: 20 },
        ]);
    });

    it(`creates the new project and redirect user on his own personal dashboard`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        const wrapper = await getWrapper();
        const store = useStore();

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

        are_restricted_users_allowed = true;
        wrapper.vm.$data.selected_visibility = "public";

        wrapper.vm.$data.name_properties = {
            slugified_name: "this-is-a-test",
            name: "this is a test",
        };

        wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(store.createProject).toHaveBeenCalledWith(expected_project_properties);
        expect(store.createProjectFromArchive).not.toHaveBeenCalled();

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
        );
    });

    it(`Redirects user on waiting for validation when project needs a site administrator approval`, async () => {
        is_project_approval_required = true;
        are_restricted_users_allowed = true;
        const wrapper = await getWrapper();
        wrapper.vm.$data.selected_visibility = "private";
        await wrapper.vm.$nextTick();

        wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$route.name).toBe("approval");
    });

    it(`Create a new project when project is created from an archive`, async () => {
        const selected_company_template = {
            title: "my title",
            description: "string",
            id: "from_project_archive",
            glyph: "string",
            is_built_in: false,
        };
        const wrapper = await getWrapper(selected_company_template);
        const store = useStore();
        wrapper.vm.$data.selected_visibility = "private";
        await wrapper.vm.$nextTick();

        wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");

        expect(store.createProjectFromArchive).toHaveBeenCalled();
        expect(store.createProject).not.toHaveBeenCalled();
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

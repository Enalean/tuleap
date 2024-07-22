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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ProjectInformation from "./ProjectInformation.vue";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import ProjectName from "./Input/ProjectName.vue";
import ProjectInformationInputPrivacyList from "./Input/ProjectInformationInputPrivacyList.vue";
import type { Router } from "vue-router";
import * as location_helper from "../../helpers/location-helper";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { useStore } from "../../stores/root";
import type { ProjectArchiveTemplateData, TemplateData } from "../../type";
import * as router from "../../helpers/use-router";
import { ACCESS_PRIVATE, ACCESS_PUBLIC } from "../../constant";
import emitter from "../../helpers/emitter";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

jest.useFakeTimers();

let has_error = false;
let are_restricted_users_allowed = false;
let is_project_approval_required = false;
let is_template_selected = true;
const create_project_mock = jest.fn();
const create_project_from_archive_mock = jest.fn();
describe("ProjectInformation -", () => {
    let push_route_spy: jest.Mock;
    beforeEach(() => {
        has_error = false;
        are_restricted_users_allowed = false;
        is_project_approval_required = false;
        is_template_selected = true;
        push_route_spy = jest.fn();

        jest.spyOn(router, "useRouter").mockImplementation(() => {
            return { push: push_route_spy } as unknown as Router;
        });
    });

    function getWrapper(
        selected_company_template: TemplateData | ProjectArchiveTemplateData | null = null,
    ): VueWrapper {
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
            global: {
                ...getGlobalTestOptions(pinia),
                stubs: ["router-link"],
            },
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

        expect(wrapper.findComponent(ProjectInformationSvg).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectInformationFooter).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectInformationInputPrivacyList).exists()).toBe(true);
        expect(wrapper.findComponent(ProjectName).exists()).toBe(true);

        expect(wrapper.find("[data-test=project-creation-failed]").exists()).toBe(true);
    });

    it("redirects user on /new when he does not have all needed information to start his project creation", async () => {
        is_template_selected = false;
        await getWrapper();
        expect(push_route_spy).toHaveBeenCalledWith("new");
    });

    it(`creates the new project and redirect user on his own personal dashboard`, async () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();

        const wrapper = await getWrapper();
        const store = useStore();

        emitter.emit("choose-trove-cat", { category_id: "1", value_id: "10" });
        wrapper.vm.$nextTick();
        emitter.emit("choose-trove-cat", { category_id: "2", value_id: "20" });
        wrapper.vm.$nextTick();

        emitter.emit("update-field-list", { field_id: "1", value: "test value" });
        wrapper.vm.$nextTick();
        emitter.emit("update-field-list", { field_id: "2", value: "other value" });
        wrapper.vm.$nextTick();
        emitter.emit("update-project-visibility", { new_visibility: ACCESS_PUBLIC });
        wrapper.vm.$nextTick();
        emitter.emit("update-project-name", {
            slugified_name: "this-is-a-test",
            name: "this is a test",
        });
        wrapper.vm.$nextTick();

        const expected_project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            description: "",
            categories: [
                { category_id: "1", value_id: "10" },
                { category_id: "2", value_id: "20" },
            ],
            xml_template_name: "scrum",
            fields: [
                { field_id: "1", value: "test value" },
                { field_id: "2", value: "other value" },
            ],
            allow_restricted: false,
        };

        are_restricted_users_allowed = true;

        await wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");

        expect(store.createProject).toHaveBeenCalledWith(expected_project_properties);
        expect(store.createProjectFromArchive).not.toHaveBeenCalled();

        await jest.runOnlyPendingTimersAsync();

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/projects/this-is-a-test/?should-display-created-project-modal=true&xml-template-name=scrum",
        );
    });

    it(`Redirects user on waiting for validation when project needs a site administrator approval`, async () => {
        is_project_approval_required = true;
        are_restricted_users_allowed = true;
        const wrapper = await getWrapper();
        emitter.emit("update-project-visibility", { new_visibility: ACCESS_PRIVATE });
        wrapper.vm.$nextTick();

        await wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");

        expect(push_route_spy).toHaveBeenCalledWith("approval");
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
        emitter.emit("update-project-visibility", { new_visibility: ACCESS_PRIVATE });
        wrapper.vm.$nextTick();

        await wrapper.get("[data-test=project-registration-form]").trigger("submit.prevent");

        expect(store.createProjectFromArchive).toHaveBeenCalled();
        expect(store.createProject).not.toHaveBeenCalled();
    });
});

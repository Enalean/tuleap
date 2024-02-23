/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { createProjectRegistrationLocalVue } from "../../../../helpers/local-vue-for-tests";
import UserProjectList from "./UserProjectList.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { TemplateData } from "../../../../type";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { useStore } from "../../../../stores/root";

describe("UserProjectList", () => {
    let wrapper: Wrapper<UserProjectList>,
        project_list: Array<TemplateData>,
        project_a: TemplateData,
        selectedCompanyTemplate: TemplateData | null;
    const set_selected_template_mock = jest.fn();

    beforeEach(() => {
        project_a = {
            title: "My A project",
            description: "",
            id: "101",
            glyph: "",
            is_built_in: false,
        };

        const project_b: TemplateData = {
            title: "My B project",
            description: "",
            id: "102",
            glyph: "",
            is_built_in: false,
        };

        project_list = [project_a, project_b];
    });

    async function getWrapper(): Promise<Wrapper<UserProjectList>> {
        const useStore = defineStore("root", {
            actions: {
                setSelectedTemplate: () => set_selected_template_mock,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(UserProjectList, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
            propsData: { projectList: project_list, selectedCompanyTemplate },
        });
    }

    it("Spawns the UserProjectList component", async () => {
        selectedCompanyTemplate = null;
        wrapper = await getWrapper();

        expect(wrapper).toMatchSnapshot();
    });

    it("Should select the previously selected project by default when one has been previously selected", async () => {
        selectedCompanyTemplate = project_a;
        wrapper = await getWrapper();

        expect(wrapper.vm.$data.selected_project).toBe(project_a);
    });

    it("Should reset the selection when the currently selected template has been reset", async () => {
        selectedCompanyTemplate = project_a;
        wrapper = await getWrapper();

        wrapper.vm.$data.selected_project = null;

        expect(wrapper.vm.$data.selected_project).toBeNull();
    });

    it(`user can select a project`, async () => {
        selectedCompanyTemplate = null;
        wrapper = await getWrapper();
        const store = useStore();

        wrapper.vm.$data.selected_project = project_a;
        await wrapper.vm.$nextTick();

        wrapper.get("[data-test=from-another-project]").trigger("change");
        await wrapper.vm.$nextTick();

        expect(store.setSelectedTemplate).toHaveBeenCalled();
    });

    it(`displays a message when user is not administrator of any project`, async () => {
        project_list = [];
        selectedCompanyTemplate = project_a;
        wrapper = await getWrapper();

        expect(wrapper.find("[data-test=from-another-project]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=no-project-list]").exists()).toBeTruthy();
    });
});

/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import UserProjectList from "./UserProjectList.vue";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { TemplateData } from "../../../type";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import { State } from "../../../store/type";
import { Store } from "vuex-mock-store";

describe("UserProjectList", () => {
    let wrapper: Wrapper<UserProjectList>,
        project_list: Array<TemplateData>,
        project_a: TemplateData,
        store: Store;

    beforeEach(() => {
        const state: State = {} as State;

        const store_options = {
            state,
        };
        store = createStoreMock(store_options);

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

    it("Spawns the UserProjectList component", async () => {
        wrapper = shallowMount(UserProjectList, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: { projectList: project_list },
            mocks: { $store: store },
        });

        expect(wrapper).toMatchSnapshot();
    });

    it(`user can select a project`, async () => {
        wrapper = shallowMount(UserProjectList, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: { projectList: project_list },
            mocks: { $store: store },
        });

        wrapper.vm.$data.selected_project = project_a;
        await wrapper.vm.$nextTick();

        wrapper.get("[data-test=from-another-project]").trigger("change");
        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("setSelectedTemplate", project_a);
    });

    it(`displays a message when user is not administrator of any project`, async () => {
        wrapper = shallowMount(UserProjectList, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: { projectList: [] },
            mocks: { $store: store },
        });

        expect(wrapper.find("[data-test=from-another-project]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=no-project-list]").exists()).toBeTruthy();
    });
});

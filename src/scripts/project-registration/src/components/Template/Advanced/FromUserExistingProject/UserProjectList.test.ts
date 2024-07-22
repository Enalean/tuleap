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

import UserProjectList from "./UserProjectList.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { TemplateData } from "../../../../type";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { selectOrThrow } from "@tuleap/dom";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("UserProjectList", () => {
    let wrapper: VueWrapper,
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

    function getWrapper(): VueWrapper {
        const useStore = defineStore("root", {
            actions: {
                setSelectedTemplate: () => set_selected_template_mock,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(UserProjectList, {
            global: {
                ...getGlobalTestOptions(pinia),
            },
            propsData: { projectList: project_list, selectedCompanyTemplate },
        });
    }

    it("Spawns the UserProjectList component", () => {
        selectedCompanyTemplate = null;
        wrapper = getWrapper();

        expect(wrapper).toMatchSnapshot();
    });

    it("Should select the previously selected project by default when one has been previously selected", () => {
        selectedCompanyTemplate = project_a;
        wrapper = getWrapper();

        const option = selectOrThrow(
            wrapper.element,
            "[data-test=select-project-101]",
            HTMLOptionElement,
        );

        expect(option.selected).toBe(true);
    });

    it(`displays a message when user is not administrator of any project`, () => {
        project_list = [];
        selectedCompanyTemplate = project_a;
        wrapper = getWrapper();

        expect(wrapper.find("[data-test=from-another-project]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=no-project-list]").exists()).toBeTruthy();
    });
});

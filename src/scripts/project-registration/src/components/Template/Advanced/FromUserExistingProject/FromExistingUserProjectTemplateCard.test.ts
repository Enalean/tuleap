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
import FromExistingUserProjectTemplateCard from "./FromExistingUserProjectTemplateCard.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { TemplateData } from "../../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import UserProjectList from "./UserProjectList.vue";

describe("FromExistingUserProjectTemplateCard", () => {
    let projects_user_is_admin_of: TemplateData[], alm2: TemplateData;

    async function getWrapper(
        selected_company_template: null | TemplateData = null,
        projects_user_is_admin_of: TemplateData[] = [],
    ): Promise<Wrapper<FromExistingUserProjectTemplateCard>> {
        return shallowMount(FromExistingUserProjectTemplateCard, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        selected_company_template,
                        projects_user_is_admin_of,
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        alm2 = {
            title: "alm - 2",
            description: "ALM",
            id: "alm2",
            glyph: "<svg></svg>",
            is_built_in: false,
        };

        projects_user_is_admin_of = [
            {
                title: "alm - 1",
                description: "ALM",
                id: "alm1",
                glyph: "<svg></svg>",
                is_built_in: false,
            },
            alm2,
        ];
    });

    it("Display the description by default", async () => {
        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Display spinner when project list is loading`, async () => {
        const wrapper = await getWrapper();

        wrapper.vm.$data.is_loading_project_list = true;
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Does not display spinner if an error happened`, async () => {
        const wrapper = await getWrapper();

        wrapper.vm.$data.is_loading_project_list = true;
        wrapper.vm.$data.has_error = true;
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Display error if something went wrong`, async () => {
        const wrapper = await getWrapper();

        wrapper.vm.$data.has_error = true;
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Displays the project list if user has already loaded it`, async () => {
        const wrapper = await getWrapper(null, projects_user_is_admin_of);

        wrapper.get("[data-test=project-registration-card-label").trigger("click");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalledWith("loadUserProjects");

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeTruthy();
    });

    it(`Loads the project list if user has not loaded it yet`, async () => {
        const wrapper = await getWrapper();

        wrapper.get("[data-test=project-registration-card-label").trigger("click");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("loadUserProjects");
    });

    it("should display the card as checked and the current selection if a project has been selected previously", async () => {
        const wrapper = await getWrapper(alm2, projects_user_is_admin_of);
        const input = wrapper.find("[data-test=selected-template-input]").element;

        if (!(input instanceof HTMLInputElement)) {
            throw new Error("[data-test=selected-template-input] is not a HTMLInputElement");
        }

        expect(input.checked).toBe(true);
        expect(wrapper.getComponent(UserProjectList).exists()).toBe(true);
    });
});

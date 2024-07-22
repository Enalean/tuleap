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

import FromExistingUserProjectTemplateCard from "./FromExistingUserProjectTemplateCard.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { TemplateData } from "../../../../type";
import UserProjectList from "./UserProjectList.vue";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { useStore } from "../../../../stores/root";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("FromExistingUserProjectTemplateCard", () => {
    let projects_user_is_admin_of: TemplateData[], alm2: TemplateData;
    const loadUserProjects = jest.fn().mockImplementation(() => Promise.reject());

    function getWrapper(
        selected_company_template: null | TemplateData = null,
        projects_user_is_admin_of: TemplateData[] = [],
        is_option_selected: boolean = false,
    ): VueWrapper {
        const useStore = defineStore("root", {
            state: () => ({
                selected_company_template,
                projects_user_is_admin_of,
            }),
            actions: {
                loadUserProjects: loadUserProjects,
            },
            getters: {
                is_advanced_option_selected: () => (): boolean => {
                    return is_option_selected;
                },
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(FromExistingUserProjectTemplateCard, {
            global: {
                ...getGlobalTestOptions(pinia),
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

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBe(true);
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBe(false);
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBe(false);
    });

    describe("loadProjects() -", () => {
        it(`loads the project the user's projects and display the spinner during the loading`, async () => {
            const wrapper = getWrapper(null, [], false);

            expect(wrapper.find("[data-test=user-project-description]").exists()).toBe(true);
            expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBe(false);
            expect(wrapper.find("[data-test=user-project-error]").exists()).toBe(false);

            wrapper.find("[data-test=project-registration-card-label]").trigger("click");

            await nextTick();

            expect(wrapper.find("[data-test=user-project-description]").exists()).toBe(false);
            expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBe(true);
            expect(wrapper.find("[data-test=user-project-error]").exists()).toBe(false);

            await nextTick();

            expect(wrapper.find("[data-test=user-project-description]").exists()).toBe(true);
            expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBe(false);
            expect(wrapper.find("[data-test=user-project-error]").exists()).toBe(false);
        });
    });

    it(`Loads the project list if user has not loaded it yet`, async () => {
        const wrapper = await getWrapper();
        const store = useStore();

        await wrapper.get("[data-test=project-registration-card-label]").trigger("click");

        expect(store.loadUserProjects).toHaveBeenCalled();
    });

    it("should display the card as checked if the card is currently selected", async () => {
        const wrapper = await getWrapper(alm2, projects_user_is_admin_of, true);
        const input = wrapper.find<HTMLInputElement>("[data-test=selected-template-input]").element;

        expect(input.checked).toBe(true);
        expect(wrapper.findComponent(UserProjectList).exists()).toBe(true);
    });
});

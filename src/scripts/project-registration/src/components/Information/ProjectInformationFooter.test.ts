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
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { jest } from "@jest/globals";
import { useStore } from "../../stores/root";

describe("ProjectInformationFooter", () => {
    const resetProjectCreationError = jest.fn();
    let is_creating_project = false;

    async function getWrapper(): Promise<Wrapper<Vue, Element>> {
        const useStore = defineStore("root", {
            state: () => ({
                is_creating_project,
            }),
            getters: {
                has_error: () => false,
            },
            actions: {
                resetProjectCreationError,
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectInformationFooter, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
        });
    }

    it(`reset the project creation error when the 'Back' button is clicked`, async () => {
        const wrapper = await getWrapper();
        const store = useStore();
        wrapper.get("[data-test=project-registration-back-button]").trigger("click");
        await wrapper.vm.$nextTick();
        expect(store.resetProjectCreationError).toHaveBeenCalled();
    });

    it(`Displays spinner when project is creating`, async () => {
        is_creating_project = true;
        const wrapper = await getWrapper();
        await wrapper.vm.$nextTick();

        expect(wrapper.get("[data-test=project-submission-icon]").classes()).toEqual([
            "fa",
            "tlp-button-icon-right",
            "fa-spin",
            "fa-circle-o-notch",
        ]);
    });

    it(`Does not display spinner by default`, async () => {
        is_creating_project = false;
        const wrapper = await getWrapper();

        expect(wrapper.get("[data-test=project-submission-icon]").classes()).toEqual([
            "fa",
            "tlp-button-icon-right",
            "fa-arrow-circle-o-right",
        ]);
    });
});

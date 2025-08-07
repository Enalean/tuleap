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
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { jest } from "@jest/globals";
import { useStore } from "../../stores/root";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("ProjectInformationFooter", () => {
    const resetProjectCreationError = jest.fn();
    let is_creating_project = false;

    function getWrapper(has_error: boolean = false): VueWrapper {
        const useStore = defineStore("root", {
            state: () => ({
                is_creating_project,
            }),
            getters: {
                has_error: () => has_error,
            },
            actions: {
                resetProjectCreationError,
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectInformationFooter, {
            global: {
                ...getGlobalTestOptions(pinia),
                stubs: ["router-link"],
            },
        });
    }

    it(`reset the project creation error when the 'Back' button is clicked`, async () => {
        const wrapper = getWrapper();
        const store = useStore();
        await wrapper.get("[data-test=project-registration-back-button]").trigger("click");
        expect(store.resetProjectCreationError).toHaveBeenCalled();
    });

    it(`Displays spinner when project is creating`, () => {
        is_creating_project = true;
        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=project-submission-icon]").classes()).toStrictEqual([
            "tlp-button-icon",
            "fa-solid",
            "fa-spin",
            "fa-circle-notch",
        ]);
    });

    it(`Does not display spinner by default`, () => {
        is_creating_project = false;
        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=project-submission-icon]").classes()).toStrictEqual([
            "tlp-button-icon",
            "fa-regular",
            "fa-circle-right",
        ]);
    });

    it(`disable the submission button when the project is being created and there is no error displayed`, () => {
        is_creating_project = true;
        const has_error = false;
        const wrapper = getWrapper(has_error);

        const submit_button = wrapper.get<HTMLButtonElement>(
            "[data-test=project-registration-next-button]",
        );
        expect(submit_button.element.disabled).toBe(true);
    });
});

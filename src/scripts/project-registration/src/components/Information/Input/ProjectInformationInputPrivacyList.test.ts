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
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import ProjectInformationInputPrivacyList from "./ProjectInformationInputPrivacyList.vue";
import * as list_picker from "@tuleap/list-picker";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";

async function getWrapper(
    project_default_visibility: string,
    are_restricted_users_allowed: boolean,
): Promise<Wrapper<Vue, Element>> {
    const useStore = defineStore("root", {
        state: () => ({
            project_default_visibility,
            are_restricted_users_allowed,
        }),
    });

    const pinia = createTestingPinia();
    useStore(pinia);

    const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
        localVue: await createProjectRegistrationLocalVue(),
        pinia,
    });
    return wrapper;
}

describe("ProjectInformationInputPrivacyList", () => {
    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockReturnValue({
            destroy: () => {
                // Nothing to do since we did not really create something
            },
        });
    });

    describe("Displayed options depends on platform configuration -", () => {
        it("Displays only public and private when platform does not allow restricted", async () => {
            const wrapper = await getWrapper("private", false);
            expect(wrapper.find("[data-test=unrestricted]").exists()).toBe(false);
            expect(wrapper.find("[data-test=private]").exists()).toBe(true);
            expect(wrapper.find("[data-test=private-wo-restr]").exists()).toBe(false);
            expect(wrapper.find("[data-test=public]").exists()).toBe(true);
        });

        it("Displays all options when restricted are allowed", async () => {
            const wrapper = await getWrapper("private", true);
            expect(wrapper.find("[data-test=private]").exists()).toBe(true);
            expect(wrapper.find("[data-test=private-wo-restr]").exists()).toBe(true);
            expect(wrapper.find("[data-test=unrestricted]").exists()).toBe(true);
            expect(wrapper.find("[data-test=public]").exists()).toBe(true);
        });
    });
});

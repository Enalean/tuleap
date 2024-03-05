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
import FromProjectArchiveCard from "./FromProjectArchiveCard.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import type Vue from "vue";

describe("FromProjectArchiveCard", () => {
    async function getWrapper(
        is_advanced_option_selected: boolean = false,
    ): Promise<Wrapper<Vue, Element>> {
        const useStore = defineStore("root", {
            getters: {
                is_advanced_option_selected: () => (): boolean => {
                    return is_advanced_option_selected;
                },
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(FromProjectArchiveCard, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
        });
    }

    it("Display the description by default", async () => {
        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=archive-project-description]").exists()).toBe(true);
        expect(wrapper.find("[data-test=archive-project-file-input]").exists()).toBe(false);
    });

    it(`Display the archive input if the card is selected`, async () => {
        const wrapper = await getWrapper(true);

        expect(wrapper.find("[data-test=archive-project-description]").exists()).toBe(false);
        expect(wrapper.find("[data-test=archive-project-file-input]").exists()).toBe(true);
    });
});

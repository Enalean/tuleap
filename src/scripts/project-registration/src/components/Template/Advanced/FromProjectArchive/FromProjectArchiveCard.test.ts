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

import FromProjectArchiveCard from "./FromProjectArchiveCard.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("FromProjectArchiveCard", () => {
    function getWrapper(is_advanced_option_selected: boolean = false): VueWrapper {
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
            global: {
                ...getGlobalTestOptions(pinia),
            },
        });
    }

    it("Display the description by default", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=archive-project-description]").exists()).toBe(true);
        expect(wrapper.find("[data-test=archive-project-file-input]").exists()).toBe(false);
    });

    it(`Display the archive input if the card is selected`, () => {
        const wrapper = getWrapper(true);

        expect(wrapper.find("[data-test=archive-project-description]").exists()).toBe(false);
        expect(wrapper.find("[data-test=archive-project-file-input]").exists()).toBe(true);
    });
});

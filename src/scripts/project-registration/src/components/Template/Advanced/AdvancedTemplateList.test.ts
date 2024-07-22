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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import AdvancedTemplateList from "./AdvancedTemplateList.vue";
import FromProjectArchiveCard from "./FromProjectArchive/FromProjectArchiveCard.vue";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("AdvancedTemplateList", () => {
    function getWrapper(can_create_from_project_file: boolean): VueWrapper {
        const useStore = defineStore("root", {
            state: () => ({
                can_create_from_project_file,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(AdvancedTemplateList, {
            global: {
                ...getGlobalTestOptions(pinia),
                stubs: ["router-link"],
            },
        });
    }
    it(`Display the "from project file" card when the feature flag is enabled`, () => {
        const wrapper = getWrapper(true);
        expect(wrapper.findComponent(FromProjectArchiveCard).isVisible()).toBe(true);
    });

    it(`Does not display "the from project file" card when the feature flag is disabled`, () => {
        const wrapper = getWrapper(false);
        expect(wrapper.findComponent(FromProjectArchiveCard).exists()).toBe(false);
    });
});

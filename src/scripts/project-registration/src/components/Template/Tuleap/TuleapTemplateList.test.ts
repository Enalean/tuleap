/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TemplateCardContent from "../TemplateCard.vue";
import TuleapTemplateList from "./TuleapTemplateList.vue";
import type { TemplateData } from "../../../type";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("TuleapTemplateList", () => {
    let wrapper: VueWrapper;

    function createWrapper(tuleap_templates: TemplateData[]): VueWrapper {
        const useStore = defineStore("root", {
            state: () => ({
                tuleap_templates,
            }),
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TuleapTemplateList, {
            global: {
                ...getGlobalTestOptions(pinia),
            },
        });
    }

    it(`spawns the component and sub component`, async () => {
        const tuleap_templates = [
            {
                title: "scrum",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true,
            } as TemplateData,
            {
                title: "kanban",
                description: "kanban desc",
                id: "kanban",
                glyph: "<svg>kanban</svg>",
                is_built_in: true,
            } as TemplateData,
        ];

        wrapper = await createWrapper(tuleap_templates);

        expect(wrapper.findComponent(TemplateCardContent).exists()).toBe(true);
        expect(wrapper.findAllComponents(TemplateCardContent)).toHaveLength(2);
    });

    it(`does not display anything if no tuleap templates are found`, async () => {
        const tuleap_templates: TemplateData[] = [];

        wrapper = await createWrapper(tuleap_templates);

        expect(wrapper.find("[data-test=tuleap-templates-section]").exists()).toBe(false);
    });
});

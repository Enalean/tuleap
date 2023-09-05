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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import TemplateCardContent from "../TemplateCard.vue";
import TuleapTemplateList from "./TuleapTemplateList.vue";
import type { TemplateData } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";

describe("TuleapTemplateList", () => {
    let local_vue = createLocalVue();
    let wrapper: Wrapper<TuleapTemplateList>;

    async function createWrapper(
        tuleap_templates: TemplateData[],
    ): Promise<Wrapper<TuleapTemplateList>> {
        const configuration_state: ConfigurationState = {
            tuleap_templates: tuleap_templates,
        } as ConfigurationState;

        local_vue = await createProjectRegistrationLocalVue();

        return shallowMount(TuleapTemplateList, {
            localVue: local_vue,
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: configuration_state,
                    },
                }),
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

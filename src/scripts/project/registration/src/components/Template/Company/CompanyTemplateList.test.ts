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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import TemplateCardContent from "../TemplateCard.vue";
import CompanyTemplateList from "./CompanyTemplateList.vue";
import type { RootState } from "../../../store/type";
import type { ConfigurationState } from "../../../store/configuration";

describe("CompanyTemplateList", () => {
    let local_vue = createLocalVue();
    let wrapper: Wrapper<CompanyTemplateList>;

    describe("has several project templates -", () => {
        beforeEach(async () => {
            const company_templates = [
                {
                    id: "10",
                    title: "scrum",
                    description: "scrum desc",
                    glyph: "<svg></svg>",
                    is_built_in: false,
                },
                {
                    id: "11",
                    title: "kanban",
                    description: "kanban desc",
                    glyph: "<svg>kanban</svg>",
                    is_built_in: false,
                },
            ];

            const configuration_state: ConfigurationState = {
                company_templates: company_templates,
                company_name: "",
            } as ConfigurationState;

            local_vue = await createProjectRegistrationLocalVue();

            wrapper = shallowMount(CompanyTemplateList, {
                localVue: local_vue,
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });
        });

        it(`spawns the component and sub component`, () => {
            expect(wrapper.findComponent(TemplateCardContent).exists()).toBe(true);
            expect(wrapper.findAllComponents(TemplateCardContent)).toHaveLength(2);
        });
    });
});

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

import { Store } from "vuex-mock-store";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import { createLocalVue, shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import TemplateCardContent from "../TemplateCard.vue";
import CompanyTemplateList from "./CompanyTemplateList.vue";
import { State } from "../../../store/type";
import { TemplateData } from "../../../type";

describe("CompanyTemplateList", () => {
    let local_vue = createLocalVue();
    let store: Store;
    let wrapper: Wrapper<CompanyTemplateList>;

    describe("has no templates", () => {
        beforeEach(async () => {
            const company_templates: TemplateData[] = [];
            const state: State = {
                company_templates: company_templates,
                default_project_template: null,
                company_name: "",
            } as State;

            const store_options = {
                state,
            };
            store = createStoreMock(store_options);
            local_vue = await createProjectRegistrationLocalVue();

            wrapper = shallowMount(CompanyTemplateList, {
                localVue: local_vue,
                mocks: { $store: store },
            });
        });

        it(`display nothing when company hasn't defined any templates`, () => {
            expect(
                wrapper.contains("[data-test=project-registration-company-template-title]")
            ).toBe(false);
        });
    });

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

            const state: State = {
                company_templates: company_templates,
                default_project_template: null,
                company_name: "",
            } as State;

            const store_options = {
                state,
            };
            store = createStoreMock(store_options);
            local_vue = await createProjectRegistrationLocalVue();

            wrapper = shallowMount(CompanyTemplateList, {
                localVue: local_vue,
                mocks: { $store: store },
            });
        });

        it(`spawns the component and sub component`, () => {
            expect(wrapper.contains(TemplateCardContent)).toBe(true);
            expect(wrapper.findAll(TemplateCardContent)).toHaveLength(2);
        });

        it(`displays the company name if the platform name is not Tuleap`, async () => {
            wrapper.vm.$store.state.company_name = "Nichya company";
            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-company-template-title]")
                    .element.innerHTML.trim()
            ).toEqual("Nichya company templates");
        });

        it(`displays 'Custom templates' if the platform name is Tuleap`, async () => {
            wrapper.vm.$store.state.company_name = "Tuleap";
            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-company-template-title]")
                    .element.innerHTML.trim()
            ).toEqual("Custom templates");
        });
    });
});

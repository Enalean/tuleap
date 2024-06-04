/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import TemplateSelection from "./TemplateSelection.vue";
import type { ExternalTemplateData, TemplateData } from "../../type";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import TuleapCompanyTemplateList from "./Company/CompanyTemplateList.vue";
import AdvancedTemplateList from "./Advanced/AdvancedTemplateList.vue";
import CategorisedExternalTemplatesList from "./CategorisedExternalTemplates/CategorisedExternalTemplatesList.vue";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";

describe("TemplateSelection", () => {
    async function getWrapper(
        tuleap_templates: TemplateData[],
        company_templates: TemplateData[],
        external_templates: ExternalTemplateData[],
        company_name: string,
        selected_template_category: null | string = null,
    ): Promise<Wrapper<Vue, Element>> {
        const useStore = defineStore("root", {
            state: () => ({
                tuleap_templates,
                company_templates,
                company_name,
                external_templates,
                selected_template_category,
            }),
            actions: {
                resetSelectedTemplate: jest.fn(),
                setSelectedTemplateCategory: jest.fn(),
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TemplateSelection, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
        });
    }

    let tuleap_templates: TemplateData[] = [];
    let company_templates: TemplateData[] = [];
    let external_templates: ExternalTemplateData[];

    beforeEach(() => {
        tuleap_templates = [
            {
                title: "scrum",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true,
            },
        ];

        company_templates = [
            {
                id: "11",
                title: "kanban",
                description: "kanban desc",
                glyph: "<svg>kanban</svg>",
                is_built_in: false,
            },
        ];

        external_templates = [
            {
                id: "program",
                title: "SAFe - Program",
                description: "SAFe - Program",
                glyph: "<svg>SAFe</svg>",
                is_built_in: true,
                template_category: {
                    shortname: "SAFe",
                    label: "Program/Teams",
                    should_case_of_label_be_respected: true,
                },
            },
            {
                id: "dummy",
                title: "Dummy",
                description: "Dummy templates",
                glyph: "<svg>Dummy</svg>",
                is_built_in: true,
                template_category: {
                    shortname: "dummies",
                    label: "Dummies",
                    should_case_of_label_be_respected: true,
                },
            },
        ];
    });

    describe("Company templates", () => {
        it(`displays the company name if the platform name is not Tuleap`, async () => {
            const wrapper = await getWrapper(
                tuleap_templates,
                company_templates,
                external_templates,
                "ACME",
            );
            expect(
                wrapper
                    .get("[data-test=project-registration-acme-templates-tab]")
                    .element.innerHTML.trim(),
            ).toBe("ACME");
        });

        it(`displays 'Custom templates' if the platform name is Tuleap`, async () => {
            const wrapper = await getWrapper(
                tuleap_templates,
                company_templates,
                external_templates,
                "Tuleap",
            );
            expect(
                wrapper
                    .get("[data-test=project-registration-acme-templates-tab]")
                    .element.innerHTML.trim(),
            ).toBe("Custom templates");
        });

        it("should not display the tab containing the company templates when there are no company templates", async () => {
            const wrapper = await getWrapper(tuleap_templates, [], external_templates, "ACME");
            expect(
                wrapper.find("[data-test=project-registration-acme-templates-tab]").exists(),
            ).toBe(false);

            expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(false);
        });
    });

    describe("templates categories default display", () => {
        it("should display the previously selected category when there is one", async () => {
            const wrapper = await getWrapper(
                tuleap_templates,
                company_templates,
                external_templates,
                "ACME",
                "Tuleap",
            );

            expect(
                wrapper
                    .get("[data-test=project-registration-tuleap-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(true);
        });

        it("should display the ACME category by default if there are ACME templates", async () => {
            const wrapper = await getWrapper(
                tuleap_templates,
                company_templates,
                external_templates,
                "ACME",
                "ACME",
            );

            expect(
                wrapper
                    .get("[data-test=project-registration-acme-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(true);
        });

        it("should display the Tuleap category by default if there are no ACME templates", async () => {
            const wrapper = await getWrapper(
                tuleap_templates,
                [],
                external_templates,
                "ACME",
                "Tuleap",
            );

            expect(
                wrapper
                    .get("[data-test=project-registration-tuleap-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(true);
        });

        it("should display the first external template category by default if there are no tuleap/ACME templates", async () => {
            const wrapper = await getWrapper([], [], external_templates, "ACME", "SAFe");

            expect(
                wrapper
                    .get("[data-test=project-registration-SAFe-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(CategorisedExternalTemplatesList).isVisible()).toBe(true);
        });

        it("should display the advanced category by default if there are no ACME/Tuleap/External templates", async () => {
            const wrapper = await getWrapper([], [], [], "ACME", "Advanced");

            expect(
                wrapper
                    .get("[data-test=project-registration-advanced-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(true);
        });
    });
});

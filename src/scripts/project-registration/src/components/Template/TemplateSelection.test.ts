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
import type { ConfigurationState } from "../../store/configuration";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../store/type";
import type { ExternalTemplateData, TemplateData } from "../../type";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import TuleapCompanyTemplateList from "./Company/CompanyTemplateList.vue";
import AdvancedTemplateList from "./Advanced/AdvancedTemplateList.vue";
import CategorisedExternalTemplatesList from "./CategorisedExternalTemplates/CategorisedExternalTemplatesList.vue";

describe("TemplateSelection", () => {
    async function getWrapper(
        tuleap_templates: TemplateData[],
        company_templates: TemplateData[],
        external_templates: ExternalTemplateData[],
        company_name: string,
        selected_template_category: null | string = null,
    ): Promise<Wrapper<TemplateSelection>> {
        const configuration_state: ConfigurationState = {
            tuleap_templates,
            company_templates,
            company_name,
            external_templates,
        } as ConfigurationState;

        return shallowMount(TemplateSelection, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: configuration_state,
                        selected_template_category,
                    } as RootState & { configuration: ConfigurationState },
                }),
            },
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
            await wrapper.vm.$nextTick();
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
            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-acme-templates-tab]")
                    .element.innerHTML.trim(),
            ).toBe("Custom templates");
        });

        it("should not display the tab containing the company templates when there are no company templates", async () => {
            const wrapper = await getWrapper(tuleap_templates, [], external_templates, "ACME");
            await wrapper.vm.$nextTick();
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

            expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();

            await wrapper.vm.$nextTick();
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
            );

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setSelectedTemplateCategory",
                "ACME",
            );
            wrapper.vm.$store.state.selected_template_category = "ACME";

            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-acme-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(true);
        });

        it("should display the Tuleap category by default if there are no ACME templates", async () => {
            const wrapper = await getWrapper(tuleap_templates, [], external_templates, "ACME");

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setSelectedTemplateCategory",
                "Tuleap",
            );
            wrapper.vm.$store.state.selected_template_category = "Tuleap";

            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-tuleap-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(true);
        });

        it("should display the first external template category by default if there are no tuleap/ACME templates", async () => {
            const wrapper = await getWrapper([], [], external_templates, "ACME");

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setSelectedTemplateCategory",
                "SAFe",
            );
            wrapper.vm.$store.state.selected_template_category = "SAFe";

            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-SAFe-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(CategorisedExternalTemplatesList).isVisible()).toBe(true);
        });

        it("should display the advanced category by default if there are no ACME/Tuleap/External templates", async () => {
            const wrapper = await getWrapper([], [], [], "ACME");

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setSelectedTemplateCategory",
                "Advanced",
            );
            wrapper.vm.$store.state.selected_template_category = "Advanced";

            await wrapper.vm.$nextTick();
            expect(
                wrapper
                    .get("[data-test=project-registration-advanced-templates-tab]")
                    .element.classList.contains("tlp-tab-active"),
            ).toBe(true);

            expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(true);
        });
    });

    it("should toggle the right templates categories", async () => {
        const wrapper = await getWrapper(
            tuleap_templates,
            company_templates,
            external_templates,
            "ACME",
        );
        const tab_tuleap_templates = wrapper.get(
            "[data-test=project-registration-tuleap-templates-tab]",
        );
        const tab_acme_templates = wrapper.get(
            "[data-test=project-registration-acme-templates-tab]",
        );
        const tab_advanced_templates = wrapper.get(
            "[data-test=project-registration-advanced-templates-tab]",
        );
        const tab_safe_templates = wrapper.get(
            "[data-test=project-registration-SAFe-templates-tab]",
        );
        const tab_dummy_templates = wrapper.get(
            "[data-test=project-registration-dummies-templates-tab]",
        );

        tab_safe_templates.trigger("click");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetSelectedTemplate");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedTemplateCategory",
            "SAFe",
        );
        wrapper.vm.$store.state.selected_template_category = "SAFe";

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(false);
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(0).isVisible()).toBe(
            true,
        );
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(1).isVisible()).toBe(
            false,
        );

        tab_acme_templates.trigger("click");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetSelectedTemplate");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedTemplateCategory",
            "ACME",
        );
        wrapper.vm.$store.state.selected_template_category = "ACME";

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(true);
        expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(false);
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(0).isVisible()).toBe(
            false,
        );
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(1).isVisible()).toBe(
            false,
        );

        tab_dummy_templates.trigger("click");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetSelectedTemplate");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedTemplateCategory",
            "dummies",
        );
        wrapper.vm.$store.state.selected_template_category = "dummies";

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(false);
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(0).isVisible()).toBe(
            false,
        );
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(1).isVisible()).toBe(
            true,
        );

        tab_advanced_templates.trigger("click");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetSelectedTemplate");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedTemplateCategory",
            "Advanced",
        );
        wrapper.vm.$store.state.selected_template_category = "Advanced";

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(true);
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(0).isVisible()).toBe(
            false,
        );
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(1).isVisible()).toBe(
            false,
        );

        tab_tuleap_templates.trigger("click");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetSelectedTemplate");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedTemplateCategory",
            "Tuleap",
        );
        wrapper.vm.$store.state.selected_template_category = "Tuleap";

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TuleapCompanyTemplateList).isVisible()).toBe(false);
        expect(wrapper.findComponent(TuleapTemplateList).isVisible()).toBe(true);
        expect(wrapper.findComponent(AdvancedTemplateList).isVisible()).toBe(false);
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(0).isVisible()).toBe(
            false,
        );
        expect(wrapper.findAllComponents(CategorisedExternalTemplatesList).at(1).isVisible()).toBe(
            false,
        );
    });
});

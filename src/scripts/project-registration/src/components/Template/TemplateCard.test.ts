/*
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TemplateCard from "./TemplateCard.vue";
import type { TemplateData } from "../../type";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("CardWithChildren", () => {
    const getters = {
        is_currently_selected_template: (): boolean => false,
    };

    async function createWrapper(tuleap_template: TemplateData): Promise<Wrapper<TemplateCard>> {
        return shallowMount(TemplateCard, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: { template: tuleap_template },
            mocks: {
                $store: createStoreMock({ getters }),
            },
        });
    }

    it(`display cards`, async () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };
        const wrapper = await createWrapper(tuleap_template);

        expect(wrapper.find("[data-test=scrum-template-svg]").exists()).toBeTruthy();
    });

    it(`checks the input`, async () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };
        const wrapper = await createWrapper(tuleap_template);

        wrapper.get("[data-test=project-registration-card-label]").trigger("click");

        const radio: HTMLInputElement = wrapper.get("[data-test=project-registration-radio]")
            .element as HTMLInputElement;
        expect(radio.checked).toBe(true);
    });

    it(`stores the template when the template is choosen`, async () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };

        const wrapper = await createWrapper(tuleap_template);

        wrapper.get("[data-test=project-registration-radio]").trigger("change");

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "setSelectedTemplate",
            tuleap_template,
        );
    });

    it("should check the input when the current template is selected", async () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };

        getters.is_currently_selected_template = (): boolean => true;

        const wrapper = await createWrapper(tuleap_template);

        const radio = wrapper.get("[data-test=project-registration-radio]").element;
        if (!(radio instanceof HTMLInputElement)) {
            throw new Error("[data-test=project-registration-radio] is not a HTMLInputElement");
        }

        expect(radio.checked).toBe(true);
    });
});

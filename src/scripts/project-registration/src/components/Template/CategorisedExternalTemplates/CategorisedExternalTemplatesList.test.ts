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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import TemplateCardContent from "../TemplateCard.vue";
import CategorisedExternalTemplatesList from "./CategorisedExternalTemplatesList.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

function getWrapper(): VueWrapper {
    return shallowMount(CategorisedExternalTemplatesList, {
        global: {
            ...getGlobalTestOptions(),
        },
        propsData: {
            templates: [
                {
                    id: "program",
                    title: "SAFe - Program",
                    description: "SAFe - Program",
                    glyph: "<svg>SAFe Program</svg>",
                    is_built_in: true,
                    template_category: {
                        shortname: "SAFe",
                        label: "Program/Teams",
                        should_case_of_label_be_respected: false,
                    },
                },
                {
                    id: "teams",
                    title: "SAFe - Teams",
                    description: "SAFe - Teams",
                    glyph: "<svg>SAFe Teams</svg>",
                    is_built_in: true,
                    template_category: {
                        shortname: "SAFe",
                        label: "Program/Teams",
                        should_case_of_label_be_respected: false,
                    },
                },
            ],
        },
    });
}
describe("CategorisedExternalTemplatesList", () => {
    it(`spawns the components and sub components`, () => {
        const wrapper = getWrapper();
        expect(wrapper.findComponent(TemplateCardContent).exists()).toBe(true);
        expect(wrapper.findAllComponents(TemplateCardContent)).toHaveLength(2);
    });
    describe("has several project templates -", () => {
        it(`spawns the components and sub components`, () => {
            const wrapper = getWrapper();
            expect(wrapper.findComponent(TemplateCardContent).exists()).toBe(true);
            expect(wrapper.findAllComponents(TemplateCardContent)).toHaveLength(2);
        });
    });
});

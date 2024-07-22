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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TemplateCard from "./TemplateCard.vue";
import type { TemplateData } from "../../type";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { useStore } from "../../stores/root";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

describe("CardWithChildren", () => {
    let setSelectedTemplate: jest.Mock;
    let is_currently_selected = false;

    beforeEach(() => {
        setSelectedTemplate = jest.fn();
        is_currently_selected = false;
    });

    function createWrapper(tuleap_template: TemplateData): VueWrapper {
        const useStore = defineStore("root", {
            getters: {
                is_currently_selected_template: () => (): boolean => {
                    return is_currently_selected;
                },
            },
            actions: {
                setSelectedTemplate: setSelectedTemplate,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TemplateCard, {
            global: {
                ...getGlobalTestOptions(pinia),
                directives: {
                    "dompurify-html": buildVueDompurifyHTMLDirective(),
                },
            },
            propsData: { template: tuleap_template },
        });
    }

    it(`display cards`, () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };
        const wrapper = createWrapper(tuleap_template);

        expect(wrapper.find("[data-test=scrum-template-svg]").exists()).toBeTruthy();
    });

    it(`checks the input`, () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };
        const wrapper = createWrapper(tuleap_template);

        wrapper.get("[data-test=project-registration-card-label]").trigger("click");

        const radio: HTMLInputElement = wrapper.get("[data-test=project-registration-radio]")
            .element as HTMLInputElement;
        expect(radio.checked).toBe(true);
    });

    it(`stores the template when the template is choosen`, () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };

        const wrapper = createWrapper(tuleap_template);
        const store = useStore();

        wrapper.get("[data-test=project-registration-radio]").trigger("change");

        expect(store.setSelectedTemplate).toHaveBeenCalledWith(tuleap_template);
    });

    it("should check the input when the current template is selected", () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            id: "scrum_template",
            glyph: "<svg></svg>",
            is_built_in: true,
        };

        is_currently_selected = true;

        const wrapper = createWrapper(tuleap_template);

        const radio = wrapper.get("[data-test=project-registration-radio]").element;
        if (!(radio instanceof HTMLInputElement)) {
            throw new Error("[data-test=project-registration-radio] is not a HTMLInputElement");
        }

        expect(radio.checked).toBe(true);
    });
});

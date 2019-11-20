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

import { createLocalVue, shallowMount, Wrapper } from "@vue/test-utils";
import TuleapTemplateCard from "./TuleapTemplateCard.vue";
import { TemplateData } from "../../type";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";

describe("CardWithChildren", () => {
    let local_vue = createLocalVue();

    beforeEach(async () => {
        local_vue = await createProjectRegistrationLocalVue();
    });

    function createWrapper(tuleap_template: TemplateData): Wrapper<TuleapTemplateCard> {
        return shallowMount(TuleapTemplateCard, {
            localVue: local_vue,
            propsData: { template: tuleap_template }
        });
    }

    it(`It display cards`, () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            name: "scrum_template",
            svg: "<svg></svg>"
        };
        const wrapper = createWrapper(tuleap_template);

        expect(wrapper.contains("[data-test=scrum-template-svg")).toBeTruthy();
    });

    it(`It checks the input`, () => {
        const tuleap_template: TemplateData = {
            title: "scrum",
            description: "scrum desc",
            name: "scrum_template",
            svg: "<svg></svg>"
        };
        const wrapper = createWrapper(tuleap_template);

        wrapper.find("[data-test=project-registration-card-label]").trigger("click");

        const checkbox: HTMLInputElement = wrapper.find("[data-test=project-registration-radio]")
            .element as HTMLInputElement;
        expect(checkbox.checked).toBe(true);
    });
});

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

import { Store } from "vuex-mock-store";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import { createLocalVue, shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import TemplateCardContent from "../TemplateCard.vue";
import TuleapTemplateList from "./TuleapTemplateList.vue";
import { State } from "../../../store/type";
import { TemplateData } from "../../../type";

describe("TuleapTemplateList", () => {
    let local_vue = createLocalVue();
    let store: Store;
    let wrapper: Wrapper<TuleapTemplateList>;

    async function createWrapper(
        tuleap_templates: TemplateData[]
    ): Promise<Wrapper<TuleapTemplateList>> {
        const state: State = {
            tuleap_templates: tuleap_templates,
        } as State;

        const store_options = {
            state,
        };
        store = createStoreMock(store_options);
        local_vue = await createProjectRegistrationLocalVue();

        return shallowMount(TuleapTemplateList, {
            localVue: local_vue,
            mocks: { $store: store },
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

        expect(wrapper.contains(TemplateCardContent)).toBe(true);
        expect(wrapper.findAll(TemplateCardContent)).toHaveLength(2);
    });

    it(`does not display anything if no tuleap templates are found`, async () => {
        const tuleap_templates: TemplateData[] = [];

        wrapper = await createWrapper(tuleap_templates);

        expect(wrapper.contains("[data-test=tuleap-templates-section]")).toBe(false);
    });
});

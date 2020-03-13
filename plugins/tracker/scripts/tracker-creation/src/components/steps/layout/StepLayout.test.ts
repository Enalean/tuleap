/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import StepLayout from "./StepLayout.vue";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { State } from "../../../store/type";
import { createTrackerCreationLocalVue } from "../../../helpers/local-vue-for-tests";

describe("StepLayout", () => {
    let wrapper: Wrapper<StepLayout>;

    beforeEach(async () => {
        wrapper = shallowMount(StepLayout, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        company_name: ""
                    } as State
                })
            },
            localVue: await createTrackerCreationLocalVue()
        });
    });

    it(`displays the company name if the platform name is not Tuleap`, () => {
        wrapper.vm.$store.state.company_name = "Nichya company";
        expect(wrapper.find("[data-test=platform-template-name]").element.innerHTML.trim()).toEqual(
            "Nichya company templates"
        );
    });

    it(`displays 'Custom templates' if the platform name is Tuleap`, () => {
        wrapper.vm.$store.state.company_name = "Tuleap";
        expect(wrapper.find("[data-test=platform-template-name]").element.innerHTML.trim()).toEqual(
            "Custom templates"
        );
    });
});

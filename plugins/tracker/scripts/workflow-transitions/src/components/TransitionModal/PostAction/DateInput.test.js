/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import DateInput from "./DateInput.vue";
import { DATE_FIELD_VALUE } from "../../../constants/workflow-constants.js";
import { createLocalVueForTests } from "../../../support/local-vue.js";

describe("DateInput", () => {
    let wrapper;

    beforeEach(async () => {
        wrapper = shallowMount(DateInput, {
            propsData: { value: DATE_FIELD_VALUE.CLEAR },
            localVue: await createLocalVueForTests(),
        });
    });

    const findSelectedOption = () => {
        const select = wrapper.get("select").element;
        return select.options[select.selectedIndex];
    };

    describe("without value", () => {
        beforeEach(() => wrapper.setProps({ value: null }));

        it("Shows placeholder", () => {
            expect(findSelectedOption().dataset.testType).toBe("placeholder");
        });
    });

    describe('with "current" value', () => {
        beforeEach(() => wrapper.setProps({ value: DATE_FIELD_VALUE.CURRENT }));

        it('Select "current" option', () => {
            expect(findSelectedOption().dataset.testType).toBe("current");
        });
    });

    describe("when selecting another option", () => {
        beforeEach(() => {
            wrapper.get('[data-test-type="current"]').element.selected = true;
            wrapper.get("select").trigger("change");
        });

        it("emits input event with corresponding value", () => {
            expect(wrapper.emitted().input).toBeTruthy();
            expect(wrapper.emitted().input[0]).toStrictEqual([DATE_FIELD_VALUE.CURRENT]);
        });
    });
});

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
 */

import localVue from "../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import FormatSelector from "./FormatSelector.vue";

let value, disabled, required;

function getInstance() {
    return shallowMount(FormatSelector, {
        localVue,
        propsData: {
            id: "unique-id",
            label: "My translated label",
            value,
            disabled,
            required,
        },
    });
}

describe(`FormatSelector`, () => {
    describe(`when the format was "html"`, () => {
        beforeEach(() => {
            value = "html";
        });

        it(`and when I switch to "text",
            it will dispatch an "input" event with the new format`, () => {
            const wrapper = getInstance();
            wrapper.vm.format = "text";

            expect(wrapper.emitted("input")[0]).toEqual(["text"]);
        });
    });

    describe(`when the format was "text"`, () => {
        it(`and when I switch to "html",
            it will dispatch an "input" event with the new format`, () => {
            const wrapper = getInstance();
            wrapper.vm.format = "html";

            expect(wrapper.emitted("input")[0]).toEqual(["html"]);
        });
    });

    it(`when the format is anything else, it throws`, () => {
        const wrapper = getInstance();
        expect(wrapper.vm.$options.props.value.validator("markdown")).toBe(false);
    });

    describe(`disabled`, () => {
        it(`will set the format selectbox to disabled`, () => {
            disabled = true;

            const wrapper = getInstance();
            const format_selectbox = wrapper.get("[data-test=format]");

            expect(format_selectbox.attributes("disabled")).toBe("disabled");
        });
    });

    describe(`required`, () => {
        it(`will show a red asterisk icon next to the field label`, () => {
            required = true;
            const wrapper = getInstance();

            expect(wrapper.contains(".fa-asterisk")).toBe(true);
        });
    });
});

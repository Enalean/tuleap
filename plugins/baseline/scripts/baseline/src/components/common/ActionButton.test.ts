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

import type { Wrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import localVue from "../../support/local-vue";
import ActionButton from "./ActionButton.vue";

describe("ActionButton", () => {
    const spinner_selector = '[data-test-type="spinner"]';
    const button_selector = '[data-test-type="button"]';
    let wrapper: Wrapper<Vue>;

    beforeEach(() => {
        wrapper = mount(ActionButton, {
            localVue,
            propsData: {
                icon: "delete",
            },
        });
    });

    it("does not show spinner", () => {
        expect(wrapper.find(spinner_selector).exists()).toBeFalsy();
    });
    it("shows icon", () => {
        expect(wrapper.find(".fa-delete").exists()).toBeTruthy();
    });
    it("enables button", () => {
        expect(wrapper.get(button_selector).attributes().disabled).toBeFalsy();
    });

    describe("when clicking", () => {
        beforeEach(() => wrapper.trigger("click"));

        it("emits click", () => {
            expect(wrapper.emitted().click).toBeTruthy();
        });
    });

    describe("when loading", () => {
        beforeEach(() => wrapper.setProps({ loading: true }));

        it("shows spinner", () => {
            expect(wrapper.find(spinner_selector).exists()).toBeTruthy();
        });
    });

    describe("when disabled", () => {
        beforeEach(() => wrapper.setProps({ disabled: true }));

        it("disables button", () => {
            expect(wrapper.get(button_selector).attributes().disabled).toBeTruthy();
        });
    });
});

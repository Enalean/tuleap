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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ActionButton from "./ActionButton.vue";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";

describe("ActionButton", () => {
    const spinner_selector = '[data-test-type="spinner"]';
    const button_selector = '[data-test-type="button"]';

    function createWrapper(disabled = false, loading = false): VueWrapper {
        return shallowMount(ActionButton, {
            global: { ...getGlobalTestOptions() },
            props: {
                icon: "delete",
                disabled,
                loading,
            },
        });
    }

    it("does not show spinner", () => {
        const wrapper = createWrapper();
        expect(wrapper.find(spinner_selector).exists()).toBeFalsy();
    });
    it("shows icon", () => {
        const wrapper = createWrapper();
        expect(wrapper.find(".fa-delete").exists()).toBeTruthy();
    });
    it("enables button", () => {
        const wrapper = createWrapper();
        expect(wrapper.get(button_selector).attributes()).not.toHaveProperty("disabled");
    });

    it("when clicking it emits click", () => {
        const wrapper = createWrapper();
        wrapper.trigger("click");
        expect(wrapper.emitted().click).toBeTruthy();
    });

    it("when loading it shows spinner", () => {
        const wrapper = createWrapper(false, true);
        expect(wrapper.find(spinner_selector).exists()).toBeTruthy();
    });

    it("when disabled it disables button", () => {
        const wrapper = createWrapper(true);
        expect(wrapper.get(button_selector).attributes()).toHaveProperty("disabled");
    });
});

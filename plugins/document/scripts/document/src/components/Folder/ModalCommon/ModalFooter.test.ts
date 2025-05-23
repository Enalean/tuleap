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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import ModalFooter from "./ModalFooter.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ModalFooter", () => {
    function createWrapper(isLoading: boolean): VueWrapper<InstanceType<typeof ModalFooter>> {
        return shallowMount(ModalFooter, {
            props: {
                isLoading: isLoading,
                ariaLabelledBy: "label",
                dataTest: "",
                iconSubmitButtonClass: "",
                submitButtonLabel: "Submit",
            },
            global: { ...getGlobalTestOptions({}) },
        });
    }
    it(`Given: the footer is not loading
    'When an item is created or updated
    'Then the button does not display the loading icon`, () => {
        const wrapper = createWrapper(false);
        expect(wrapper.find("[data-test=document-modal-footer-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-modal-footer-spinner]").exists()).toBeFalsy();
    });

    it(`Given: the footer is loading
    'When an item is created or updated
    'Then the button display the loading icon`, () => {
        const wrapper = createWrapper(true);
        expect(wrapper.find("[data-test=document-modal-footer-icon]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-modal-footer-spinner]").exists()).toBeTruthy();
    });
});

/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import CancelSaveButtons from "./CancelSaveButtons.vue";
import { getGlobalTestOptions } from "../../../../../../helpers/global-options-for-test";
import emitter from "../../../../../../helpers/emitter";

jest.useFakeTimers();

describe("CancelSaveButtons", () => {
    function createWrapper(
        is_action_ongoing: boolean,
    ): VueWrapper<InstanceType<typeof CancelSaveButtons>> {
        return shallowMount(CancelSaveButtons, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                is_action_ongoing,
            },
        });
    }

    describe(`When there is no ongoing action`, () => {
        let wrapper: VueWrapper<InstanceType<typeof CancelSaveButtons>>;
        beforeEach(() => {
            wrapper = createWrapper(false);
        });

        it(`when the user clicks on the cancel button, it will emit a "cancel" event`, () => {
            const cancel_button = wrapper.get("[data-test=cancel]");

            cancel_button.trigger("click");

            expect(wrapper.emitted("cancel")).toBeTruthy();
        });

        it(`when the user presses the ESC key, it will emit a "cancel" event`, () => {
            const wrapper = createWrapper(false);

            emitter.emit("esc-key-pressed");

            expect(wrapper.emitted("cancel")).toBeTruthy();
        });

        it(`when the user clicks on the save button, it will emit a "save" event`, () => {
            const save_button = wrapper.get("[data-test=save]");
            save_button.trigger("click");

            expect(wrapper.emitted("save")).toBeTruthy();
        });
    });

    describe(`When there is an ongoing action`, () => {
        let wrapper: VueWrapper<InstanceType<typeof CancelSaveButtons>>;
        beforeEach(() => {
            wrapper = createWrapper(true);
        });

        it(`the save button will be disabled and will show a spinner icon`, () => {
            const save_button = wrapper.get("[data-test=save]");
            expect(save_button.attributes("disabled")).toBe("");
            const save_icon = wrapper.get("[data-test=save-icon]");
            expect(save_icon.classes()).toContain("fa-circle-o-notch");
            expect(save_icon.classes()).toContain("fa-spin");
        });

        it(`the cancel button will be disabled`, () => {
            const cancel_button = wrapper.get("[data-test=cancel]");
            expect(cancel_button.attributes("disabled")).toBe("");
        });

        it(`when the user presses the ESC key, it won't emit an event`, () => {
            emitter.emit("esc-key-pressed");

            expect(wrapper.emitted("cancel")).toBeUndefined();
        });

        it(`when the user clicks on the save button, it won't emit an event`, () => {
            const save_button = wrapper.get("[data-test=save]");
            save_button.trigger("click");

            expect(wrapper.emitted("cancel")).toBeUndefined();
        });

        it(`when the user clicks on the cancel button, it won't emit an event`, () => {
            const cancel_button = wrapper.get("[data-test=cancel]");
            cancel_button.trigger("click");

            expect(wrapper.emitted("cancel")).toBeUndefined();
        });
    });
});

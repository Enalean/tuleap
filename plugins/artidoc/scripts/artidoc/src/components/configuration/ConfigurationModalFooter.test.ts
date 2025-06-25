/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import ConfigurationModalFooter from "@/components/configuration/ConfigurationModalFooter.vue";
import {
    CLOSE_CONFIGURATION_MODAL,
    TRACKER_SELECTION_TAB,
} from "@/components/configuration/configuration-modal";

describe("ConfigurationModalFooter", () => {
    let onSaveCallback: () => void, closeModal: () => void;
    let is_success: boolean;
    let is_error: boolean;

    beforeEach(() => {
        onSaveCallback = vi.fn();
        closeModal = vi.fn();
        is_success = false;
        is_error = false;
    });

    const getWrapper = (): VueWrapper => {
        return shallowMount(ConfigurationModalFooter, {
            props: {
                current_tab: TRACKER_SELECTION_TAB,
                on_save_callback: onSaveCallback,
                is_submit_button_disabled: false,
                error_message: "",
                is_error,
                is_saving: false,
                is_success,
            },
            global: {
                provide: {
                    [CLOSE_CONFIGURATION_MODAL.valueOf()]: closeModal,
                },
                plugins: [createGettext({ silent: true })],
            },
        });
    };

    it("should display success feedback", () => {
        is_success = true;
        const wrapper = getWrapper();

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(false);
    });

    it("should display error feedback", () => {
        is_error = true;
        const wrapper = getWrapper();

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });

    it("When the cancel button is clicked, then it should execute the closeModal() callback", () => {
        const wrapper = getWrapper();
        wrapper.get("[data-test=cancel-modal-button]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the close button is clicked, then it should execute the closeModal() callback", () => {
        is_success = true;
        const wrapper = getWrapper();
        wrapper.get("[data-test=close-modal-after-success]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the submit button is clicked, then it should execute the onSave() callback", () => {
        const wrapper = getWrapper();

        wrapper.get("[data-test=submit]").trigger("click");

        expect(onSaveCallback).toHaveBeenCalledOnce();
    });
});

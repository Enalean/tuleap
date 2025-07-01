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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import ConfigureReadonlyFieldsFooter from "@/components/configuration/ConfigureReadonlyFieldsFooter.vue";
import { CLOSE_CONFIGURATION_MODAL } from "@/components/configuration/configuration-modal";
import type { SaveFieldsConfiguration } from "@/configuration/FieldsConfigurationSaver";
import { SaveFieldsConfigurationStub } from "@/configuration/stubs/SaveFieldsConfigurationStub";

describe("ConfigureReadonlyFieldsFooter", () => {
    let closeModal: () => void;

    beforeEach(() => {
        closeModal = vi.fn();
    });

    const getWrapper = (configuration_saver: SaveFieldsConfiguration): VueWrapper => {
        return shallowMount(ConfigureReadonlyFieldsFooter, {
            props: {
                is_submit_button_disabled: false,
                new_selected_fields: [],
                configuration_saver,
            },
            global: {
                provide: {
                    [CLOSE_CONFIGURATION_MODAL.valueOf()]: closeModal,
                },
                plugins: [createGettext({ silent: true })],
            },
        });
    };

    it("should display error feedback", async () => {
        const wrapper = getWrapper(SaveFieldsConfigurationStub.buildError());
        await wrapper.find("[data-test=submit]").trigger("click");

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });

    it("When the cancel button is clicked, then it should execute the closeModal() callback", async () => {
        const wrapper = getWrapper(SaveFieldsConfigurationStub.buildSuccess());
        await wrapper.get("[data-test=cancel-modal-button]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the submit button is clicked, then it should close the modal", async () => {
        const wrapper = getWrapper(SaveFieldsConfigurationStub.buildSuccess());

        await wrapper.get("[data-test=submit]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });
});

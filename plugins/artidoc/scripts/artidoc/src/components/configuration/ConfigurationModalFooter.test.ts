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
import type { ConfigurationStore, Tracker } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import ConfigurationModalFooter from "@/components/configuration/ConfigurationModalFooter.vue";
import type { ConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import {
    CLOSE_CONFIGURATION_MODAL,
    TRACKER_SELECTION_TAB,
} from "@/components/configuration/configuration-modal";

describe("ConfigurationModalFooter", () => {
    let configuration_helper: ConfigurationScreenHelper,
        onSaveCallback: () => void,
        closeModal: () => void;

    beforeEach(() => {
        onSaveCallback = vi.fn();
        closeModal = vi.fn();
    });

    const getWrapper = (configuration_store: ConfigurationStore): VueWrapper => {
        configuration_helper = useConfigurationScreenHelper(configuration_store);

        return shallowMount(ConfigurationModalFooter, {
            props: {
                configuration_helper,
                current_tab: TRACKER_SELECTION_TAB,
                on_save_callback: onSaveCallback,
                is_submit_button_disabled: false,
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
        const wrapper = getWrapper(ConfigurationStoreStub.withSuccessfulSave());

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(false);
    });

    it("should display error feedback", () => {
        const wrapper = getWrapper(ConfigurationStoreStub.withError());

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });

    it("When the cancel button is clicked, then it should execute the closeModal() callback", () => {
        const wrapper = getWrapper(
            ConfigurationStoreStub.withSelectedTracker({ id: 102 } as Tracker),
        );
        wrapper.get("[data-test=cancel-modal-button]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the close button is clicked, then it should execute the closeModal() callback", () => {
        const wrapper = getWrapper(ConfigurationStoreStub.withSuccessfulSave());
        wrapper.get("[data-test=close-modal-after-success]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the submit button is clicked, then it should execute the onSave() callback", async () => {
        const wrapper = getWrapper(
            ConfigurationStoreStub.withSelectedTracker({ id: 102 } as Tracker),
        );

        configuration_helper.new_selected_tracker.value = { id: 103 } as Tracker;
        await wrapper.vm.$nextTick();

        wrapper.get("[data-test=submit]").trigger("click");

        expect(onSaveCallback).toHaveBeenCalledOnce();
    });
});

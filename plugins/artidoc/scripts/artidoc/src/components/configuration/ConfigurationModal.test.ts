/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import type { ConfigurationStore, Tracker } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import type { OpenConfigurationModalBus } from "@/composables/useOpenConfigurationModalBus";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBus,
} from "@/composables/useOpenConfigurationModalBus";
import { mockStrictInject } from "@/helpers/mock-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

describe("ConfigurationModal", () => {
    function setupInjectionKeys(store: ConfigurationStore, bus: OpenConfigurationModalBus): void {
        mockStrictInject([
            [CONFIGURATION_STORE, store],
            [OPEN_CONFIGURATION_MODAL_BUS, bus],
        ]);
    }

    it("should display success feedback", () => {
        setupInjectionKeys(
            ConfigurationStoreStub.withSuccessfullSave(),
            useOpenConfigurationModalBus(),
        );

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(false);
    });

    it("should display error feedback", () => {
        setupInjectionKeys(ConfigurationStoreStub.withError(), useOpenConfigurationModalBus());

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });

    it("should save the configuration and call the onSave callback", async () => {
        let has_been_called = false;
        const onSuccessfulSaved = (): void => {
            has_been_called = true;
        };

        const bus = useOpenConfigurationModalBus();

        const save: ConfigurationStore["saveConfiguration"] = vi
            .fn()
            .mockImplementation((new_selected_tracker: Tracker, onSave: () => void): void => {
                onSave();
            });

        setupInjectionKeys(ConfigurationStoreStub.withMockedSavedConfiguration(save), bus);

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        bus.openModal(onSuccessfulSaved);

        expect(has_been_called).toBe(false);

        await wrapper.find("form").trigger("submit");

        expect(has_been_called).toBe(true);
    });
});

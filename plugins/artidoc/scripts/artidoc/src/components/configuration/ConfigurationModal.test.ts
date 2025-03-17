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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import type { OpenConfigurationModalBusStore } from "@/stores/useOpenConfigurationModalBusStore";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";

describe("ConfigurationModal", () => {
    function getWrapper(
        store: ConfigurationStore,
        bus: OpenConfigurationModalBusStore,
    ): VueWrapper {
        return shallowMount(ConfigurationModal, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIGURATION_STORE.valueOf()]: store,
                    [OPEN_CONFIGURATION_MODAL_BUS.valueOf()]: bus,
                    [ARE_FIELDS_ENABLED.valueOf()]: true,
                },
            },
        });
    }

    it("should display fields tab as disabled if the tracker is not configured", () => {
        const wrapper = getWrapper(
            ConfigurationStoreStub.withSelectedTracker(null),
            useOpenConfigurationModalBusStore(),
        );

        expect(wrapper.find("[data-test=tab-fields]").classes()).toContain("tlp-tab-disabled");
    });

    it("should display success feedback", () => {
        const wrapper = getWrapper(
            ConfigurationStoreStub.withSuccessfulSave(),
            useOpenConfigurationModalBusStore(),
        );

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(false);
    });

    it("should display error feedback", () => {
        const wrapper = getWrapper(
            ConfigurationStoreStub.withError(),
            useOpenConfigurationModalBusStore(),
        );

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });

    it("should save the configuration", async () => {
        let has_been_called = false;

        const bus = useOpenConfigurationModalBusStore();

        const save: ConfigurationStore["saveConfiguration"] = vi
            .fn()
            .mockImplementation((): void => {
                has_been_called = true;
            });

        const wrapper = getWrapper(ConfigurationStoreStub.withMockedSavedConfiguration(save), bus);

        bus.openModal();

        expect(has_been_called).toBe(false);

        await wrapper.find("form").trigger("submit");

        expect(has_been_called).toBe(true);
    });

    it("When the modal is closed after a successful save, then it should execute onSuccessfulSaveCallback", () => {
        const bus = useOpenConfigurationModalBusStore();
        const wrapper = getWrapper(ConfigurationStoreStub.withSuccessfulSave(), bus);
        const onSuccessfulSaveCallback = vi.fn();

        bus.openModal(onSuccessfulSaveCallback);

        wrapper.find("[data-test=close-modal-after-success]").trigger("click");

        expect(onSuccessfulSaveCallback).toHaveBeenCalledOnce();
    });
});

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
import { mount } from "@vue/test-utils";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { createGettext } from "vue3-gettext";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import type { OpenConfigurationModalBusStore } from "@/stores/useOpenConfigurationModalBusStore";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";
import { TITLE } from "@/title-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import {
    ALLOWED_TRACKERS,
    buildAllowedTrackersCollection,
} from "@/configuration/AllowedTrackersCollection";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { SelectedTrackerStub } from "@/helpers/stubs/SelectedTrackerStub";

describe("ConfigurationModal", () => {
    function getWrapper(
        store: ConfigurationStore,
        bus: OpenConfigurationModalBusStore,
    ): VueWrapper {
        return mount(ConfigurationModal, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIGURATION_STORE.valueOf()]: store,
                    [ALLOWED_TRACKERS.valueOf()]: buildAllowedTrackersCollection([]),
                    [SELECTED_TRACKER.valueOf()]: SelectedTrackerStub.build(),
                    [OPEN_CONFIGURATION_MODAL_BUS.valueOf()]: bus,
                    [ARE_FIELDS_ENABLED.valueOf()]: true,
                    [TITLE.valueOf()]: "My artidoc",
                    [SECTIONS_STATES_COLLECTION.valueOf()]: SectionsStatesCollectionStub.build(),
                },
            },
        });
    }

    it("When the modal is closed after a successful save, then it should execute onSuccessfulSaveCallback", async () => {
        const bus = useOpenConfigurationModalBusStore();
        const wrapper = getWrapper(ConfigurationStoreStub.withSuccessfulSave(), bus);
        const onSuccessfulSaveCallback = vi.fn();

        bus.openModal(onSuccessfulSaveCallback);
        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=close-modal-after-success]").trigger("click");

        expect(onSuccessfulSaveCallback).toHaveBeenCalledOnce();
    });
});

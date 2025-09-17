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
import { createGettext } from "vue3-gettext";
import type { OpenConfigurationModalBusStore } from "@/stores/useOpenConfigurationModalBusStore";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import { TITLE } from "@/title-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import {
    ALLOWED_TRACKERS,
    buildAllowedTrackersCollection,
} from "@/configuration/AllowedTrackersCollection";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { SelectedTrackerStub } from "@/helpers/stubs/SelectedTrackerStub";
import ConfigureTracker from "@/components/configuration/ConfigureTracker.vue";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import {
    buildSelectedFieldsCollection,
    SELECTED_FIELDS,
} from "@/configuration/SelectedFieldsCollection";
import {
    AVAILABLE_FIELDS,
    buildAvailableFieldsCollection,
} from "@/configuration/AvailableFieldsCollection";
import ConfigureTrackerFooter from "@/components/configuration/ConfigureTrackerFooter.vue";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import { Option } from "@tuleap/option";
import * as configuration_saver from "@/configuration/TrackerConfigurationSaver";
import { SaveTrackerConfigurationStub } from "@/configuration/stubs/SaveTrackerConfigurationStub";
import { CAN_USER_DISPLAY_VERSIONS } from "@/can-user-display-versions-injection-key";

describe("ConfigurationModal", () => {
    const epic_tracker = TrackerStub.build(12, "Epic");
    const story_tracker = TrackerStub.build(13, "Story");

    function getWrapper(bus: OpenConfigurationModalBusStore): VueWrapper {
        const selected_tracker = SelectedTrackerStub.withTracker(epic_tracker);
        const selected_fields = buildSelectedFieldsCollection([]);
        return mount(ConfigurationModal, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [ALLOWED_TRACKERS.valueOf()]: buildAllowedTrackersCollection([
                        epic_tracker,
                        story_tracker,
                    ]),
                    [SELECTED_TRACKER.valueOf()]: selected_tracker,
                    [OPEN_CONFIGURATION_MODAL_BUS.valueOf()]: bus,
                    [TITLE.valueOf()]: "My artidoc",
                    [SECTIONS_STATES_COLLECTION.valueOf()]: SectionsStatesCollectionStub.build(),
                    [DOCUMENT_ID.valueOf()]: 10,
                    [SELECTED_FIELDS.valueOf()]: selected_fields,
                    [AVAILABLE_FIELDS.valueOf()]: buildAvailableFieldsCollection(
                        selected_tracker,
                        selected_fields,
                    ),
                    [CAN_USER_DISPLAY_VERSIONS.valueOf()]: true,
                },
            },
        });
    }

    it("When the modal is closed after a successful save, then it should execute onSuccessfulSaveCallback", async () => {
        vi.spyOn(configuration_saver, "buildTrackerConfigurationSaver").mockReturnValue(
            SaveTrackerConfigurationStub.buildSuccess(),
        );

        const bus = useOpenConfigurationModalBusStore();
        const wrapper = getWrapper(bus);
        const onSuccessfulSaveCallback = vi.fn();

        bus.openModal(onSuccessfulSaveCallback);
        await wrapper.vm.$nextTick();
        wrapper
            .findComponent(ConfigureTracker)
            .findComponent(TrackerSelection)
            .vm.$emit("select-tracker", Option.fromNullable(story_tracker));
        await wrapper.vm.$nextTick();
        await wrapper
            .findComponent(ConfigureTracker)
            .findComponent(ConfigureTrackerFooter)
            .find("[data-test=submit]")
            .trigger("click");
        await wrapper.vm.$nextTick();

        await wrapper.find("[data-test=close-modal-after-success]").trigger("click");

        expect(onSuccessfulSaveCallback).toHaveBeenCalledOnce();
    });
});

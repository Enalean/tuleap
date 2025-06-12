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

import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import AddExistingSectionModal from "@/components/AddExistingSectionModal.vue";
import { createGettext } from "vue3-gettext";
import { Option } from "@tuleap/option";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import type { OpenAddExistingSectionModalBus } from "@/composables/useOpenAddExistingSectionModalBus";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import { noop } from "@/helpers/noop";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { SelectedTrackerStub } from "@/helpers/stubs/SelectedTrackerStub";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";

describe("AddExistingSectionModal", () => {
    let bus: OpenAddExistingSectionModalBus, selected_tracker: SelectedTrackerRef;
    beforeEach(() => {
        bus = useOpenAddExistingSectionModalBus();
        selected_tracker = SelectedTrackerStub.build();
    });

    function getWrapper(): VueWrapper {
        return shallowMount(AddExistingSectionModal, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [DOCUMENT_ID.valueOf()]: 1,
                    [SELECTED_TRACKER.valueOf()]: selected_tracker,
                    [SECTIONS_COLLECTION.valueOf()]: SectionsCollectionStub.withSections([]),
                    [OPEN_ADD_EXISTING_SECTION_MODAL_BUS.valueOf()]: bus,
                },
            },
        });
    }

    it("should display the modal", () => {
        const wrapper = getWrapper();

        expect(wrapper.classes()).not.toContain("tlp-modal-shown");

        bus.openModal(AT_THE_END, noop);

        expect(wrapper.classes()).toContain("tlp-modal-shown");
    });

    it(`Shows an error when the configured tracker has no title`, async () => {
        const wrapper = getWrapper();

        selected_tracker.value = Option.fromValue(TrackerStub.withDescription());

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=modal-error]").exists()).toBe(true);
    });
});

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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import AddExistingSectionModal from "@/components/AddExistingSectionModal.vue";
import { createGettext } from "vue3-gettext";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import { noop } from "@/helpers/noop";

describe("AddExistingSectionModal", () => {
    it("should display the modal", () => {
        const bus = useOpenAddExistingSectionModalBus();

        const wrapper = shallowMount(AddExistingSectionModal, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [DOCUMENT_ID.valueOf()]: 1,
                    [CONFIGURATION_STORE.valueOf()]: ConfigurationStoreStub.withSelectedTracker(
                        TrackerStub.withTitleAndDescription(),
                    ),
                    [SECTIONS_COLLECTION.valueOf()]: 1,
                    [OPEN_ADD_EXISTING_SECTION_MODAL_BUS.valueOf()]: bus,
                },
            },
        });

        expect(wrapper.classes()).not.toContain("tlp-modal-shown");

        bus.openModal(AT_THE_END, noop);

        expect(wrapper.classes()).toContain("tlp-modal-shown");
    });
});

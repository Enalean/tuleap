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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isPendingArtifactSection, isPendingFreetextSection } from "@/helpers/artidoc-section.type";
import AddNewSectionButton from "@/components/AddNewSectionButton.vue";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import type { OpenConfigurationModalBusStore } from "@/stores/useOpenConfigurationModalBusStore";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import { createGettext } from "vue3-gettext";
import type { InsertSections } from "@/sections/insert/SectionsInserter";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import type { OpenAddExistingSectionModalBus } from "@/composables/useOpenAddExistingSectionModalBus";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";
import { SectionsInserterStub } from "@/sections/stubs/SectionsInserterStub";
import { SelectedTrackerStub } from "@/helpers/stubs/SelectedTrackerStub";

vi.mock("@tuleap/tlp-dropdown");

describe("AddNewSectionButton", () => {
    let add_existing_section_bus: OpenAddExistingSectionModalBus,
        configuration_bus: OpenConfigurationModalBusStore;

    beforeEach(() => {
        add_existing_section_bus = useOpenAddExistingSectionModalBus();
        configuration_bus = useOpenConfigurationModalBusStore();
    });

    function getWrapper(
        sections_inserter: InsertSections,
        selected_tracker: SelectedTrackerRef,
        configuration_bus: OpenConfigurationModalBusStore,
        add_existing_section_bus: OpenAddExistingSectionModalBus,
    ): VueWrapper {
        return shallowMount(AddNewSectionButton, {
            props: { position: AT_THE_END, sections_inserter },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [SELECTED_TRACKER.valueOf()]: selected_tracker,
                    [OPEN_CONFIGURATION_MODAL_BUS.valueOf()]: configuration_bus,
                    [OPEN_ADD_EXISTING_SECTION_MODAL_BUS.valueOf()]: add_existing_section_bus,
                },
            },
        });
    }

    const expectLastInsertedSectionToBeAPendingArtifactSection = (
        inserted_section: ArtidocSection | null,
    ): void => {
        if (inserted_section === null) {
            throw new Error("Expected a pending artifact section to be inserted.");
        }

        expect(isPendingArtifactSection(inserted_section)).toBe(true);
    };

    const expectLastInsertedSectionToBeAPendingFreetextSection = (
        inserted_section: ArtidocSection | null,
    ): void => {
        if (inserted_section === null) {
            throw new Error("Expected a pending freetext section to be inserted.");
        }

        expect(isPendingFreetextSection(inserted_section)).toBe(true);
    };

    describe("[Create new section] when the tracker is not configured", () => {
        it("should ask to open the configuration modal on click", async () => {
            let has_modal_been_opened = false;

            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            const wrapper = getWrapper(
                SectionsInserterStub.withoutExpectedCall(),
                SelectedTrackerStub.withNoTracker(),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
        });

        it("should insert a pending artifact section after the configuration is saved", async () => {
            const selected_tracker = SelectedTrackerStub.withNoTracker();

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                selected_tracker.value = Option.fromValue(TrackerStub.withTitleAndDescription());
                onSuccessfulSaved();
            });

            const inserter = SectionsInserterStub.withExpectedCall();
            const wrapper = getWrapper(
                inserter,
                selected_tracker,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);

            expectLastInsertedSectionToBeAPendingArtifactSection(inserter.getLastInsertedSection());
        });
    });

    describe("[Create new section] when the tracker is configured but user cannot submit title", () => {
        it("should ask to open the configuration modal on click when title is not submittable", async () => {
            let has_modal_been_opened = false;

            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            const wrapper = getWrapper(
                SectionsInserterStub.withoutExpectedCall(),
                SelectedTrackerStub.withTracker(TrackerStub.withDescription()),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
        });

        it("should insert a pending artifact section after the configuration is saved", async () => {
            const selected_tracker = SelectedTrackerStub.withTracker(TrackerStub.withDescription());

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                selected_tracker.value = Option.fromValue(TrackerStub.withTitleAndDescription());
                onSuccessfulSaved();
            });

            const inserter = SectionsInserterStub.withExpectedCall();
            const wrapper = getWrapper(
                inserter,
                selected_tracker,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expectLastInsertedSectionToBeAPendingArtifactSection(inserter.getLastInsertedSection());
        });
    });

    describe("[Create new section] when the tracker is configured but user cannot submit description", () => {
        it("should ask to open the configuration modal on click when description is not submittable", async () => {
            let has_modal_been_opened = false;

            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            const wrapper = getWrapper(
                SectionsInserterStub.withoutExpectedCall(),
                SelectedTrackerStub.withTracker(TrackerStub.withTitle()),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
        });

        it("should insert a pending artifact section after the configuration is saved", async () => {
            const selected_tracker = SelectedTrackerStub.withTracker(TrackerStub.withTitle());

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                selected_tracker.value = Option.fromValue(TrackerStub.withTitleAndDescription());
                onSuccessfulSaved();
            });

            const inserter = SectionsInserterStub.withExpectedCall();
            const wrapper = getWrapper(
                inserter,
                selected_tracker,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expectLastInsertedSectionToBeAPendingArtifactSection(inserter.getLastInsertedSection());
        });
    });

    describe("[Create new section] when tracker is configured and user can submit new section", () => {
        it("should insert a pending artifact section", async () => {
            let has_modal_been_opened = false;

            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            const inserter = SectionsInserterStub.withExpectedCall();
            const wrapper = getWrapper(
                inserter,
                SelectedTrackerStub.build(),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(false);
            expectLastInsertedSectionToBeAPendingArtifactSection(inserter.getLastInsertedSection());
        });

        it("should ask to open the add existing section modal", async () => {
            let has_modal_been_opened = false;

            const configuration_bus = useOpenConfigurationModalBusStore();
            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            add_existing_section_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            const wrapper = getWrapper(
                SectionsInserterStub.withoutExpectedCall(),
                SelectedTrackerStub.build(),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-existing-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
        });
    });

    describe("[Add existing section] when the tracker is not configured", () => {
        it("should ask to open the configuration modal on click", async () => {
            const openConfigurationModal = vi.spyOn(configuration_bus, "openModal");
            const openAddExistingSectionModal = vi.spyOn(add_existing_section_bus, "openModal");

            const wrapper = getWrapper(
                SectionsInserterStub.withoutExpectedCall(),
                SelectedTrackerStub.withNoTracker(),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-existing-section]").trigger("click");

            expect(openConfigurationModal).toHaveBeenCalledOnce();
            expect(openAddExistingSectionModal).not.toHaveBeenCalled();
        });

        it("should open the AddExistingSectionModal after the configuration is saved", async () => {
            const selected_tracker = SelectedTrackerStub.withNoTracker();

            const openAddExistingSectionModal = vi.spyOn(add_existing_section_bus, "openModal");

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                selected_tracker.value = Option.fromValue(TrackerStub.withTitleAndDescription());
                onSuccessfulSaved();
            });

            const wrapper = getWrapper(
                SectionsInserterStub.withoutExpectedCall(),
                selected_tracker,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-existing-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(openAddExistingSectionModal).toHaveBeenCalledOnce();
        });
    });

    describe("AddNewFreetextSection", () => {
        it("should insert a new pending freetext section", () => {
            const inserter = SectionsInserterStub.withExpectedCall();
            const wrapper = getWrapper(
                inserter,
                SelectedTrackerStub.withNoTracker(),
                configuration_bus,
                add_existing_section_bus,
            );

            wrapper.find("[data-test=add-freetext-section]").trigger("click");

            expectLastInsertedSectionToBeAPendingFreetextSection(inserter.getLastInsertedSection());
        });
    });
});

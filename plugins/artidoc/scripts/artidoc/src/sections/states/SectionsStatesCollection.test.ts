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

import { describe, it, expect, beforeEach } from "vitest";
import { ref } from "vue";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { getSectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { getSectionStateBuilder } from "@/sections/states/SectionStateBuilder";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

describe("SectionsStatesCollection", () => {
    let states_collection: SectionsStatesCollection,
        stored_sections: ReactiveStoredArtidocSection[];

    beforeEach(() => {
        states_collection = getSectionsStatesCollection(getSectionStateBuilder(true, ref([])));

        stored_sections = ReactiveStoredArtidocSectionStub.fromCollection([
            FreetextSectionFactory.create(),
            FreetextSectionFactory.pending(),
            ArtifactSectionFactory.create(),
            PendingArtifactSectionFactory.create(),
        ]);
    });

    it("createStateForSection() should create a state for the given section", () => {
        states_collection.createStateForSection(stored_sections[0]);

        expect(() => states_collection.getSectionState(stored_sections[0].value)).not.toThrow();
    });

    it("createAllSectionsStates() should create states for all the sections in the given collection ", () => {
        states_collection.createAllSectionsStates(stored_sections);

        stored_sections.forEach((section) => {
            expect(() => states_collection.getSectionState(section.value)).not.toThrow();
        });
    });

    it("destroyAll() should destroy all the states in the collection", () => {
        states_collection.createAllSectionsStates(stored_sections);
        states_collection.destroyAll();

        stored_sections.forEach((section) => {
            expect(() => states_collection.getSectionState(section.value)).toThrow();
        });
    });

    it("destroySectionState() should destroy the state of the given section", () => {
        states_collection.createStateForSection(stored_sections[0]);

        states_collection.destroySectionState(stored_sections[0].value);

        expect(() => states_collection.getSectionState(stored_sections[0].value)).toThrow();
    });

    it("has_at_least_one_section_in_edit_mode should be true at least one section is in edit mode", () => {
        states_collection.createAllSectionsStates(stored_sections);

        expect(states_collection.has_at_least_one_section_in_edit_mode.value).toBe(true);

        stored_sections.forEach((section) => {
            states_collection.getSectionState(section.value).is_section_in_edit_mode.value = false;
        });

        expect(states_collection.has_at_least_one_section_in_edit_mode.value).toBe(false);
    });

    it("has_at_least_one_section_in_edit_mode should be true when a pending section is added to the states collection", () => {
        expect(states_collection.has_at_least_one_section_in_edit_mode.value).toBe(false);
        states_collection.createStateForSection(
            ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.pending()),
        );

        expect(states_collection.has_at_least_one_section_in_edit_mode.value).toBe(true);
    });
});

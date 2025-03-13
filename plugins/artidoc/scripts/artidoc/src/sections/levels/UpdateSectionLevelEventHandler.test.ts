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
import type { MockInstance } from "vitest";
import type { HeadingsButton } from "@/toolbar/HeadingsButton";
import type { HeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import type { NumberSections } from "@/sections/levels/SectionsNumberer";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import { getHeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import { createHeadingButton } from "@/toolbar/create-heading-button";
import { getSectionsNumberer, LEVEL_1, LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import type { HandleUpdateSectionLevelEvent } from "@/sections/levels/UpdateSectionLevelEventHandler";
import { getUpdateSectionLevelEventHandler } from "@/sections/levels/UpdateSectionLevelEventHandler";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";

describe("UpdateSectionLevelEventHandler", () => {
    let headings_button: HeadingsButton,
        headings_button_state: HeadingsButtonState,
        sections_numberer: NumberSections,
        setSectionLevel: MockInstance,
        states_collection: SectionsStatesCollection,
        section: ReactiveStoredArtidocSection;

    beforeEach(() => {
        section = ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.create());

        headings_button = createHeadingButton(section);
        sections_numberer = getSectionsNumberer(
            SectionsCollectionStub.fromReactiveStoredArtifactSections([section]),
        );
        setSectionLevel = vi.spyOn(sections_numberer, "setSectionLevel");

        states_collection = SectionsStatesCollectionStub.fromReactiveStoredArtifactSections([
            section,
        ]);

        headings_button_state = getHeadingsButtonState();
        headings_button_state.activateButtonForSection(section);
    });

    const getHandler = (): HandleUpdateSectionLevelEvent => {
        return getUpdateSectionLevelEventHandler(
            headings_button,
            headings_button_state,
            states_collection,
            sections_numberer,
        );
    };

    const getSectionState = (): SectionState => states_collection.getSectionState(section.value);

    it("Given an event, When it is not an update-section-level, Then it should do nothing", () => {
        const event = new Event("click");

        getHandler().handle(event);

        expect(setSectionLevel).not.toHaveBeenCalled();
    });

    it("Given an update-section-level, When the level of the active section is already at the wanted level, then it should do nothing", () => {
        const event = new CustomEvent("update-section-level", {
            detail: {
                level: section.value.level,
            },
        });

        getHandler().handle(event);

        expect(setSectionLevel).not.toHaveBeenCalled();
        expect(getSectionState().has_title_level_been_changed.value).toBe(false);
        expect(headings_button.section?.level).toBe(section.value.level);
    });

    it("Given an update-section-level, When the level of the active section is NOT already at the wanted level, then it should update it", () => {
        section.value.level = LEVEL_1;
        getSectionState().initial_level.value = LEVEL_1;

        const event = new CustomEvent("update-section-level", {
            detail: {
                level: LEVEL_3,
            },
        });

        getHandler().handle(event);

        expect(setSectionLevel).toHaveBeenCalledOnce();
        expect(setSectionLevel).toHaveBeenCalledWith(section, LEVEL_3);
        expect(getSectionState().has_title_level_been_changed.value).toBe(true);
        expect(headings_button.section?.level).toBe(LEVEL_3);
    });

    it("Given an update-section-level, When the level of the section is set back to its original value, then has_title_level_been_changed should be false", () => {
        section.value.level = LEVEL_3;
        getSectionState().initial_level.value = LEVEL_1;

        const event = new CustomEvent("update-section-level", {
            detail: {
                level: LEVEL_1,
            },
        });

        getHandler().handle(event);

        expect(setSectionLevel).toHaveBeenCalledOnce();
        expect(setSectionLevel).toHaveBeenCalledWith(section, LEVEL_1);
        expect(getSectionState().has_title_level_been_changed.value).toBe(false);
        expect(headings_button.section?.level).toBe(LEVEL_1);
    });
});

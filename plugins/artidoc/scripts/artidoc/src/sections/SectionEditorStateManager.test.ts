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
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { BuildSectionState } from "@/sections/SectionStateBuilder";
import { getSectionStateBuilder } from "@/sections/SectionStateBuilder";
import { getSectionEditorStateManager } from "@/sections/SectionEditorStateManager";
import { getSectionHtmlDescription } from "@/helpers/get-section-html-description";

describe("SectionEditorStateManager", () => {
    let state_builder: BuildSectionState;

    beforeEach(() => {
        state_builder = getSectionStateBuilder(true, ref([]));
    });

    describe("setEditedContent", () => {
        it.each([
            ["artifact section", ArtifactSectionFactory.create()],
            ["pending artifact section", PendingArtifactSectionFactory.create()],
            ["freetext section", FreetextSectionFactory.create()],
            ["pending freetext section", FreetextSectionFactory.pending()],
        ])(
            "Given %s, When setEditedContent() is called, then it should set the edited title/description with the provided values",
            (section_type, artidoc_section) => {
                const section = ReactiveStoredArtidocSectionStub.fromSection(artidoc_section);
                const section_state = state_builder.forSection(section);
                const new_title = "new title";
                const new_description = "new description";

                getSectionEditorStateManager(section, section_state).setEditedContent(
                    new_title,
                    new_description,
                );

                expect(section_state.edited_title.value).toBe(new_title);
                expect(section_state.edited_description.value).toBe(new_description);
            },
        );

        it("When the new title is different than the original one, then is_editor_reset_needed and is_section_in_edit_mode should both be true", () => {
            const section = ReactiveStoredArtidocSectionStub.fromSection(
                FreetextSectionFactory.create(),
            );
            const section_state = state_builder.forSection(section);

            getSectionEditorStateManager(section, section_state).setEditedContent(
                "new title",
                getSectionHtmlDescription(section),
            );

            expect(section_state.is_editor_reset_needed.value).toBe(true);
            expect(section_state.is_section_in_edit_mode.value).toBe(true);
        });

        it("When the new description is different than the original one, then is_editor_reset_needed and is_section_in_edit_mode should both be true", () => {
            const section = ReactiveStoredArtidocSectionStub.fromSection(
                FreetextSectionFactory.create(),
            );
            const section_state = state_builder.forSection(section);

            getSectionEditorStateManager(section, section_state).setEditedContent(
                section.value.display_title,
                "new description",
            );

            expect(section_state.is_editor_reset_needed.value).toBe(true);
            expect(section_state.is_section_in_edit_mode.value).toBe(true);
        });
    });

    describe("resetContent", () => {
        it("should reset the edited title/description and is_section_in_edit_mode to their former values", () => {
            const section = ReactiveStoredArtidocSectionStub.fromSection(
                FreetextSectionFactory.create(),
            );
            const section_state = state_builder.forSection(section);
            const manager = getSectionEditorStateManager(section, section_state);

            manager.setEditedContent("new title", "new description");

            manager.resetContent();

            expect(section_state.edited_title.value).toBe(section.value.display_title);
            expect(section_state.edited_description.value).toBe(getSectionHtmlDescription(section));
            expect(section_state.is_section_in_edit_mode.value).toBe(false);
            // Should not be reset, otherwise the editor component won't reset its DOM
            expect(section_state.is_editor_reset_needed.value).toBe(true);
        });
    });

    describe("markEditorAsReset", () => {
        it("When the content of a section has been edited and reset, then it should reset is_editor_reset_needed back to false", () => {
            const section = ReactiveStoredArtidocSectionStub.fromSection(
                FreetextSectionFactory.create(),
            );
            const section_state = state_builder.forSection(section);
            const manager = getSectionEditorStateManager(section, section_state);

            manager.setEditedContent("new title", "new description");

            manager.resetContent();
            expect(section_state.is_editor_reset_needed.value).toBe(true);

            manager.markEditorAsReset();
            expect(section_state.is_editor_reset_needed.value).toBe(false);
        });
    });
});

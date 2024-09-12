/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { beforeEach, describe, expect, it } from "vitest";
import type { SectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { useSectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { SectionEditor } from "@/composables/useSectionEditor";

describe("useSectionEditorsStore", () => {
    describe("has_at_least_one_editor_opened", () => {
        let section_1: ArtidocSection;
        let section_2: ArtidocSection;
        let section_3: ArtidocSection;

        let editor_for_section_1: SectionEditor;
        let editor_for_section_2: SectionEditor;
        let editor_for_section_3: SectionEditor;

        let collection: SectionEditorsStore;

        beforeEach(() => {
            section_1 = ArtifactSectionFactory.create();
            section_2 = PendingArtifactSectionFactory.create();
            section_3 = ArtifactSectionFactory.create();

            editor_for_section_1 = SectionEditorStub.withEditableSection();
            editor_for_section_2 = SectionEditorStub.withEditableSection();
            editor_for_section_3 = SectionEditorStub.withEditableSection();

            collection = useSectionEditorsStore();

            collection.addEditor(section_1, editor_for_section_1);
            collection.addEditor(section_2, editor_for_section_2);
            collection.addEditor(section_3, editor_for_section_3);
        });

        it("should return false when no editor is open", () => {
            expect(collection.hasAtLeastOneEditorOpened()).toBe(false);
        });

        it("should return true when one editor is open", () => {
            editor_for_section_2.editor_state.is_section_in_edit_mode.value = true;

            expect(collection.hasAtLeastOneEditorOpened()).toBe(true);
        });

        it("should return true when two editors are open", () => {
            editor_for_section_2.editor_state.is_section_in_edit_mode.value = true;
            editor_for_section_3.editor_state.is_section_in_edit_mode.value = true;

            expect(collection.hasAtLeastOneEditorOpened()).toBe(true);
        });

        it("should return false as soon as no editor is open", () => {
            editor_for_section_2.editor_state.is_section_in_edit_mode.value = true;
            editor_for_section_3.editor_state.is_section_in_edit_mode.value = true;

            expect(collection.hasAtLeastOneEditorOpened()).toBe(true);

            editor_for_section_3.editor_state.is_section_in_edit_mode.value = false;

            expect(collection.hasAtLeastOneEditorOpened()).toBe(true);

            editor_for_section_2.editor_state.is_section_in_edit_mode.value = false;

            expect(collection.hasAtLeastOneEditorOpened()).toBe(false);
        });

        it("should return false if the last open editor is removed", () => {
            editor_for_section_2.editor_state.is_section_in_edit_mode.value = true;
            editor_for_section_3.editor_state.is_section_in_edit_mode.value = true;

            expect(collection.hasAtLeastOneEditorOpened()).toBe(true);

            collection.removeEditor(section_2);

            expect(collection.hasAtLeastOneEditorOpened()).toBe(true);

            collection.removeEditor(section_3);

            expect(collection.hasAtLeastOneEditorOpened()).toBe(false);
        });
    });
});

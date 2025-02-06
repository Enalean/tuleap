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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useSectionEditor } from "@/composables/useSectionEditor";
import { noop } from "@/helpers/noop";
import * as saveSection from "@/composables/useSaveSection";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { PendingSectionsReplacerStub } from "@/sections/stubs/PendingSectionsReplacerStub";
import { SectionsUpdaterStub } from "@/sections/stubs/SectionsUpdaterStub";
import { SectionsRemoverStub } from "@/sections/stubs/SectionsRemoverStub";
import { SectionsPositionsForSaveRetrieverStub } from "@/sections/stubs/SectionsPositionsForSaveRetrieverStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";
import { SectionEditorCloserStub } from "@/sections/stubs/SectionEditorCloserStub";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = FreetextSectionFactory.create();
const document_id = 105;

describe("useSectionEditor", () => {
    describe("editor_actions", () => {
        let save: Mock;
        let force_save: Mock;

        beforeEach(() => {
            save = vi.fn();
            force_save = vi.fn();

            vi.spyOn(saveSection, "default").mockReturnValue({
                save,
                forceSave: force_save,
            });
        });

        describe("save_editor", () => {
            it.each([
                ["artifact_section", artifact_section],
                ["freetext_section", freetext_section],
            ])("should save the editor content with %s", (name, section) => {
                const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(section);
                const section_state = SectionStateStub.withEditedContent();
                const { editor_actions } = useSectionEditor(
                    document_id,
                    reactive_section,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    SectionEditorCloserStub.withExpectedCall(),
                    noop,
                );
                editor_actions.saveEditor();

                expect(save).toHaveBeenCalledOnce();
            });
        });
        describe("force_save_editor", () => {
            it.each([
                ["artifact_section", artifact_section],
                ["freetext_section", freetext_section],
            ])("should force save the editor content %s", (name, section) => {
                const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(section);
                const section_state = SectionStateStub.withEditedContent();
                const { editor_actions } = useSectionEditor(
                    document_id,
                    reactive_section,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    SectionEditorCloserStub.withExpectedCall(),
                    noop,
                );
                editor_actions.forceSaveEditor();

                expect(force_save).toHaveBeenCalledOnce();
            });
        });
    });
});

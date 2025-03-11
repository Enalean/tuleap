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

import { describe, it, expect, vi } from "vitest";
import type { MockInstance } from "vitest";
import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";
import { getSectionEditorCloser } from "@/sections/editors/SectionEditorCloser";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { getSectionEditorStateManager } from "@/sections/editors/SectionEditorStateManager";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionsRemoverStub } from "@/sections/stubs/SectionsRemoverStub";
import { getFileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

describe("SectionEditorCloser", () => {
    let resetErrorStates: MockInstance,
        resetContent: MockInstance,
        setWaitingListAttachments: MockInstance,
        cancelSectionUploads: MockInstance,
        removeSection: MockInstance;

    const getSectionCloser = (section: ReactiveStoredArtidocSection): CloseSectionEditor => {
        const section_state = SectionStateStub.withEditedContent();
        const error_state_manager = SectionErrorManagerStub.withNoExpectedFault();
        const section_editor_state_manager = getSectionEditorStateManager(section, section_state);
        const section_attachments_manager = SectionAttachmentFilesManagerStub.forSection(
            section.value,
        );
        const file_uploads_collection = getFileUploadsCollection();
        const sections_remover = SectionsRemoverStub.withExpectedCall();

        resetErrorStates = vi.spyOn(error_state_manager, "resetErrorStates");
        resetContent = vi.spyOn(section_editor_state_manager, "resetContent");
        setWaitingListAttachments = vi.spyOn(
            section_attachments_manager,
            "setWaitingListAttachments",
        );
        cancelSectionUploads = vi.spyOn(file_uploads_collection, "cancelSectionUploads");
        removeSection = vi.spyOn(sections_remover, "removeSection");

        return getSectionEditorCloser(
            section,
            error_state_manager,
            section_editor_state_manager,
            section_attachments_manager,
            sections_remover,
            file_uploads_collection,
        );
    };

    const expectStatesToHaveBeenReset = (): void => {
        expect(resetErrorStates).toHaveBeenCalledOnce();
        expect(resetContent).toHaveBeenCalledOnce();

        expect(setWaitingListAttachments).toHaveBeenCalledOnce();
        expect(setWaitingListAttachments).toHaveBeenCalledWith([]);
    };

    const expectUploadsToHaveBeenCanceledForSection = (
        section: ReactiveStoredArtidocSection,
    ): void => {
        expect(cancelSectionUploads).toHaveBeenCalledOnce();
        expect(cancelSectionUploads).toHaveBeenCalledWith(section.value.id);
    };

    describe("closeEditor", () => {
        it("should reset the section's editor and error states and remove its waiting attachements", () => {
            getSectionCloser(
                ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.create()),
            ).closeEditor();

            expectStatesToHaveBeenReset();
        });
    });

    describe("closeAndCancelEditor", () => {
        it("should reset the section's editor and error states, remove its waiting attachements and cancel its ongoing uploads", () => {
            const section = ReactiveStoredArtidocSectionStub.fromSection(
                FreetextSectionFactory.create(),
            );
            getSectionCloser(section).closeAndCancelEditor();

            expectStatesToHaveBeenReset();
            expectUploadsToHaveBeenCanceledForSection(section);
        });

        it.each([
            [
                "freetext",
                ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.pending()),
            ],
            [
                "artifact",
                ReactiveStoredArtidocSectionStub.fromSection(
                    PendingArtifactSectionFactory.create(),
                ),
            ],
        ])(
            "when it is a pending %s section, it should also be removed",
            (section_type, section) => {
                getSectionCloser(section).closeAndCancelEditor();

                expectUploadsToHaveBeenCanceledForSection(section);
                expectStatesToHaveBeenReset();

                expect(removeSection).toHaveBeenCalledOnce();
                expect(removeSection).toHaveBeenCalledWith(section);
            },
        );
    });
});

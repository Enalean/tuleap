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
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import * as saveSection from "@/composables/useSaveSection";
import * as refreshSection from "@/composables/useRefreshSection";
import { SECTIONS_COLLECTION } from "@/sections/sections-collection-injection-key";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import { UploadFileStoreStub } from "@/helpers/stubs/UploadFileStoreStub";
import type { UploadFileStoreType } from "@/stores/useUploadFileStore";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { PendingSectionsReplacerStub } from "@/sections/stubs/PendingSectionsReplacerStub";
import { SectionsUpdaterStub } from "@/sections/stubs/SectionsUpdaterStub";
import { SectionsRemoverStub } from "@/sections/stubs/SectionsRemoverStub";
import { SectionsPositionsForSaveRetrieverStub } from "@/sections/stubs/SectionsPositionsForSaveRetrieverStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { noop } from "@/helpers/noop";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";
import { getSectionEditorStateManager } from "@/sections/SectionEditorStateManager";
import { getSectionHtmlDescription } from "@/helpers/get-section-html-description";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = FreetextSectionFactory.create();
const document_id = 105;

describe("useSectionEditor", () => {
    let store_stub: SectionsCollection;
    let upload_file_store_stub: UploadFileStoreType;

    beforeEach(() => {
        store_stub = SectionsCollectionStub.withSections([]);
        upload_file_store_stub = {
            ...UploadFileStoreStub.uploadInProgress(),
            cancelSectionUploads: vi.fn(),
        };
        mockStrictInject([
            [CAN_USER_EDIT_DOCUMENT, true],
            [SECTIONS_COLLECTION, store_stub],
            [UPLOAD_FILE_STORE, upload_file_store_stub],
        ]);
    });

    describe("editor_actions", () => {
        let save: Mock;
        let force_save: Mock;
        let refresh_section: Mock;
        beforeEach(() => {
            save = vi.fn();
            force_save = vi.fn();
            refresh_section = vi.fn();

            vi.spyOn(saveSection, "default").mockReturnValue({
                save,
                forceSave: force_save,
            });
            vi.spyOn(refreshSection, "useRefreshSection").mockReturnValue({
                refreshSection: refresh_section,
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
                    getSectionEditorStateManager(reactive_section, section_state),
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
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
                    getSectionEditorStateManager(reactive_section, section_state),
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    noop,
                );
                editor_actions.forceSaveEditor();

                expect(force_save).toHaveBeenCalledOnce();
            });
        });
        describe("refresh_section", () => {
            it.each([
                ["artifact_section", artifact_section],
                ["freetext_section", freetext_section],
            ])("should refresh the editor content with %s", (name, section) => {
                const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(section);
                const section_state = SectionStateStub.withEditedContent();

                const { editor_actions } = useSectionEditor(
                    document_id,
                    reactive_section,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    getSectionEditorStateManager(reactive_section, section_state),
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    noop,
                );
                editor_actions.refreshSection();

                expect(refresh_section).toHaveBeenCalledOnce();
            });
        });
        describe("cancel_editor", () => {
            it.each([
                ["artifact_section", artifact_section],
                ["freetext_section", freetext_section],
            ])("should cancel edit mode with %s", (name, section) => {
                const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(section);
                const section_state = SectionStateStub.inEditMode();

                const editor_content_manager = getSectionEditorStateManager(
                    reactive_section,
                    section_state,
                );
                const { editor_actions } = useSectionEditor(
                    document_id,
                    reactive_section,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    editor_content_manager,
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    noop,
                );
                expect(section_state.is_section_in_edit_mode.value).toBe(true);
                editor_content_manager.setEditedContent(
                    "the title changed",
                    "the description changed",
                );

                editor_actions.cancelEditor();

                expect(section_state.is_section_in_edit_mode.value).toBe(false);

                expect(section_state.edited_title.value).toBe(reactive_section.value.display_title);
                expect(section_state.edited_description.value).toBe(
                    getSectionHtmlDescription(reactive_section),
                );
            });

            it.each([
                ["artifact_section", artifact_section],
                ["freetext_section", freetext_section],
            ])("should cancel file uploads with %s", (name, section) => {
                const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(section);
                const section_state = SectionStateStub.withEditedContent();
                const { editor_actions } = useSectionEditor(
                    document_id,
                    reactive_section,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    getSectionEditorStateManager(reactive_section, section_state),
                    SectionAttachmentFilesManagerStub.forSection(section),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    noop,
                );
                editor_actions.cancelEditor();

                expect(upload_file_store_stub.cancelSectionUploads).toHaveBeenCalledOnce();
            });

            it("should remove the section if it is a pending one", () => {
                const sections_remover = SectionsRemoverStub.withExpectedCall();
                const section_state = SectionStateStub.inEditMode();
                const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(
                    PendingArtifactSectionFactory.create(),
                );

                const { editor_actions } = useSectionEditor(
                    document_id,
                    reactive_section,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    getSectionEditorStateManager(reactive_section, section_state),
                    SectionAttachmentFilesManagerStub.forSection(reactive_section.value),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    sections_remover,
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    noop,
                );
                editor_actions.cancelEditor();
                expect(sections_remover.getLastRemovedSection()).toStrictEqual(
                    reactive_section.value,
                );
            });
        });
    });
});

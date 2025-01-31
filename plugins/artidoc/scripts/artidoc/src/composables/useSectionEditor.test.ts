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
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import * as saveSection from "@/composables/useSaveSection";
import * as refreshSection from "@/composables/useRefreshSection";
import * as editorContent from "@/composables/useEditorSectionContent";
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

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = FreetextSectionFactory.create();
const merge_artifacts = vi.fn();
const set_waiting_list = vi.fn();

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
            [DOCUMENT_ID, 1],
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
                const { editor_actions } = useSectionEditor(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    merge_artifacts,
                    set_waiting_list,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    () => {},
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
                const { editor_actions } = useSectionEditor(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    merge_artifacts,
                    set_waiting_list,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    () => {},
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
                const { editor_actions } = useSectionEditor(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    merge_artifacts,
                    set_waiting_list,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    () => {},
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
                const section_state = SectionStateStub.inEditMode();
                const { editor_actions, editor_section_content } = useSectionEditor(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    merge_artifacts,
                    set_waiting_list,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    () => {},
                );
                expect(section_state.is_section_in_edit_mode.value).toBe(true);
                editor_section_content.inputSectionContent(
                    "the title changed",
                    "the description changed",
                );

                editor_actions.cancelEditor();

                expect(section_state.is_section_in_edit_mode.value).toBe(false);

                if (name === "artifact_section") {
                    expect(editor_section_content.getReadonlyDescription()).toBe(
                        artifact_section.description.value,
                    );
                    return;
                }

                expect(editor_section_content.getReadonlyDescription()).toBe(
                    freetext_section.description,
                );
            });
            it.each([
                ["artifact_section", artifact_section],
                ["freetext_section", freetext_section],
            ])("should cancel file uploads with %s", (name, section) => {
                const { editor_actions } = useSectionEditor(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    merge_artifacts,
                    set_waiting_list,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    SectionsRemoverStub.withNoExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    vi.fn(),
                );
                editor_actions.cancelEditor();

                expect(upload_file_store_stub.cancelSectionUploads).toHaveBeenCalledOnce();
            });

            it("should remove the section if it is a pending one", () => {
                const sections_remover = SectionsRemoverStub.withExpectedCall();
                const section = ReactiveStoredArtidocSectionStub.fromSection(
                    PendingArtifactSectionFactory.create(),
                );

                const { editor_actions } = useSectionEditor(
                    section,
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    merge_artifacts,
                    set_waiting_list,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withNoExpectedCall(),
                    sections_remover,
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    () => {},
                );
                editor_actions.cancelEditor();
                expect(sections_remover.getLastRemovedSection()).toStrictEqual(section.value);
            });
        });
    });

    describe("editor_section_content", () => {
        it.each([
            ["artifact_section", artifact_section],
            ["freetext_section", freetext_section],
        ])("should return the editor content %s", (name, section) => {
            const editor_content = vi.spyOn(editorContent, "useEditorSectionContent");

            const { editor_section_content } = useSectionEditor(
                ReactiveStoredArtidocSectionStub.fromSection(section),
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                merge_artifacts,
                set_waiting_list,
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withNoExpectedCall(),
                SectionsRemoverStub.withNoExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                () => {},
            );
            expect(editor_content).toHaveBeenCalledOnce();
            expect(editor_section_content).toBeDefined();
        });
    });
});

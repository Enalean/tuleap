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
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { SectionsStore } from "@/stores/useSectionsStore";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import * as saveSection from "@/composables/useSaveSection";
import * as refreshSection from "@/composables/useRefreshSection";
import * as editorError from "@/composables/useEditorErrors";
import * as editorContent from "@/composables/useEditorSectionContent";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import type { SectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { EDITORS_COLLECTION, useSectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { EDITOR_CHOICE } from "@/helpers/editor-choice";
import { ref } from "vue";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import { UploadFileStoreStub } from "@/helpers/stubs/UploadFileStoreStub";
import type { UploadFileStoreType } from "@/stores/useUploadFileStore";

const section = ArtifactSectionFactory.create();
const merge_artifacts = vi.fn();
const set_waiting_list = vi.fn();

const remove_section = vi.fn();
describe("useSectionEditor", () => {
    let store_stub: SectionsStore;
    let editors_collection: SectionEditorsStore;
    let upload_file_store_stub: UploadFileStoreType;

    beforeEach(() => {
        store_stub = {
            ...InjectedSectionsStoreStub.withLoadedSections([]),
            removeSection: remove_section,
        };
        editors_collection = useSectionEditorsStore();
        upload_file_store_stub = {
            ...UploadFileStoreStub.uploadInProgress(),
            cancelSectionUploads: vi.fn(),
        };
        mockStrictInject([
            [CAN_USER_EDIT_DOCUMENT, true],
            [DOCUMENT_ID, 1],
            [SECTIONS_STORE, store_stub],
            [EDITORS_COLLECTION, editors_collection],
            [EDITOR_CHOICE, { is_prose_mirror: ref(false) }],
            [UPLOAD_FILE_STORE, upload_file_store_stub],
        ]);
    });

    describe("editor_state", () => {
        it("should return editor states", () => {
            const { editor_state } = useSectionEditor(
                section,
                merge_artifacts,
                set_waiting_list,
                ref(true),
                () => {},
            );

            expect(editor_state.is_image_upload_allowed.value).toEqual(true);
            expect(editor_state.is_save_allowed.value).toEqual(false);
            expect(editor_state.is_section_editable.value).toEqual(true);
            expect(editor_state.is_section_in_edit_mode.value).toEqual(false);
            expect(editor_state.isJustRefreshed()).toEqual(false);
            expect(editor_state.isJustSaved()).toEqual(false);
            expect(editor_state.isBeingSaved()).toEqual(false);
        });
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
                isBeingSaved: vi.fn(),
                isJustSaved: vi.fn(),
            });
            vi.spyOn(refreshSection, "useRefreshSection").mockReturnValue({
                refreshSection: refresh_section,
                isJustRefreshed: vi.fn(),
            });
        });

        describe("enable_editor", () => {
            it("should enable editor", () => {
                const { editor_actions, editor_state } = useSectionEditor(
                    section,
                    merge_artifacts,
                    set_waiting_list,
                    ref(false),
                    () => {},
                );
                expect(editor_state.is_section_in_edit_mode.value).toEqual(false);
                expect(editors_collection.hasAtLeastOneEditorOpened()).toBe(false);

                editor_actions.enableEditor();

                expect(editor_state.is_section_in_edit_mode.value).toEqual(true);
                expect(editors_collection.hasAtLeastOneEditorOpened()).toBe(true);
            });
        });

        describe("save_editor", () => {
            it("should save the editor content", () => {
                const { editor_actions } = useSectionEditor(
                    section,
                    merge_artifacts,
                    set_waiting_list,
                    ref(false),
                    () => {},
                );
                editor_actions.saveEditor();

                expect(save).toHaveBeenCalledOnce();
            });
        });
        describe("force_save_editor", () => {
            it("should force save the editor content", () => {
                const { editor_actions } = useSectionEditor(
                    section,
                    merge_artifacts,
                    set_waiting_list,
                    ref(false),
                    () => {},
                );
                editor_actions.forceSaveEditor();

                expect(force_save).toHaveBeenCalledOnce();
            });
        });
        describe("refresh_section", () => {
            it("should refresh the editor content", () => {
                const { editor_actions } = useSectionEditor(
                    section,
                    merge_artifacts,
                    set_waiting_list,
                    ref(false),
                    () => {},
                );
                editor_actions.refreshSection();

                expect(refresh_section).toHaveBeenCalledOnce();
            });
        });
        describe("cancel_editor", () => {
            it("should cancel edit mode", () => {
                const { editor_actions, editor_state, editor_section_content } = useSectionEditor(
                    section,
                    merge_artifacts,
                    set_waiting_list,
                    ref(false),
                    () => {},
                );
                editor_state.is_section_in_edit_mode.value = true;
                editor_section_content.inputCurrentDescription("the description changed");

                editor_actions.cancelEditor(null);

                expect(editor_state.is_section_in_edit_mode.value).toBe(false);
                expect(editor_section_content.getReadonlyDescription()).toBe(
                    section.description.value,
                );
                expect(store_stub.removeSection).not.toHaveBeenCalled();
            });
            it("should cancel file uploads", () => {
                const { editor_actions } = useSectionEditor(
                    section,
                    merge_artifacts,
                    set_waiting_list,
                    ref(false),
                    vi.fn(),
                );
                editor_actions.cancelEditor(null);

                expect(upload_file_store_stub.cancelSectionUploads).toHaveBeenCalledOnce();
            });

            describe("when prose mirror is disable", () => {
                it("should remove the section if it is a pending one", () => {
                    const { editor_actions } = useSectionEditor(
                        PendingArtifactSectionFactory.create(),
                        merge_artifacts,
                        set_waiting_list,
                        ref(false),
                        () => {},
                    );
                    editor_actions.cancelEditor(null);

                    expect(store_stub.removeSection).toHaveBeenCalled();
                });
            });
        });
    });
    describe("editor_error", () => {
        it("should enable editor", () => {
            const editor_error_handler = vi.spyOn(editorError, "useEditorErrors");

            const { editor_error } = useSectionEditor(
                section,
                merge_artifacts,
                set_waiting_list,
                ref(false),
                () => {},
            );
            expect(editor_error_handler).toHaveBeenCalledOnce();
            expect(editor_error).toBeDefined();
        });
    });
    describe("editor_section_content", () => {
        it("should return the editor content", () => {
            const editor_content = vi.spyOn(editorContent, "useEditorSectionContent");

            const { editor_section_content } = useSectionEditor(
                section,
                merge_artifacts,
                set_waiting_list,
                ref(false),
                () => {},
            );
            expect(editor_content).toHaveBeenCalledOnce();
            expect(editor_section_content).toBeDefined();
        });
    });
});

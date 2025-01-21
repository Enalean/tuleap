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

import { beforeEach, describe, expect, it, vi } from "vitest";
import useSaveSection from "@/composables/useSaveSection";
import type { EditorErrors } from "@/composables/useEditorErrors";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { flushPromises } from "@vue/test-utils";
import * as rest_querier from "@/helpers/rest-querier";
import * as latest from "@/helpers/get-section-in-its-latest-version";
import { okAsync } from "neverthrow";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { PendingSectionsReplacerStub } from "@/helpers/stubs/PendingSectionsReplacerStub";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = FreetextSectionFactory.create();

describe("useSaveSection", () => {
    let editor_errors: EditorErrors;
    let callbacks: Parameters<typeof useSaveSection>[2];

    beforeEach(() => {
        editor_errors = {
            ...SectionEditorStub.withoutEditableSection().editor_error,
            handleError: vi.fn(),
        };
        callbacks = {
            updateSectionStore: vi.fn(),
            updateCurrentSection: vi.fn(),
            closeEditor: vi.fn(),
            setEditMode: vi.fn(),
            getSectionPositionForSave: vi.fn(),
            mergeArtifactAttachments: vi.fn(),
        };
        mockStrictInject([
            [CAN_USER_EDIT_DOCUMENT, true],
            [DOCUMENT_ID, 1],
        ]);
    });
    describe("forceSave", () => {
        it("should save artifact section", async () => {
            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putArtifact")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = useSaveSection(
                editor_errors,
                PendingSectionsReplacerStub.withNoExpectedCall(),
                callbacks,
            );

            forceSave(artifact_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });
        it("should save freetext section", async () => {
            const mock_put_freetext_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = useSaveSection(
                editor_errors,
                PendingSectionsReplacerStub.withNoExpectedCall(),
                callbacks,
            );

            forceSave(freetext_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_freetext_description).toHaveBeenCalledOnce();
        });
    });
    describe("save", () => {
        beforeEach(() => {
            vi.spyOn(latest, "getSectionInItsLatestVersion").mockReturnValue(
                okAsync(PendingArtifactSectionFactory.create()),
            );
        });
        describe("when the new description and title are the same as the original one", () => {
            it("should disable edit mode with artifact section", () => {
                const { save } = useSaveSection(
                    editor_errors,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    callbacks,
                );

                save(artifact_section, {
                    description: artifact_section.description.value,
                    title: artifact_section.display_title,
                });

                expect(callbacks.setEditMode).toBeCalledWith(false);
            });
            it("should disable edit mode with freetext section", () => {
                const { save } = useSaveSection(
                    editor_errors,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    callbacks,
                );

                save(freetext_section, {
                    description: freetext_section.description,
                    title: freetext_section.display_title,
                });

                expect(callbacks.setEditMode).toBeCalledWith(false);
            });
            it("should not save artifact section", async () => {
                const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");
                const { save } = useSaveSection(
                    editor_errors,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    callbacks,
                );

                save(artifact_section, {
                    description: artifact_section.description.value,
                    title: artifact_section.display_title,
                });

                await flushPromises();

                expect(mock_put_artifact_description).not.toHaveBeenCalledOnce();
            });

            it("should not save freetext section", async () => {
                const mock_put_freetext_description = vi.spyOn(rest_querier, "putSection");
                const { save } = useSaveSection(
                    editor_errors,
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    callbacks,
                );

                save(freetext_section, {
                    description: freetext_section.description,
                    title: freetext_section.display_title,
                });

                await flushPromises();

                expect(mock_put_freetext_description).not.toHaveBeenCalledOnce();
            });
        });

        it("should save artifact section", async () => {
            const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");

            const { save } = useSaveSection(
                editor_errors,
                PendingSectionsReplacerStub.withNoExpectedCall(),
                callbacks,
            );

            save(artifact_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });

        it("should save freetext section", async () => {
            const mock_put_freetext_description = vi.spyOn(rest_querier, "putSection");

            const { save } = useSaveSection(
                editor_errors,
                PendingSectionsReplacerStub.withNoExpectedCall(),
                callbacks,
            );

            save(freetext_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_freetext_description).toHaveBeenCalledOnce();
        });

        it("When the saved section is a pending artifact section, Then it should create it and replace it by the saved one.", async () => {
            const replacer = PendingSectionsReplacerStub.withExpectedCall();
            const { save } = useSaveSection(editor_errors, replacer, callbacks);

            const pending_section = PendingArtifactSectionFactory.create();
            const createArtifact = vi
                .spyOn(rest_querier, "postArtifact")
                .mockReturnValue(okAsync({ id: 105 }));
            const createArtifactSection = vi
                .spyOn(rest_querier, "createArtifactSection")
                .mockReturnValue(okAsync(ArtifactSectionFactory.override(pending_section)));

            save(pending_section, { title: "Pending section", description: "Save me" });
            await flushPromises();

            expect(createArtifact).toHaveBeenCalledOnce();
            expect(createArtifactSection).toHaveBeenCalledOnce();
            expect(replacer.hasBeenCalled()).toBe(true);
        });

        it("When the saved section is a pending freetext section, Then it should create it and replace it by the saved one.", async () => {
            const replacer = PendingSectionsReplacerStub.withExpectedCall();
            const { save } = useSaveSection(editor_errors, replacer, callbacks);

            const pending_section = FreetextSectionFactory.pending();
            const createFreetextSection = vi
                .spyOn(rest_querier, "createFreetextSection")
                .mockReturnValue(okAsync(FreetextSectionFactory.override(pending_section)));

            save(pending_section, { title: "Pending section", description: "Save me" });
            await flushPromises();

            expect(createFreetextSection).toHaveBeenCalledOnce();
            expect(replacer.hasBeenCalled()).toBe(true);
        });
    });
});

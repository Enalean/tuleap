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

const section = ArtifactSectionFactory.create();

describe("useSaveSection", () => {
    let editor_errors: EditorErrors;
    let callbacks: Parameters<typeof useSaveSection>[1];

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
            replacePendingByArtifactSection: vi.fn(),
            getSectionPositionForSave: vi.fn(),
            mergeArtifactAttachments: vi.fn(),
        };
        mockStrictInject([
            [CAN_USER_EDIT_DOCUMENT, true],
            [DOCUMENT_ID, 1],
        ]);
    });
    describe("forceSave", () => {
        it("should save section", async () => {
            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putArtifact")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = useSaveSection(editor_errors, callbacks);

            forceSave(section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });
    });
    describe("save", () => {
        beforeEach(() => {
            vi.spyOn(latest, "getSectionInItsLatestVersion").mockReturnValue(
                okAsync(PendingArtifactSectionFactory.create()),
            );
        });
        describe("when the new description and title are the same as the original one", () => {
            it("should disable edit mode", () => {});
            it("should not save section", async () => {
                const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");
                const { save } = useSaveSection(editor_errors, callbacks);

                save(section, {
                    description: section.description.value,
                    title: section.display_title,
                });

                await flushPromises();

                expect(mock_put_artifact_description).not.toHaveBeenCalledOnce();
            });
        });
        it("should save section", async () => {
            const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");

            const { save } = useSaveSection(editor_errors, callbacks);

            save(section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });
    });
});

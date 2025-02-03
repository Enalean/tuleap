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

import { describe, expect, it, vi } from "vitest";
import useSaveSection from "@/composables/useSaveSection";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { flushPromises } from "@vue/test-utils";
import * as rest_querier from "@/helpers/rest-querier";
import { okAsync } from "neverthrow";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { PendingSectionsReplacerStub } from "@/sections/stubs/PendingSectionsReplacerStub";
import { noop } from "@/helpers/noop";
import { SectionsUpdaterStub } from "@/sections/stubs/SectionsUpdaterStub";
import { SectionsPositionsForSaveRetrieverStub } from "@/sections/stubs/SectionsPositionsForSaveRetrieverStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = FreetextSectionFactory.create();
const document_id = 105;

describe("useSaveSection", () => {
    describe("forceSave", () => {
        it("should save artifact section", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section));

            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putArtifact")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = useSaveSection(
                document_id,
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(artifact_section),
                noop,
            );

            forceSave(artifact_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });
        it("should save freetext section", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section));

            const mock_put_freetext_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = useSaveSection(
                document_id,
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(freetext_section),
                noop,
            );

            forceSave(freetext_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_freetext_description).toHaveBeenCalledOnce();
        });
    });
    describe("save", () => {
        describe("when the new description and title are the same as the original one", () => {
            it("should disable edit mode with artifact section", () => {
                vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section));

                const section_state = SectionStateStub.inEditMode();
                const { save } = useSaveSection(
                    document_id,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    SectionAttachmentFilesManagerStub.forSection(artifact_section),
                    noop,
                );

                save(artifact_section, {
                    description: artifact_section.description.value,
                    title: artifact_section.display_title,
                });

                expect(section_state.is_section_in_edit_mode.value).toBe(false);
            });
            it("should disable edit mode with freetext section", () => {
                vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section));

                const section_state = SectionStateStub.inEditMode();
                const { save } = useSaveSection(
                    document_id,
                    section_state,
                    SectionErrorManagerStub.withNoExpectedFault(),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    SectionAttachmentFilesManagerStub.forSection(freetext_section),
                    noop,
                );

                save(freetext_section, {
                    description: freetext_section.description,
                    title: freetext_section.display_title,
                });

                expect(section_state.is_section_in_edit_mode.value).toBe(false);
            });
            it("should not save artifact section", async () => {
                vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section));

                const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");
                const { save } = useSaveSection(
                    document_id,
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    SectionAttachmentFilesManagerStub.forSection(artifact_section),
                    noop,
                );

                save(artifact_section, {
                    description: artifact_section.description.value,
                    title: artifact_section.display_title,
                });

                await flushPromises();

                expect(mock_put_artifact_description).not.toHaveBeenCalledOnce();
            });

            it("should not save freetext section", async () => {
                vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section));

                const mock_put_freetext_description = vi.spyOn(rest_querier, "putSection");
                const { save } = useSaveSection(
                    document_id,
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withNoExpectedFault(),
                    PendingSectionsReplacerStub.withNoExpectedCall(),
                    SectionsUpdaterStub.withExpectedCall(),
                    SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                    SectionAttachmentFilesManagerStub.forSection(freetext_section),
                    noop,
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
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section));

            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putArtifact")
                .mockReturnValue(okAsync({} as Response));

            const { save } = useSaveSection(
                document_id,
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(artifact_section),
                noop,
            );

            save(artifact_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });

        it("should save freetext section", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section));

            const mock_put_freetext_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync({} as Response));

            const { save } = useSaveSection(
                document_id,
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(freetext_section),
                noop,
            );

            save(freetext_section, { description: "new description", title: "new title" });
            await flushPromises();

            expect(mock_put_freetext_description).toHaveBeenCalledOnce();
        });

        it("When the saved section is a pending artifact section, Then it should create it and replace it by the saved one.", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section));

            const replacer = PendingSectionsReplacerStub.withExpectedCall();
            const { save } = useSaveSection(
                document_id,
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                replacer,
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(artifact_section),
                noop,
            );

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
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section));

            const replacer = PendingSectionsReplacerStub.withExpectedCall();
            const { save } = useSaveSection(
                document_id,
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                replacer,
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(freetext_section),
                noop,
            );

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

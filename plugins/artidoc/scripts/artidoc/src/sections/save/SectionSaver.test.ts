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
import { flushPromises } from "@vue/test-utils";
import { okAsync } from "neverthrow";
import * as rest_querier from "@/helpers/rest-querier";
import { getSectionSaver } from "@/sections/save/SectionSaver";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { PendingSectionsReplacerStub } from "@/sections/stubs/PendingSectionsReplacerStub";
import { SectionsUpdaterStub } from "@/sections/stubs/SectionsUpdaterStub";
import { SectionsPositionsForSaveRetrieverStub } from "@/sections/stubs/SectionsPositionsForSaveRetrieverStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionEditorCloserStub } from "@/sections/stubs/SectionEditorCloserStub";

const artifact_section = ReactiveStoredArtidocSectionStub.fromSection(
    ArtifactSectionFactory.create(),
);
const freetext_section = ReactiveStoredArtidocSectionStub.fromSection(
    FreetextSectionFactory.create(),
);
const document_id = 105;

describe("SectionSaver", () => {
    describe("forceSave", () => {
        it("should save artifact section", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section.value));

            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = getSectionSaver(
                document_id,
                artifact_section,
                SectionStateStub.withEditedContent(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(artifact_section.value),
                SectionEditorCloserStub.withExpectedCall(),
            );

            forceSave();
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });
        it("should save freetext section", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section.value));
            const mock_put_freetext_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync(new Response()));

            const { forceSave } = getSectionSaver(
                document_id,
                freetext_section,
                SectionStateStub.withEditedContent(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(freetext_section.value),
                SectionEditorCloserStub.withExpectedCall(),
            );

            forceSave();
            await flushPromises();

            expect(mock_put_freetext_description).toHaveBeenCalledOnce();
        });
    });
    describe("save", () => {
        it("should save artifact sections", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section.value));

            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync({} as Response));

            const { save } = getSectionSaver(
                document_id,
                artifact_section,
                SectionStateStub.withEditedContent(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(artifact_section.value),
                SectionEditorCloserStub.withExpectedCall(),
            );

            save();
            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();
        });

        it("should save freetext sections", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section.value));

            const mock_put_freetext_description = vi
                .spyOn(rest_querier, "putSection")
                .mockReturnValue(okAsync({} as Response));

            const { save } = getSectionSaver(
                document_id,
                freetext_section,
                SectionStateStub.withEditedContent(),
                SectionErrorManagerStub.withNoExpectedFault(),
                PendingSectionsReplacerStub.withNoExpectedCall(),
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(freetext_section.value),
                SectionEditorCloserStub.withExpectedCall(),
            );

            save();
            await flushPromises();

            expect(mock_put_freetext_description).toHaveBeenCalledOnce();
        });

        it("When the saved section is a pending artifact section, Then it should create it and replace it by the saved one.", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(artifact_section.value));

            const replacer = PendingSectionsReplacerStub.withExpectedCall();
            const { save } = getSectionSaver(
                document_id,
                ReactiveStoredArtidocSectionStub.fromSection(
                    PendingArtifactSectionFactory.create(),
                ),
                SectionStateStub.withEditedContent(),
                SectionErrorManagerStub.withNoExpectedFault(),
                replacer,
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(artifact_section.value),
                SectionEditorCloserStub.withExpectedCall(),
            );

            const pending_section = PendingArtifactSectionFactory.create();
            const createArtifact = vi
                .spyOn(rest_querier, "createSection")
                .mockReturnValue(okAsync(ArtifactSectionFactory.override(pending_section)));

            save();
            await flushPromises();

            expect(createArtifact).toHaveBeenCalledOnce();
            expect(replacer.hasBeenCalled()).toBe(true);
        });

        it("When the saved section is a pending freetext section, Then it should create it and replace it by the saved one.", async () => {
            vi.spyOn(rest_querier, "getSection").mockReturnValue(okAsync(freetext_section.value));

            const replacer = PendingSectionsReplacerStub.withExpectedCall();
            const { save } = getSectionSaver(
                document_id,
                ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.pending()),
                SectionStateStub.withEditedContent(),
                SectionErrorManagerStub.withNoExpectedFault(),
                replacer,
                SectionsUpdaterStub.withExpectedCall(),
                SectionsPositionsForSaveRetrieverStub.withDefaultPositionAtTheEnd(),
                SectionAttachmentFilesManagerStub.forSection(freetext_section.value),
                SectionEditorCloserStub.withExpectedCall(),
            );

            const pending_section = FreetextSectionFactory.pending();
            const createFreetextSection = vi
                .spyOn(rest_querier, "createSection")
                .mockReturnValue(okAsync(FreetextSectionFactory.override(pending_section)));

            save();
            await flushPromises();

            expect(createFreetextSection).toHaveBeenCalledOnce();
            expect(replacer.hasBeenCalled()).toBe(true);
        });
    });
});

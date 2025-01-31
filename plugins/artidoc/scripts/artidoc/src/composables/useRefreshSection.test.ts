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
import type { MockedFunction } from "vitest";
import { useRefreshSection } from "@/composables/useRefreshSection";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { EditorErrors } from "@/composables/useEditorErrors";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import { errAsync, okAsync } from "neverthrow";
import * as rest from "@/helpers/rest-querier";
import { flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SectionsUpdaterStub } from "@/sections/stubs/SectionsUpdaterStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = ArtifactSectionFactory.create();
const editor_errors: EditorErrors = {
    ...SectionEditorStub.build().editor_error,
    handleError: vi.fn(),
};

describe("useRefreshSection", () => {
    let closeEditor: MockedFunction<() => void>;

    beforeEach(() => {
        closeEditor = vi.fn();
    });

    describe("refresh_section", () => {
        describe.each([
            ["artifact", artifact_section, ArtifactSectionFactory.create()],
            ["freetext", freetext_section, FreetextSectionFactory.create()],
        ])("when the api call get section is successful with %s", (name, section, new_section) => {
            beforeEach(() => {
                vi.spyOn(rest, "getSection").mockReturnValue(okAsync(new_section));
            });

            it(`should call update section from editor with ${name}`, async () => {
                const updater = SectionsUpdaterStub.withExpectedCall();
                const { refreshSection } = useRefreshSection(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    editor_errors,
                    updater,
                    closeEditor,
                );
                refreshSection();

                await flushPromises();

                expect(updater.getLastUpdatedSection()).toStrictEqual(new_section);
            });
            it(`should close editor with ${name}`, async () => {
                const updater = SectionsUpdaterStub.withExpectedCall();
                const { refreshSection } = useRefreshSection(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    editor_errors,
                    updater,
                    closeEditor,
                );

                refreshSection();
                await flushPromises();

                expect(updater.getLastUpdatedSection()).toStrictEqual(new_section);
                expect(closeEditor).toHaveBeenCalledOnce();
            });
            describe("when the api call returns an artifact section", () => {
                it("should call update section from store", async () => {
                    const updater = SectionsUpdaterStub.withExpectedCall();
                    const { refreshSection } = useRefreshSection(
                        ReactiveStoredArtidocSectionStub.fromSection(
                            ArtifactSectionFactory.create(),
                        ),
                        SectionStateStub.inEditMode(),
                        editor_errors,
                        updater,
                        closeEditor,
                    );

                    refreshSection();
                    await flushPromises();

                    expect(updater.getLastUpdatedSection()).toStrictEqual(new_section);
                });
            });
        });
        describe.each([
            ["artifact", artifact_section],
            ["freetext", freetext_section],
        ])("when the api call get section trigger an error with %s", (name, section) => {
            const fault = Fault.fromMessage("an error");

            beforeEach(() => {
                vi.spyOn(rest, "getSection").mockReturnValue(errAsync(fault));
            });

            it(`should call handle error from editor with ${name}`, async () => {
                const { refreshSection } = useRefreshSection(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    editor_errors,
                    SectionsUpdaterStub.withNoExpectedCall(),
                    closeEditor,
                );
                refreshSection();
                await flushPromises();

                expect(editor_errors.handleError).toHaveBeenCalledWith(fault);
            });
            it(`should update is_outdated with ${name}`, async () => {
                const { refreshSection } = useRefreshSection(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    SectionStateStub.inEditMode(),
                    editor_errors,
                    SectionsUpdaterStub.withNoExpectedCall(),
                    closeEditor,
                );
                editor_errors.is_outdated.value = true;
                expect(editor_errors.is_outdated.value).toBe(true);

                refreshSection();
                await flushPromises();

                expect(editor_errors.is_outdated.value).toBe(false);
            });
        });
    });
});

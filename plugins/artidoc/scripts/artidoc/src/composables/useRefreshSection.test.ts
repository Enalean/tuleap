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
import { useRefreshSection } from "@/composables/useRefreshSection";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { EditorErrors } from "@/composables/useEditorErrors";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import { errAsync, okAsync } from "neverthrow";
import * as rest from "@/helpers/rest-querier";
import { flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = ArtifactSectionFactory.create();
const editor_errors: EditorErrors = {
    ...SectionEditorStub.withoutEditableSection().editor_error,
    handleError: vi.fn(),
};

describe("useRefreshSection", () => {
    let callbacks: Parameters<typeof useRefreshSection>[2];
    beforeEach(() => {
        callbacks = {
            closeEditor: vi.fn(),
            updateSectionStore: vi.fn(),
            updateCurrentSection: vi.fn(),
        };
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
                const { refreshSection } = useRefreshSection(section, editor_errors, callbacks);
                refreshSection();

                await flushPromises();

                expect(callbacks.updateCurrentSection).toHaveBeenCalledWith(new_section);
            });
            it(`should close editor with ${name}`, async () => {
                const { refreshSection } = useRefreshSection(section, editor_errors, callbacks);

                refreshSection();
                await flushPromises();

                expect(callbacks.closeEditor).toHaveBeenCalledOnce();
            });
            describe("when the api call returns an artifact section", () => {
                it("should call update section from store", async () => {
                    const { refreshSection } = useRefreshSection(
                        ArtifactSectionFactory.create(),
                        editor_errors,
                        callbacks,
                    );

                    refreshSection();
                    await flushPromises();

                    expect(callbacks.updateSectionStore).toHaveBeenCalledWith(new_section);
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
                const { refreshSection } = useRefreshSection(section, editor_errors, callbacks);
                refreshSection();
                await flushPromises();

                expect(editor_errors.handleError).toHaveBeenCalledWith(fault);
            });
            it(`should update is_outdated with ${name}`, async () => {
                const { refreshSection } = useRefreshSection(section, editor_errors, callbacks);
                editor_errors.is_outdated.value = true;
                expect(editor_errors.is_outdated.value).toBe(true);

                refreshSection();
                await flushPromises();

                expect(editor_errors.is_outdated.value).toBe(false);
            });
        });
    });
});

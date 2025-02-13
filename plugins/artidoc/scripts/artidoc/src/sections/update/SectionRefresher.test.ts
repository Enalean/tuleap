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
import { getSectionRefresher } from "@/sections/update/SectionRefresher";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { errAsync, okAsync } from "neverthrow";
import * as rest from "@/helpers/rest-querier";
import { flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SectionsUpdaterStub } from "@/sections/stubs/SectionsUpdaterStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { SectionEditorCloserStub } from "@/sections/stubs/SectionEditorCloserStub";

const artifact_section = ArtifactSectionFactory.create();
const freetext_section = ArtifactSectionFactory.create();

describe("SectionRefresher", () => {
    describe.each([
        ["artifact", artifact_section, ArtifactSectionFactory.create()],
        ["freetext", freetext_section, FreetextSectionFactory.create()],
    ])("when the api call get section is successful with %s", (name, section, new_section) => {
        beforeEach(() => {
            vi.spyOn(rest, "getSection").mockReturnValue(okAsync(new_section));
        });

        it(`should call update section from editor with ${name}`, async () => {
            const updater = SectionsUpdaterStub.withExpectedCall();
            const { refreshSection } = getSectionRefresher(
                ReactiveStoredArtidocSectionStub.fromSection(section),
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withNoExpectedFault(),
                updater,
                SectionEditorCloserStub.withExpectedCall(),
            );
            refreshSection();

            await flushPromises();

            expect(updater.getLastUpdatedSection()).toStrictEqual(new_section);
        });
        it(`should close editor with ${name}`, async () => {
            const updater = SectionsUpdaterStub.withExpectedCall();
            const editor_closer = SectionEditorCloserStub.withExpectedCall();
            const { refreshSection } = getSectionRefresher(
                ReactiveStoredArtidocSectionStub.fromSection(section),
                SectionStateStub.inEditMode(),
                SectionErrorManagerStub.withExpectedFault(),
                updater,
                editor_closer,
            );

            refreshSection();
            await flushPromises();

            expect(updater.getLastUpdatedSection()).toStrictEqual(new_section);
            expect(editor_closer.hasEditorBeenClosed()).toBe(true);
        });
        describe("when the api call returns an artifact section", () => {
            it("should call update section from store", async () => {
                const updater = SectionsUpdaterStub.withExpectedCall();
                const { refreshSection } = getSectionRefresher(
                    ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create()),
                    SectionStateStub.inEditMode(),
                    SectionErrorManagerStub.withExpectedFault(),
                    updater,
                    SectionEditorCloserStub.withExpectedCall(),
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
            const error_manager = SectionErrorManagerStub.withExpectedFault();

            const { refreshSection } = getSectionRefresher(
                ReactiveStoredArtidocSectionStub.fromSection(section),
                SectionStateStub.inEditMode(),
                error_manager,
                SectionsUpdaterStub.withNoExpectedCall(),
                SectionEditorCloserStub.withExpectedCall(),
            );
            refreshSection();
            await flushPromises();

            expect(error_manager.getLastHandledFault()).toBe(fault);
        });
        it(`should update is_outdated with ${name}`, async () => {
            const section_state = SectionStateStub.inEditMode();
            const { refreshSection } = getSectionRefresher(
                ReactiveStoredArtidocSectionStub.fromSection(section),
                section_state,
                SectionErrorManagerStub.withExpectedFault(),
                SectionsUpdaterStub.withNoExpectedCall(),
                SectionEditorCloserStub.withExpectedCall(),
            );
            section_state.is_outdated.value = true;
            expect(section_state.is_outdated.value).toBe(true);

            refreshSection();
            await flushPromises();

            expect(section_state.is_outdated.value).toBe(false);
        });
    });
});

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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import type { StoredArtidocSection } from "@/sections/SectionsCollection";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SectionsRemoverStub } from "@/sections/stubs/SectionsRemoverStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { SectionErrorManagerStub } from "@/sections/stubs/SectionErrorManagerStub";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";
import { getSectionDeletor } from "@/sections/remove/SectionDeletor";
import { noop } from "@/helpers/noop";

describe("SectionDeletor", () => {
    let section_to_delete: StoredArtidocSection;

    beforeEach(() => {
        section_to_delete = CreateStoredSections.fromArtidocSection(
            FreetextSectionFactory.create(),
        );
    });

    it("Given a section, then it should delete it", async () => {
        const remove_section = SectionsRemoverStub.withExpectedCall();
        const deletor = getSectionDeletor(
            section_to_delete,
            SectionStateStub.inEditMode(),
            SectionErrorManagerStub.withNoExpectedFault(),
            remove_section,
            noop,
        );

        deletor.deleteSection();
        await flushPromises();

        expect(remove_section.getLastRemovedSection()).toBe(section_to_delete);
    });

    it("When an error occurres, and the section was in edit mode, then the error state manager should handle it", async () => {
        const fault = Fault.fromMessage("Nope");
        const error_state_manager = SectionErrorManagerStub.withExpectedFault();
        const remove_section = SectionsRemoverStub.withExpectedFault(fault);
        const deletor = getSectionDeletor(
            section_to_delete,
            SectionStateStub.inEditMode(),
            error_state_manager,
            remove_section,
            noop,
        );

        deletor.deleteSection();
        await flushPromises();

        expect(error_state_manager.getLastHandledFault()).toBe(fault);
    });

    it("When an error occurres, and the section was not in edit mode, then a global error should be raised", async () => {
        const fault = Fault.fromMessage("Nope");
        const remove_section = SectionsRemoverStub.withExpectedFault(fault);
        const raise_delete_error_callback = vi.fn();

        const deletor = getSectionDeletor(
            section_to_delete,
            SectionStateStub.withDefaults(),
            SectionErrorManagerStub.withNoExpectedFault(),
            remove_section,
            raise_delete_error_callback,
        );

        deletor.deleteSection();
        await flushPromises();

        expect(raise_delete_error_callback).toHaveBeenCalledOnce();
        expect(raise_delete_error_callback).toHaveBeenCalledWith(fault.toString());
    });
});

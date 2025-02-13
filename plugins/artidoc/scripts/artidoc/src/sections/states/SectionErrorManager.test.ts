/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import type { ManageErrorState } from "@/sections/states/SectionErrorManager";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import { OutdatedSectionFault } from "@/helpers/get-section-in-its-latest-version";
import { TuleapAPIFaultStub } from "@/helpers/stubs/TuleapAPIFaultStub";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { getSectionErrorManager } from "@/sections/states/SectionErrorManager";

describe("SectionErrorManager", () => {
    let error_manager: ManageErrorState, section_state: SectionState;

    beforeEach(() => {
        section_state = SectionStateStub.inEditMode();
        error_manager = getSectionErrorManager(section_state);
    });

    describe("handleError", () => {
        describe("when outdated error is triggered", () => {
            it("should set is_outdated", () => {
                expect(section_state.is_outdated.value).toBe(false);

                error_manager.handleError(OutdatedSectionFault.build());

                expect(section_state.is_outdated.value).toBe(true);
            });
        });

        describe("when not found error is triggered", () => {
            it("should set is_not_found", () => {
                expect(section_state.is_not_found.value).toBe(false);

                error_manager.handleError(TuleapAPIFaultStub.fromCodeAndMessage(404, "Not found"));

                expect(section_state.is_not_found.value).toBe(true);
            });
        });

        describe("when an error is triggered", () => {
            it("should set an error message", () => {
                error_manager.handleError(
                    TuleapAPIFaultStub.fromCodeAndMessage(500, "Explosion !!"),
                );

                expect(section_state.error_message.value).toBe("Explosion !!");
            });
        });
    });

    describe("resetErrorStates", () => {
        it("should reset the error states to default value", () => {
            section_state.is_outdated.value = true;
            section_state.is_not_found.value = true;
            section_state.is_in_error.value = true;

            expect(section_state.is_outdated.value).toBe(true);
            expect(section_state.is_not_found.value).toBe(true);
            expect(section_state.is_in_error.value).toBe(true);

            error_manager.resetErrorStates();

            expect(section_state.is_outdated.value).toBe(false);
            expect(section_state.is_not_found.value).toBe(false);
            expect(section_state.is_in_error.value).toBe(false);
        });
    });
});

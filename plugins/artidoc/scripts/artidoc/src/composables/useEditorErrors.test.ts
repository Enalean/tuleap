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

import { describe, expect, it } from "vitest";
import { useEditorErrors } from "@/composables/useEditorErrors";
import { OutdatedSectionFault } from "@/helpers/get-section-in-its-latest-version";
import { TuleapAPIFaultStub } from "@/helpers/stubs/TuleapAPIFaultStub";

describe("useEditorErrors", () => {
    describe("handleError", () => {
        describe("when outdated error is triggered", () => {
            it("should set is_outdated", () => {
                const { handleError, is_outdated } = useEditorErrors();

                expect(is_outdated.value).toBe(false);

                handleError(OutdatedSectionFault.build());

                expect(is_outdated.value).toBe(true);
            });
        });
        describe("when not found error is triggered", () => {
            it("should set is_not_found", () => {
                const { handleError, is_not_found } = useEditorErrors();

                expect(is_not_found.value).toBe(false);

                handleError(TuleapAPIFaultStub.fromCodeAndMessage(404, "Not found"));

                expect(is_not_found.value).toBe(true);
            });
        });
        describe("when an error is triggered", () => {
            it("should set an error message", () => {
                const { handleError, getErrorMessage } = useEditorErrors();

                handleError(TuleapAPIFaultStub.fromCodeAndMessage(500, "Explosion !!"));

                expect(getErrorMessage()).toBe("Explosion !!");
            });
        });
    });
    describe("resetErrorStates", () => {
        it("should reset the error states to default value", () => {
            const { resetErrorStates, is_outdated, is_in_error, is_not_found } = useEditorErrors();
            is_outdated.value = true;
            is_not_found.value = true;
            is_in_error.value = true;

            expect(is_outdated.value).toBe(true);
            expect(is_not_found.value).toBe(true);
            expect(is_in_error.value).toBe(true);

            resetErrorStates();

            expect(is_outdated.value).toBe(false);
            expect(is_not_found.value).toBe(false);
            expect(is_in_error.value).toBe(false);
        });
    });
});

/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import * as mutations from "./mutations.js";

describe("mutations", () => {
    describe("discardUnsavedReport()", () => {
        it("When I discard the unsaved report, then feedbacks will be hidden and the report will be set to saved", () => {
            const state = {
                is_report_saved: false,
                error_message: "Bad request",
                success_message: "Hurrah",
            };

            mutations.discardUnsavedReport(state);

            expect(state.is_report_saved).toBe(true);
            expect(state.success_message).toBeNull();
            expect(state.error_message).toBeNull();
        });
    });

    describe("switchToReadingMode()", () => {
        it("Given a report 'saved' state, then the feedbacks will be hidden, reading mode will be true and the report 'saved' state will be updated", () => {
            const state = {
                reading_mode: false,
                is_report_saved: false,
                error_message: "Bad request",
                success_message: "Yay",
            };

            mutations.switchToReadingMode(state, { saved_state: true });

            expect(state.reading_mode).toBe(true);
            expect(state.is_report_saved).toBe(true);
            expect(state.success_message).toBeNull();
            expect(state.error_message).toBeNull();
        });
    });

    describe("switchToWritingMode()", () => {
        it("the feedbacks will be hidden and reading mode will be false", () => {
            const state = {
                reading_mode: true,
                error_message: "Forbidden",
                success_message: "Huzzah",
            };

            mutations.switchToWritingMode(state);

            expect(state.reading_mode).toBe(false);
            expect(state.success_message).toBeNull();
            expect(state.error_message).toBeNull();
        });
    });

    describe("switchReportToSaved()", () => {
        it("Given a success message, then the success message will be set, the error message will be hidden and the report will be marked as saved", () => {
            const state = {
                is_report_saved: false,
                error_message: "impeccant",
                success_message: null,
            };

            mutations.switchReportToSaved(state, "Great success");

            expect(state.is_report_saved).toBe(true);
            expect(state.error_message).toBeNull();
            expect(state.success_message).toEqual("Great success");
        });
    });
});

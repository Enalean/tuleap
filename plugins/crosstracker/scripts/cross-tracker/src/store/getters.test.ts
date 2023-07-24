/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import * as getters from "./getters";
import type { State, TrackerInfo } from "../type";

describe("Store getters", () => {
    describe("shouldDisplayExportButton", () => {
        it("Given that user is not widget administrator, then he should be able to export results", () => {
            const invalid_trackers: Array<TrackerInfo> = [];
            const state: State = {
                is_user_admin: false,
                error_message: null,
                invalid_trackers: invalid_trackers,
            } as State;

            const result = getters.should_display_export_button(state);

            expect(result).toBe(true);
        });

        it("Given user is widget administrator and no trackers of query are invalid, then he should be able to export results", () => {
            const invalid_trackers: Array<TrackerInfo> = [];
            const state: State = {
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
                error_message: null,
            } as State;

            const result = getters.should_display_export_button(state);

            expect(result).toBe(true);
        });

        it("Given user is widget administrator and at least one tracker is invalid, then he should not be able to export results", () => {
            const invalid_trackers: Array<TrackerInfo> = [
                {
                    id: 1,
                    label: "My invalid tracker",
                },
            ];
            const state: State = {
                is_user_admin: true,
                error_message: null,
                invalid_trackers: invalid_trackers,
            } as State;

            const result = getters.should_display_export_button(state);

            expect(result).toBe(false);
        });

        it("Given report query has an error, nobody should be able to export result", () => {
            const state: State = {
                error_message: "An error occurred",
            } as State;

            const result = getters.should_display_export_button(state);

            expect(result).toBe(false);
        });
    });
});

/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { ArtifactField, DryRunState, Project, RootState, Tracker } from "./types";
import { setFromTracker } from "../from-tracker-presenter";
import {
    fully_migrated_fields_count,
    not_migrated_fields_count,
    partially_migrated_fields_count,
    sorted_projects,
    tracker_list_with_disabled_from,
} from "./getters";

describe("getters", () => {
    it("sorted_projects should return the projects alphabetically sorted", () => {
        const projects: Project[] = [
            {
                id: 105,
                label: "Scrum",
            },
            {
                id: 106,
                label: "Git",
            },
            {
                id: 107,
                label: "Kanban",
            },
        ];

        expect(sorted_projects({ projects } as RootState).map(({ id }) => id)).toStrictEqual([
            106, 107, 105,
        ]);
    });

    it("tracker_list_with_disabled_from should return the trackers with the current one disabled", () => {
        const current_tracker_id = 11;
        const trackers: Tracker[] = [
            {
                id: 10,
                label: "Tasks",
                disabled: false,
            },
            {
                id: current_tracker_id,
                label: "User stories",
                disabled: false,
            },
        ];

        setFromTracker(current_tracker_id, "stories", "daphne-blue", 126, 102);

        const with_disabled_from = tracker_list_with_disabled_from({ trackers } as RootState);

        expect(with_disabled_from).toHaveLength(trackers.length);
        expect(with_disabled_from[0].disabled).toBe(false);
        expect(with_disabled_from[1].disabled).toBe(true);
    });

    describe("fields count", () => {
        let dry_run_fields: DryRunState;

        const a_field: ArtifactField = {
            field_id: 10,
            label: "A field",
            name: "a_field",
        };

        beforeEach(() => {
            dry_run_fields = {
                fields_not_migrated: [],
                fields_partially_migrated: [],
                fields_migrated: [],
            };
        });

        it("not_migrated_fields_count should return number of not migrated fields", () => {
            dry_run_fields.fields_not_migrated.push(a_field, a_field, a_field);

            expect(not_migrated_fields_count({ dry_run_fields } as RootState)).toBe(3);
        });

        it("partially_migrated_fields_count should return number of partially migrated fields", () => {
            dry_run_fields.fields_partially_migrated.push(a_field, a_field, a_field);

            expect(partially_migrated_fields_count({ dry_run_fields } as RootState)).toBe(3);
        });

        it("fully_migrated_fields_count should return number of fully migrated fields", () => {
            dry_run_fields.fields_migrated.push(a_field, a_field, a_field);

            expect(fully_migrated_fields_count({ dry_run_fields } as RootState)).toBe(3);
        });
    });
});

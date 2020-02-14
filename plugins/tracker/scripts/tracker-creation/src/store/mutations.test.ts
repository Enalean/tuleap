/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import * as mutations from "./mutations";
import { State } from "./type";

describe("mutations", () => {
    describe("setSelectedTrackerTemplate", () => {
        let state: State;

        beforeEach(() => {
            state = {
                project_templates: [
                    {
                        project_name: "Scrum template",
                        tracker_list: [
                            { id: "11", name: "Bugs" },
                            { id: "12", name: "Releases" },
                            { id: "13", name: "Requests" }
                        ]
                    },
                    {
                        project_name: "Default template",
                        tracker_list: [{ id: "14", name: "Activities" }]
                    }
                ],
                selected_tracker_template: null
            } as State;
        });

        it("Given a tracker id, it finds the tracker in the state, then it stores it", () => {
            mutations.setSelectedTrackerTemplate(state, "13");

            expect(state.selected_tracker_template).toEqual({ id: "13", name: "Requests" });
        });

        it("throws an error when the tracker has not been found", () => {
            expect(() => mutations.setSelectedTrackerTemplate(state, "15")).toThrowError(
                "not found"
            );
        });
    });
});

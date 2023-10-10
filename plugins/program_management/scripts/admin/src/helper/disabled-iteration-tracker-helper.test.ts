/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { disabledIterationTrackersFromProgramIncrementAndPlannableTrackers } from "./disabled-iteration-tracker-helper";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("disabledIterationTrackerHelper", () => {
    describe("disabledIterationTrackersFromProgramIncrementAndPlannableTrackers", () => {
        it("When iteration tracker selector does not exist, Then error is thrown", () => {
            const doc = createDocument();

            expect(() =>
                disabledIterationTrackersFromProgramIncrementAndPlannableTrackers(doc, "", []),
            ).toThrow("Iteration tracker element does not exist");
        });

        it("When a value is selected, Then it's disabled in iteration tracker options", () => {
            const iteration_tracker_selector = document.createElement("select");
            iteration_tracker_selector.id = "admin-configuration-iteration-tracker";
            iteration_tracker_selector.options.add(new Option("PI", "808"));
            iteration_tracker_selector.options.add(new Option("Feature", "1000"));
            iteration_tracker_selector.options.add(new Option("Bug", "1200"));
            iteration_tracker_selector.options.add(new Option("Sprint", "1400"));

            const doc = createDocument();
            doc.body.appendChild(iteration_tracker_selector);

            disabledIterationTrackersFromProgramIncrementAndPlannableTrackers(doc, "808", [
                "1000",
                "1200",
            ]);

            expect(iteration_tracker_selector.options[0].disabled).toBeTruthy();
            expect(iteration_tracker_selector.options[1].disabled).toBeTruthy();
            expect(iteration_tracker_selector.options[2].disabled).toBeTruthy();
            expect(iteration_tracker_selector.options[3].disabled).toBeFalsy();
        });
    });
});

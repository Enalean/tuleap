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

import { disabledPlannableTrackers } from "./disabled-plannable-tracker-helper";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("disabledPlannableTrackerHelper", () => {
    describe("disabledPlannableTrackers", () => {
        it("When plannable trackers selector does not exist, Then error is thrown", () => {
            const selector = document.createElement("select");
            const doc = createDocument();

            expect(() => disabledPlannableTrackers(doc, selector)).toThrow(
                "Plannable trackers element does not exist",
            );
        });

        it("When a value is selected, Then it's disabled in plannable tracker options", () => {
            const pi_selector = document.createElement("select");
            pi_selector.options.add(new Option("PI", "808", false, true));
            pi_selector.options.add(new Option("Feature", "1000"));

            const plannable_trackers_selector = document.createElement("select");
            plannable_trackers_selector.id = "admin-configuration-plannable-trackers";
            plannable_trackers_selector.options.add(new Option("PI", "808"));
            plannable_trackers_selector.options.add(new Option("Feature", "1000"));

            const doc = createDocument();
            doc.body.appendChild(plannable_trackers_selector);

            disabledPlannableTrackers(doc, pi_selector);

            expect(plannable_trackers_selector.options[0].disabled).toBeTruthy();
            expect(plannable_trackers_selector.options[1].disabled).toBeFalsy();
        });
    });
});

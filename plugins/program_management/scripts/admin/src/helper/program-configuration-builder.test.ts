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

import { buildProgramConfiguration } from "./program-configuration-builder";

describe("program-configuration-builder", function () {
    describe("buildProgramConfiguration", function () {
        it("should return configuration with selected value", function () {
            const configuration = buildProgramConfiguration(
                createDocumentWithSelectorWithoutEmptyField(),
                100
            );

            expect(configuration.program_id).toEqual(100);
            expect(configuration.plannable_tracker_ids).toEqual([9, 10]);
            expect(configuration.program_increment_tracker_id).toEqual(8);
            expect(configuration.permissions.can_prioritize_features).toEqual(["100_3", "150"]);
        });
    });
});

function createDocumentWithSelectorWithoutEmptyField(): Document {
    const doc = document.implementation.createHTMLDocument();

    const select_pi = document.createElement("select");
    select_pi.id = "admin-configuration-program-increment-tracker";
    select_pi.add(new Option("PI", "8", false, true));

    const select_plannable_trackers = document.createElement("select");
    select_plannable_trackers.id = "admin-configuration-plannable-trackers";
    select_plannable_trackers.setAttribute("multiple", "multiple");
    select_plannable_trackers.add(new Option("Features", "9", false, true));
    select_plannable_trackers.add(new Option("Bugs", "10", false, true));

    const select_permissions = document.createElement("select");
    select_permissions.id = "admin-configuration-permission-prioritize";
    select_permissions.setAttribute("multiple", "multiple");
    select_permissions.add(new Option("Member", "100_3", false, true));
    select_permissions.add(new Option("Member", "150", false, true));

    doc.body.appendChild(select_pi);
    doc.body.appendChild(select_plannable_trackers);
    doc.body.appendChild(select_permissions);

    return doc;
}

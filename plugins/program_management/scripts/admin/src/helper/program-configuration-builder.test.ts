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
        it("should throw error when no input label", () => {
            expect(() =>
                buildProgramConfiguration(createDocumentWithSelectorWithoutLabels(), 100),
            ).toThrow("No admin-configuration-program-increment-label-section input");
        });
        it("should throw error when no iteration selector and feature flag is true", () => {
            expect(() =>
                buildProgramConfiguration(
                    createDocumentWithSelectorWithoutIterationSelector(),
                    100,
                ),
            ).toThrow("admin-configuration-iteration-tracker element does not exist");
        });
        it("should return configuration with selected value and not empty iteration object", function () {
            const configuration = buildProgramConfiguration(
                createDocumentWithSelectorWithoutEmptyField("8"),
                100,
            );

            expect(configuration.program_id).toBe(100);
            expect(configuration.plannable_tracker_ids).toEqual([9, 10]);
            expect(configuration.program_increment_tracker_id).toBe(8);
            expect(configuration.permissions.can_prioritize_features).toEqual(["100_3", "150"]);
            expect(configuration.program_increment_label).toBe("PI");
            expect(configuration.iteration?.iteration_tracker_id).toBe(8);
            expect(configuration.iteration?.iteration_label).toBe("An Iteration");
            expect(configuration.iteration?.iteration_sub_label).toBe("");
        });
        it("should return configuration with empty iteration object when no tracker iteration was selected", function () {
            const configuration = buildProgramConfiguration(
                createDocumentWithSelectorWithoutEmptyField(""),
                100,
            );
            expect(configuration.program_id).toBe(100);
            expect(configuration.plannable_tracker_ids).toEqual([9, 10]);
            expect(configuration.program_increment_tracker_id).toBe(8);
            expect(configuration.permissions.can_prioritize_features).toEqual(["100_3", "150"]);
            expect(configuration.program_increment_label).toBe("PI");
            expect(configuration.program_increment_sub_label).toBe("");
            expect(configuration.iteration).toBeNull();
        });
    });
});

function createDocumentWithSelectorWithoutEmptyField(iteration_tracker_id: string): Document {
    const doc = createDocumentWithSelectorWithoutIterationSelector();

    const iteration_selector = document.createElement("select");
    iteration_selector.id = "admin-configuration-iteration-tracker";
    iteration_selector.add(new Option("Sprint", iteration_tracker_id, false, true));

    const iteration_label = document.createElement("input");
    iteration_label.id = "admin-configuration-iteration-label-section";
    iteration_label.value = "An Iteration";
    const iteration_sub_label = document.createElement("input");
    iteration_sub_label.id = "admin-configuration-iteration-sub-label-section";

    doc.body.appendChild(iteration_selector);
    doc.body.appendChild(iteration_label);
    doc.body.appendChild(iteration_sub_label);

    return doc;
}

function createDocumentWithSelectorWithoutIterationSelector(): Document {
    const doc = createDocumentWithSelectorWithoutLabels();

    const program_increment_label = document.createElement("input");
    program_increment_label.id = "admin-configuration-program-increment-label-section";
    program_increment_label.value = "PI";
    const program_increment_sub_label = document.createElement("input");
    program_increment_sub_label.id = "admin-configuration-program-increment-sub-label-section";

    doc.body.appendChild(program_increment_label);
    doc.body.appendChild(program_increment_sub_label);

    return doc;
}

function createDocumentWithSelectorWithoutLabels(): Document {
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

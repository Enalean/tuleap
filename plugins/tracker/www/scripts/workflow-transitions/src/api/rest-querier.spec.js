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

import { restore, rewire$patch } from "tlp-fetch";
import {
    createWorkflowTransitions,
    resetWorkflowTransitions,
    updateTransitionRulesEnforcement
} from "../api/rest-querier.js";

describe("Rest queries:", () => {
    let patch;
    let json_result = {};

    beforeEach(() => {
        patch = jasmine.createSpy("patch");
        patch.and.returnValue(
            Promise.resolve({
                json: () => json_result
            })
        );
        rewire$patch(patch);
    });

    afterEach(restore);

    describe("createWorkflowTransitions()", () => {
        beforeEach(() => {
            createWorkflowTransitions(1, 9);
        });

        it("calls PATCH", () =>
            expect(patch).toHaveBeenCalledWith(
                "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22set_transitions_rules%22%3A%7B%22field_id%22%3A9%7D%7D%7D"
            ));
    });

    describe("resetWorkflowTransitions()", () => {
        beforeEach(() => {
            resetWorkflowTransitions(1);
        });

        it("calls PATCH", () =>
            expect(patch).toHaveBeenCalledWith(
                "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22delete_transitions_rules%22%3Atrue%7D%7D"
            ));
    });

    describe("updateTransitionRulesEnforcement()", () => {
        let returned_value;

        beforeEach(async () => {
            return (returned_value = await updateTransitionRulesEnforcement(1, true));
        });

        it("calls PATCH", () =>
            expect(patch).toHaveBeenCalledWith(
                "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22set_transitions_rules%22%3A%7B%22is_used%22%3Atrue%7D%7D%7D"
            ));
        it("returns PATCH response", () => {
            expect(returned_value).toBe(json_result);
        });
    });
});

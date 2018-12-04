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

import { restore, rewire$patch, rewire$post } from "tlp-fetch";
import {
    createTransition,
    createWorkflowTransitions,
    resetWorkflowTransitions,
    updateTransitionRulesEnforcement
} from "../api/rest-querier.js";

describe("Rest queries:", () => {
    afterEach(restore);

    describe("for PATCH actions:", () => {
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

        describe("createWorkflowTransitions()", () => {
            beforeEach(() => {
                createWorkflowTransitions(1, 9);
            });

            it("calls api tracker", () =>
                expect(patch).toHaveBeenCalledWith(
                    "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22set_transitions_rules%22%3A%7B%22field_id%22%3A9%7D%7D%7D"
                ));
        });

        describe("resetWorkflowTransitions()", () => {
            beforeEach(() => {
                resetWorkflowTransitions(1);
            });

            it("calls api tracker", () =>
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

    describe("for POST actions", () => {
        let post;
        const new_transition = {
            id: 4,
            from_id: 2,
            to_id: 3
        };
        const headers = {
            "content-type": "application/json"
        };

        beforeEach(() => {
            post = jasmine.createSpy("post");
            post.and.returnValue(
                Promise.resolve({
                    json: () => new_transition
                })
            );
            rewire$post(post);
        });

        describe("createTransition()", () => {
            let response;
            beforeEach(async () => {
                response = await createTransition(1, 2, 3);
            });

            it("calls api tracker_workflow_transitions", () => {
                expect(post).toHaveBeenCalledWith("/api/tracker_workflow_transitions", {
                    headers,
                    body: '{"tracker_id":1,"from_id":2,"to_id":3}'
                });
            });
            it("returns created transition", () => expect(response).toEqual(new_transition));

            describe("when transition source is new artifact", () => {
                beforeEach(async () => {
                    response = await createTransition(1, null, 3);
                });

                it("send 0 as from_id", () => {
                    expect(post).toHaveBeenCalledWith("/api/tracker_workflow_transitions", {
                        headers,
                        body: '{"tracker_id":1,"from_id":0,"to_id":3}'
                    });
                });
            });
        });
    });
});

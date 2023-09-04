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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    createTransition,
    createWorkflowTransitions,
    resetWorkflowTransitions,
    updateTransitionRulesEnforcement,
    getTransition,
    getUserGroups,
    patchTransition,
    getPostActions,
    putPostActions,
    deactivateLegacyTransitions,
    changeWorkflowMode,
} from "./rest-querier.js";
import { create, createList } from "../support/factories.js";

describe("Rest queries:", () => {
    const json_headers = {
        "content-type": "application/json",
    };

    describe("for GET actions:", () => {
        let get;
        let return_json;
        let result;

        beforeEach(() => {
            get = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(get, { return_json });
        });

        describe("getTransition()", () => {
            beforeEach(() => {
                getTransition(266);
            });

            it("calls transition API", () =>
                expect(get).toHaveBeenCalledWith("/api/tracker_workflow_transitions/266"));
        });

        describe("getUserGroups()", () => {
            beforeEach(async () => {
                result = await getUserGroups(266);
            });

            it("calls project API", () =>
                expect(get).toHaveBeenCalledWith(
                    "/api/projects/266/user_groups?query=%7B%22with_system_user_groups%22%3Atrue%7D",
                ));
            it("returns the user groups", () => expect(result).toEqual(return_json));
        });

        describe("getPostActions()", () => {
            beforeEach(async () => {
                result = await getPostActions(266);
            });

            it("calls project API", () =>
                expect(get).toHaveBeenCalledWith("/api/tracker_workflow_transitions/266/actions"));
            it("returns the post actions", () => expect(result).toEqual(return_json));
        });
    });

    describe("for PUT actions:", () => {
        let put;
        let json_result = {};

        beforeEach(() => {
            put = jest.spyOn(tlp_fetch, "put").mockReturnValue(
                Promise.resolve({
                    json: () => json_result,
                }),
            );
        });
        describe("putPostActions()", () => {
            const actions = createList("post_action", 2, "presented");

            beforeEach(() => putPostActions(9, actions));

            it("calls PUT actions with json headers", () =>
                expect(put).toHaveBeenCalledWith(
                    "/api/tracker_workflow_transitions/9/actions",
                    expect.objectContaining({ headers: json_headers }),
                ));

            it("does not send unique_ids", () => {
                let serialized_post_actions = put.mock.calls[put.mock.calls.length - 1][1].body;
                expect(serialized_post_actions).not.toMatch(/"unique_id":/);
            });
        });
    });

    describe("for PATCH actions:", () => {
        let patch;
        let json_result = {};

        beforeEach(() => {
            patch = jest.spyOn(tlp_fetch, "patch").mockReturnValue(
                Promise.resolve({
                    json: () => json_result,
                }),
            );
        });

        describe("createWorkflowTransitions()", () => {
            beforeEach(() => {
                createWorkflowTransitions(1, 9);
            });

            it("calls api tracker", () =>
                expect(patch).toHaveBeenCalledWith(
                    "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22set_transitions_rules%22%3A%7B%22field_id%22%3A9%7D%7D%7D",
                ));
        });

        describe("resetWorkflowTransitions()", () => {
            beforeEach(() => {
                resetWorkflowTransitions(1);
            });

            it("calls api tracker", () =>
                expect(patch).toHaveBeenCalledWith(
                    "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22delete_transitions_rules%22%3Atrue%7D%7D",
                ));
        });

        describe("updateTransitionRulesEnforcement()", () => {
            let returned_value;

            beforeEach(async () => {
                return (returned_value = await updateTransitionRulesEnforcement(1, true));
            });

            it("calls PATCH", () =>
                expect(patch).toHaveBeenCalledWith(
                    "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22set_transitions_rules%22%3A%7B%22is_used%22%3Atrue%7D%7D%7D",
                ));
            it("returns PATCH response", () => {
                expect(returned_value).toBe(json_result);
            });
        });

        describe("deactivateLegacyTransitions()", () => {
            beforeEach(() => {
                deactivateLegacyTransitions(1);
            });

            it("calls api tracker", () =>
                expect(patch).toHaveBeenCalledWith(
                    "/api/trackers/1?query=%7B%22workflow%22%3A%7B%22is_legacy%22%3Afalse%7D%7D",
                ));
        });

        describe("changeWorkflowMode()", () => {
            it("calls tracker API with given workflow mode", () => {
                changeWorkflowMode(49, true);
                expect(patch).toHaveBeenCalledWith(
                    "/api/trackers/49?query=%7B%22workflow%22%3A%7B%22is_advanced%22%3Atrue%7D%7D",
                );
            });
        });

        describe("patchTransition()", () => {
            const transition_params = {
                id: 1,
                authorized_user_group_ids: ["1", "2"],
                not_empty_field_ids: [3],
                is_comment_required: true,
            };

            beforeEach(async () => {
                await patchTransition(transition_params);
            });

            it("calls PATCH transition", () =>
                expect(patch).toHaveBeenCalledWith("/api/tracker_workflow_transitions/1", {
                    headers: json_headers,
                    body: '{"authorized_user_group_ids":["1","2"],"not_empty_field_ids":[3],"is_comment_required":true}',
                }));

            describe("when no authorized_user_group_ids provided", () => {
                beforeEach(async () => {
                    await patchTransition({
                        ...transition_params,
                        authorized_user_group_ids: null,
                    });
                });
                it("calls PATCH with empty authorized user groups", () =>
                    expect(patch).toHaveBeenCalledWith(
                        expect.anything(),
                        expect.objectContaining({
                            body: expect.stringMatching(/"authorized_user_group_ids":\[\]/),
                        }),
                    ));
            });

            describe("when no not_empty_field_ids provided", () => {
                beforeEach(async () => {
                    await patchTransition({
                        ...transition_params,
                        not_empty_field_ids: null,
                    });
                });
                it("calls PATCH with empty field ids", () =>
                    expect(patch).toHaveBeenCalledWith(
                        expect.anything(),
                        expect.objectContaining({
                            body: expect.stringMatching(/"not_empty_field_ids":\[\]/),
                        }),
                    ));
            });
        });
    });

    describe("for POST actions", () => {
        let post;
        const new_transition = create("transition");

        beforeEach(() => {
            post = jest.spyOn(tlp_fetch, "post").mockReturnValue(
                Promise.resolve({
                    json: () => new_transition,
                }),
            );
        });

        describe("createTransition()", () => {
            let response;
            beforeEach(async () => {
                response = await createTransition(1, 2, 3);
            });

            it("calls api tracker_workflow_transitions", () => {
                expect(post).toHaveBeenCalledWith("/api/tracker_workflow_transitions", {
                    headers: json_headers,
                    body: '{"tracker_id":1,"from_id":2,"to_id":3}',
                });
            });
            it("returns created transition", () => expect(response).toEqual(new_transition));

            describe("when transition source is new artifact", () => {
                beforeEach(async () => {
                    response = await createTransition(1, null, 3);
                });

                it("send 0 as from_id", () => {
                    expect(post).toHaveBeenCalledWith("/api/tracker_workflow_transitions", {
                        headers: json_headers,
                        body: '{"tracker_id":1,"from_id":0,"to_id":3}',
                    });
                });
            });
        });
    });
});

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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { okAsync } from "neverthrow";
import { uri } from "@tuleap/fetch-result";
import * as fetch_result from "@tuleap/fetch-result";
import type {
    ActionOnPullRequestEvent,
    GlobalComment,
    ProjectLabel,
    User,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    EVENT_TYPE_MERGE,
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import {
    fetchPullRequestInfo,
    fetchPullRequestTimelineItems,
    fetchReviewersInfo,
    fetchUserInfo,
    fetchPullRequestLabels,
    fetchMatchingUsers,
    patchTitle,
    mergePullRequest,
    reopenPullRequest,
    abandonPullRequest,
    putReviewers,
    fetchProjectLabels,
    patchPullRequestLabels,
} from "./tuleap-rest-querier";

vi.mock("@tuleap/fetch-result");

const pull_request_id = 50;

describe("tuleap-rest-querier", () => {
    describe("fetchPullRequestInfo()", () => {
        it("Given the current pull request id, then it should fetch its info", async () => {
            const pull_request_info = {
                title: "My pull request title",
            };

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(pull_request_info));
            const result = await fetchPullRequestInfo(pull_request_id);

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`
            );
            expect(result.value).toStrictEqual(pull_request_info);
        });
    });

    describe("fetchUserInfo()", () => {
        it("Given an user id, then it should fetch its info", async () => {
            const user_id = 102;
            const user_info = {
                display_name: "Joe l'asticot",
            };

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(user_info));
            const result = await fetchUserInfo(user_id);

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(uri`/api/v1/users/${user_id}`);
            expect(result.value).toStrictEqual(user_info);
        });
    });

    describe("fetchPullRequestComments", () => {
        it("Given a pull-request id, Then it should fetch all the timeline items of the pull-request", async () => {
            const timeline_items = [
                {
                    id: 12,
                    content: "This is fine",
                } as GlobalComment,
                {
                    event_type: EVENT_TYPE_MERGE,
                } as ActionOnPullRequestEvent,
            ];

            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(timeline_items));

            const result = await fetchPullRequestTimelineItems(pull_request_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/timeline`,
                expect.any(Object)
            );

            expect(result.value).toStrictEqual(timeline_items);
        });
    });

    describe("patchTitle", () => {
        it("Given a pull-request id, and a title, then it should update the title", async () => {
            vi.spyOn(fetch_result, "patchJSON").mockReturnValue(okAsync(undefined));

            const result = await patchTitle(pull_request_id, "new title");
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.patchJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`,
                {
                    title: "new title",
                }
            );
        });
    });

    describe("fetchReviewersInfo()", () => {
        it("Given a pullrequest id, then it should fetch its info", async () => {
            const reviewers = [{ id: 101 } as User, { id: 102 } as User];

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(reviewers));
            const result = await fetchReviewersInfo(pull_request_id);

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/reviewers`
            );
            expect(result.value).toStrictEqual(reviewers);
        });
    });

    describe("mergePullRequest", () => {
        it("Given a pull-request id, Then it should merge the pull-request", async () => {
            vi.spyOn(fetch_result, "patchJSON").mockReturnValue(
                okAsync({
                    status: PULL_REQUEST_STATUS_MERGED,
                })
            );

            await mergePullRequest(pull_request_id);

            expect(fetch_result.patchJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`,
                { status: PULL_REQUEST_STATUS_MERGED }
            );
        });
    });

    describe("reopenPullRequest", () => {
        it("Given a pull-request id, Then it should reopen the pull-request", async () => {
            vi.spyOn(fetch_result, "patchJSON").mockReturnValue(
                okAsync({
                    status: PULL_REQUEST_STATUS_REVIEW,
                })
            );

            await reopenPullRequest(pull_request_id);

            expect(fetch_result.patchJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`,
                { status: PULL_REQUEST_STATUS_REVIEW }
            );
        });
    });

    describe("abandonPullRequest", () => {
        it("Given a pull-request id, Then it should abandon the pull-request", async () => {
            vi.spyOn(fetch_result, "patchJSON").mockReturnValue(
                okAsync({
                    status: PULL_REQUEST_STATUS_ABANDON,
                })
            );

            await abandonPullRequest(pull_request_id);

            expect(fetch_result.patchJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`,
                { status: PULL_REQUEST_STATUS_ABANDON }
            );
        });
    });

    describe("fetchMatchingUsers", () => {
        it("Given a query, Then it should fetch the matching users", async () => {
            const users = [{ id: 101, display_name: "Joe l'Asticot" } as User];

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(users));

            const query = "Joe l'A";
            const result = await fetchMatchingUsers(query);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(uri`/api/v1/users`, {
                params: {
                    query,
                    limit: 10,
                    offset: 0,
                },
            });

            expect(result.value).toStrictEqual(users);
        });
    });

    describe("putReviewers", () => {
        it("Given a collection of users, then it should set them as reviewers of the pull-request", async () => {
            vi.spyOn(fetch_result, "put").mockReturnValue(okAsync(new Response()));

            await putReviewers(pull_request_id, [
                {
                    id: 101,
                    display_name: "Joe l'Asticot",
                } as User,
                {
                    id: 102,
                    display_name: "Joe the hobo",
                } as User,
            ]);

            expect(fetch_result.put).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/reviewers`,
                {},
                {
                    users: [{ id: 101 }, { id: 102 }],
                }
            );
        });
    });

    describe("fetchPullRequestLabels", () => {
        it("Given a pull-request id, Then it should fetch its labels", async () => {
            const labels: ProjectLabel[] = [
                {
                    id: 1,
                    label: "Emergency",
                    is_outline: false,
                    color: "red-wine",
                },
                {
                    id: 2,
                    label: "Easy fix",
                    is_outline: true,
                    color: "acid-green",
                },
            ];
            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(labels));

            const result = await fetchPullRequestLabels(pull_request_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/labels`,
                expect.any(Object)
            );

            expect(result.value).toStrictEqual(labels);
        });
    });

    describe("fetchProjectLabels", () => {
        it("Given a project id, Then it should fetch all its labels", async () => {
            const labels: ProjectLabel[] = [
                {
                    id: 1,
                    label: "Emergency",
                    is_outline: false,
                    color: "red-wine",
                },
                {
                    id: 2,
                    label: "Easy fix",
                    is_outline: true,
                    color: "acid-green",
                },
            ];
            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(labels));

            const project_id = 102;
            const result = await fetchProjectLabels(project_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/projects/${project_id}/labels`,
                expect.any(Object)
            );

            expect(result.value).toStrictEqual(labels);
        });
    });

    describe("patchPullRequestLabels", () => {
        beforeEach(() => {
            vi.spyOn(fetch_result, "patch").mockReturnValue(okAsync(new Response()));
        });

        it("when there are no added labels nor removed labels, then it should do nothing", () => {
            patchPullRequestLabels(pull_request_id, [], []);

            expect(fetch_result.patch).not.toHaveBeenCalled();
        });

        it('when there are only added labels, then it should not send a "remove" key in the payload', () => {
            patchPullRequestLabels(pull_request_id, [12], []);

            expect(fetch_result.patch).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/labels`,
                {},
                {
                    add: [
                        {
                            id: 12,
                        },
                    ],
                }
            );
        });

        it('when there are only removed labels, then it should not send an "add" key in the payload', () => {
            patchPullRequestLabels(pull_request_id, [], [12]);

            expect(fetch_result.patch).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/labels`,
                {},
                {
                    remove: [
                        {
                            id: 12,
                        },
                    ],
                }
            );
        });

        it('when there are removed and added labels, then it should send a payload containing an "add" and a "remove" keys', () => {
            patchPullRequestLabels(pull_request_id, [8], [12]);

            expect(fetch_result.patch).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/labels`,
                {},
                {
                    add: [
                        {
                            id: 8,
                        },
                    ],
                    remove: [
                        {
                            id: 12,
                        },
                    ],
                }
            );
        });
    });
});

/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getProjectList, getTrackerList, moveArtifact, moveDryRunArtifact } from "./rest-querier";
import * as tlp_fetch from "@tuleap/tlp-fetch";

jest.mock("@tuleap/tlp-fetch");

describe("API querier", () => {
    let recursiveGet: jest.SpyInstance, patch: jest.SpyInstance;
    beforeEach(() => {
        recursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");

        patch = jest.spyOn(tlp_fetch, "patch");
    });

    describe("getProjectList", () => {
        it("will get all project user is tracker admin of", async () => {
            mockFetchSuccess(recursiveGet, {
                return_json: [
                    {
                        id: 102,
                        label: "Project name",
                    },
                ],
            });
            await expect(getProjectList()).resolves.toBeDefined();

            expect(recursiveGet).toHaveBeenCalledWith("/api/projects", {
                params: {
                    query: '{"is_tracker_admin":"true"}',
                    limit: 50,
                    offset: 0,
                },
            });
        });
    });

    describe("getTrackerList", () => {
        it("Given a project id, then it will get all trackers user is admin of", async () => {
            mockFetchSuccess(recursiveGet, {
                return_json: [
                    {
                        id: 10,
                        label: "Tracker name",
                    },
                ],
            });
            const project_id = 5;

            await expect(getTrackerList(project_id)).resolves.toBeDefined();

            expect(recursiveGet).toHaveBeenCalledWith("/api/projects/5/trackers/", {
                params: {
                    query: '{"is_tracker_admin":"true"}',
                    limit: 50,
                },
            });
        });
    });

    describe("moveArtifact", () => {
        it("Given a tracker id, and a project id then it will process the move", async () => {
            const artifact_id = 101;
            const tracker_id = 5;

            mockFetchSuccess(patch);

            await expect(moveArtifact(artifact_id, tracker_id)).resolves.toBeDefined();

            expect(patch).toHaveBeenCalledWith("/api/artifacts/" + artifact_id, {
                headers: { "content-type": "application/json" },
                body: `{"move":{"tracker_id":${tracker_id},"dry_run":false,"should_populate_feedback_on_success":true}}`,
            });
        });
    });

    describe("moveDryRunArtifact", () => {
        it("Given a tracker id, and a project id then it will process the dry run move", async () => {
            const artifact_id = 101;
            const tracker_id = 5;

            mockFetchSuccess(patch);

            await expect(moveDryRunArtifact(artifact_id, tracker_id)).resolves.toBeDefined();

            expect(patch).toHaveBeenCalledWith("/api/artifacts/" + artifact_id, {
                headers: { "content-type": "application/json" },
                body: `{"move":{"tracker_id":${tracker_id},"dry_run":true,"should_populate_feedback_on_success":false}}`,
            });
        });
    });
});

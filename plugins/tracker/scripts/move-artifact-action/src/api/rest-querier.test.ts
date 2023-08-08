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

import { vi, describe, beforeEach, it, expect } from "vitest";
import type { SpyInstance } from "vitest";
import type { Result } from "neverthrow";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";

import { getProjectList, getTrackerList, moveArtifact, moveDryRunArtifact } from "./rest-querier";

function expectOkResult(result: Result<unknown, Fault>): void {
    if (!result.isOk()) {
        throw new Error("Expected an Ok");
    }
}

describe("API querier", () => {
    let getAllJSON: SpyInstance, patchJSON: SpyInstance;

    beforeEach(() => {
        getAllJSON = vi.spyOn(fetch_result, "getAllJSON");
        patchJSON = vi.spyOn(fetch_result, "patchJSON");
    });

    describe("getProjectList", () => {
        it("will get all project user is tracker admin of", async () => {
            getAllJSON.mockReturnValue(
                okAsync([
                    {
                        id: 102,
                        label: "Project name",
                    },
                ])
            );

            expectOkResult(await getProjectList());

            expect(getAllJSON).toHaveBeenCalledWith(uri`/api/projects`, {
                params: {
                    query: '{"is_tracker_admin":"true"}',
                    limit: 50,
                },
            });
        });
    });

    describe("getTrackerList", () => {
        it("Given a project id, then it will get all trackers user is admin of", async () => {
            const project_id = 5;

            getAllJSON.mockReturnValue(
                okAsync([
                    {
                        id: 10,
                        label: "Tracker name",
                    },
                ])
            );

            expectOkResult(await getTrackerList(project_id));

            expect(getAllJSON).toHaveBeenCalledWith(uri`/api/projects/${project_id}/trackers/`, {
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

            patchJSON.mockReturnValue(okAsync({}));

            expectOkResult(await moveArtifact(artifact_id, tracker_id));

            expect(patchJSON).toHaveBeenCalledWith(uri`/api/artifacts/${artifact_id}`, {
                move: {
                    tracker_id,
                    should_populate_feedback_on_success: true,
                },
            });
        });
    });

    describe("moveDryRunArtifact", () => {
        it("Given a tracker id, and a project id then it will process the dry run move", async () => {
            const artifact_id = 101;
            const tracker_id = 5;

            patchJSON.mockReturnValue(okAsync({}));

            expectOkResult(await moveDryRunArtifact(artifact_id, tracker_id));

            expect(patchJSON).toHaveBeenCalledWith(uri`/api/artifacts/${artifact_id}`, {
                move: {
                    tracker_id,
                    dry_run: true,
                },
            });
        });
    });
});

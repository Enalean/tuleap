/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import * as tlp from "@tuleap/tlp-fetch";
import type { ProjectResponse } from "./rest-querier";
import {
    getProjects,
    getTrackerCurrentlyUsedArtifactLinkTypes,
    getTrackerReports,
} from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type {
    TrackerReportResponse,
    TrackerUsedArtifactLinkResponse,
} from "@tuleap/plugin-tracker-rest-api-types";

vi.mock("@tuleap/tlp-fetch");

describe("API querier", () => {
    describe("getTrackerReports", () => {
        it("retrieves tracker reports", async () => {
            const tracker_id = 123;
            const recursive_get_spy = vi.spyOn(tlp, "recursiveGet");

            const reports_response: TrackerReportResponse[] = [
                {
                    id: 741,
                    label: "Report label",
                    is_public: true,
                    is_default: false,
                },
            ];
            mockFetchSuccess(recursive_get_spy, {
                return_json: {
                    collection: reports_response,
                },
            });

            await getTrackerReports(tracker_id);

            expect(recursive_get_spy).toHaveBeenCalledWith(
                `/api/v1/trackers/${tracker_id}/tracker_reports`,
            );
        });
    });

    describe("getTrackerCurrentlyUsedArtifactLinkTypes", () => {
        it("retrieves currently used artifact link types in the tracker", async () => {
            const tracker_id = 123;
            const recursive_get_spy = vi.spyOn(tlp, "recursiveGet");

            const used_artifact_link_types_response: TrackerUsedArtifactLinkResponse[] = [
                {
                    shortname: "_is_child",
                    forward_label: "Child",
                },
            ];
            mockFetchSuccess(recursive_get_spy, {
                return_json: {
                    collection: used_artifact_link_types_response,
                },
            });

            await getTrackerCurrentlyUsedArtifactLinkTypes(tracker_id);

            expect(recursive_get_spy).toHaveBeenCalledWith(
                `/api/v1/trackers/${tracker_id}/used_artifact_links`,
            );
        });
    });

    describe("getProjects", () => {
        it("retrieves projects sorted on the label", async () => {
            const recursive_get_spy = vi.spyOn(tlp, "recursiveGet");

            const project_a = {
                id: 102,
                label: "Project A",
                icon: "",
            };
            const project_b = {
                id: 105,
                label: "Project B",
                icon: "",
            };
            const projects: ProjectResponse[] = [project_b, project_a];
            recursive_get_spy.mockResolvedValue(projects);

            const received_projects = await getProjects();

            expect(recursive_get_spy).toHaveBeenCalledWith(`/api/v1/projects`, {
                params: { limit: 50 },
            });
            expect(received_projects).toStrictEqual(projects);
            expect(received_projects[0]).toStrictEqual(project_a);
            expect(received_projects[1]).toStrictEqual(project_b);
        });
    });
});

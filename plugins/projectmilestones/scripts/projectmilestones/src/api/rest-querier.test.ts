/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import {
    getCurrentMilestones,
    getMilestonesContent,
    getChartData,
    getNbOfPastRelease,
    getLastRelease,
    getTestManagementCampaigns,
    getMilestonesBacklog,
    getAllSprints,
} from "./rest-querier";

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type {
    ArtifactMilestone,
    BurndownData,
    MilestoneContent,
    MilestoneData,
    ParametersRequestWithId,
    TestManagementCampaign,
} from "../type";

describe("getProject() -", () => {
    const limit = 50,
        offset = 0,
        project_id = 102,
        milestone_id = 102;

    it("the REST API will be queried and the current milestones returned", async () => {
        const milestones: MilestoneData[] = [
            {
                id: 1,
                start_date: new Date().toDateString(),
            } as MilestoneData,
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(milestones));

        const result = await getCurrentMilestones({
            project_id,
            limit,
            offset,
        });

        const query = JSON.stringify({
            period: "current",
        });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/projects/" + project_id + "/milestones",
            {
                params: {
                    limit,
                    offset,
                    query,
                },
            },
        );

        expect(result).toEqual(milestones);
    });

    it("the REST API will be queried and the total of user stories of a release returned", async () => {
        const user_stories: MilestoneContent[] = [
            {
                initial_effort: 5,
                artifact: {
                    tracker: {
                        id: 1,
                    },
                },
            },
            {
                initial_effort: 8,
                artifact: {
                    tracker: {
                        id: 2,
                    },
                },
            },
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(user_stories));

        const result = await getMilestonesContent(milestone_id, {
            limit,
            offset,
        });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/milestones/" + milestone_id + "/content",
            {
                params: {
                    limit,
                    offset,
                },
            },
        );

        expect(result).toEqual(user_stories);
    });

    it("the REST API will be queried and the backlog of a release is returned", async () => {
        const backlog: MilestoneContent[] = [
            {
                initial_effort: 5,
                artifact: {
                    tracker: {
                        id: 1,
                    },
                },
            },
            {
                initial_effort: 8,
                artifact: {
                    tracker: {
                        id: 2,
                    },
                },
            },
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(backlog));

        const result = await getMilestonesBacklog(milestone_id, {
            limit,
            offset,
        });

        const query = JSON.stringify({
            status: "all",
        });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/milestones/" + milestone_id + "/backlog",
            {
                params: {
                    limit,
                    offset,
                    query,
                },
            },
        );

        expect(result).toEqual(backlog);
    });

    it("the REST API will be queried and charts data of milestone returned", async () => {
        const burndown_data: BurndownData = {
            start_date: new Date().toDateString(),
        } as BurndownData;

        const artifact_chart = {
            values: [{ value: burndown_data }, {}],
        } as ArtifactMilestone;

        const tlpGetMock = jest.spyOn(tlp_fetch, "get");

        mockFetchSuccess(tlpGetMock, {
            headers: {
                // X-PAGINATION-SIZE
                get: (): string => "2",
            },
            return_json: artifact_chart,
        });

        const result = await getChartData(milestone_id);

        expect(tlpGetMock).toHaveBeenCalledWith(
            `/api/v1/artifacts/${encodeURIComponent(milestone_id)}`,
        );

        expect(result).toEqual(artifact_chart);
    });

    it("the REST API will be queried and all sprints returned", async () => {
        const sprints: MilestoneData[] = [
            {
                id: 1,
                start_date: new Date().toDateString(),
            } as MilestoneData,
            {
                id: 2,
                start_date: new Date().toDateString(),
            } as MilestoneData,
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(sprints));

        const result = await getAllSprints(milestone_id, { limit: 1, offset: 0 });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/milestones/" + project_id + "/milestones",
            {
                params: {
                    limit: 1,
                    offset: 0,
                },
            },
        );

        expect(result).toEqual(sprints);
    });

    it("the REST API will be queried and the past milestones returned", async () => {
        const tlpGetMock = jest.spyOn(tlp_fetch, "get");

        mockFetchSuccess(tlpGetMock, {
            headers: {
                // X-PAGINATION-SIZE
                get: (): string => "10",
            },
        });

        const query = JSON.stringify({
            status: "closed",
        });

        const result = await getNbOfPastRelease({ project_id } as ParametersRequestWithId);
        expect(tlp_fetch.get).toHaveBeenCalledWith(
            "/api/v1/projects/" + project_id + "/milestones",
            {
                params: { limit: 1, offset: 0, query },
            },
        );

        expect(result).toBe(10);
    });

    describe("getLastMiletsone", () => {
        it("the REST API will be queried and the last closed milestone is returned", async () => {
            const last_release: MilestoneData = {
                label: "last",
                id: 10,
            } as MilestoneData;

            const tlpGetMock = jest.spyOn(tlp_fetch, "get");

            mockFetchSuccess(tlpGetMock, {
                headers: {
                    // X-PAGINATION-SIZE
                    get: (): string => "1",
                },
                return_json: last_release,
            });

            const query = JSON.stringify({
                status: "closed",
            });

            const result = await getLastRelease(project_id, 100);
            expect(tlp_fetch.get).toHaveBeenCalledWith(
                "/api/v1/projects/" + project_id + "/milestones",
                {
                    params: { limit: 1, offset: 99, query },
                },
            );

            expect(result).toEqual(last_release);
        });

        it("When there isn't last release, Then null is returned", async () => {
            const result = await getLastRelease(project_id, 0);

            expect(result).toBeNull();
        });
    });

    it("the REST API will be queried and all testmanagement campaigns will be returned", async () => {
        const testmanagement_campaigns: TestManagementCampaign[] = [
            { nb_of_blocked: 10 } as TestManagementCampaign,
            { nb_of_notrun: 2 } as TestManagementCampaign,
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(testmanagement_campaigns));

        const result = await getTestManagementCampaigns(milestone_id, {
            limit,
            offset,
            project_id,
        });

        const query = JSON.stringify({
            milestone_id,
        });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/projects/" + project_id + "/testmanagement_campaigns",
            {
                params: {
                    limit,
                    offset,
                    query,
                },
            },
        );

        expect(result).toEqual(testmanagement_campaigns);
    });
});

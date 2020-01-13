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
    getNbOfSprints,
    getMilestonesContent,
    getBurndownData
} from "./rest-querier";

import * as tlp from "tlp";
import { mockFetchSuccess } from "../../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper";
import { BurndownData, MilestoneContent, MilestoneData } from "../type";

jest.mock("tlp");

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
                number_of_artifact_by_trackers: []
            }
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(milestones));

        const result = await getCurrentMilestones({
            project_id,
            limit,
            offset
        });

        const query = JSON.stringify({
            period: "current"
        });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/projects/" + project_id + "/milestones",
            {
                params: {
                    limit,
                    offset,
                    query
                }
            }
        );

        expect(result).toEqual(milestones);
    });

    it("the REST API will be queried and all the content of a milestone returned", async () => {
        const tlpGetMock = jest.spyOn(tlp, "get");
        mockFetchSuccess(tlpGetMock, {
            headers: {
                // X-PAGINATION-SIZE
                get: (): number => 2
            }
        });

        const result = await getNbOfSprints(milestone_id, {
            limit,
            offset
        });

        expect(tlpGetMock).toHaveBeenCalledWith(
            "/api/v1/milestones/" + milestone_id + "/milestones",
            {
                params: {
                    limit,
                    offset
                }
            }
        );

        expect(result).toEqual(2);
    });

    it("the REST API will be queried and the total of user stories of a release returned", async () => {
        const user_stories: MilestoneContent[] = [
            {
                initial_effort: 5,
                artifact: {
                    tracker: {
                        id: 1
                    }
                }
            },
            {
                initial_effort: 8,
                artifact: {
                    tracker: {
                        id: 2
                    }
                }
            }
        ];

        const tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
        tlpRecursiveGetMock.mockReturnValue(Promise.resolve(user_stories));

        const result = await getMilestonesContent(milestone_id, {
            limit,
            offset
        });

        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
            "/api/v1/milestones/" + milestone_id + "/content",
            {
                params: {
                    limit,
                    offset
                }
            }
        );

        expect(result).toEqual(user_stories);
    });

    it("the REST API will be queried and the burndown_data of milestone returned", async () => {
        const burndown_data: BurndownData = {
            start_date: "",
            is_under_calculation: false,
            duration: 2,
            capacity: 10,
            points: [],
            opening_days: [],
            points_with_date: []
        };

        const tlpGetMock = jest.spyOn(tlp, "get");

        mockFetchSuccess(tlpGetMock, {
            headers: {
                // X-PAGINATION-SIZE
                get: (): number => 2
            },
            return_json: burndown_data
        });

        const result = await getBurndownData(milestone_id, {
            limit,
            offset
        });

        expect(tlpGetMock).toHaveBeenCalledWith(
            `/api/v1/milestones/` + encodeURIComponent(milestone_id) + `/burndown`,
            {
                params: {
                    limit,
                    offset
                }
            }
        );

        expect(result).toEqual(burndown_data);
    });
});

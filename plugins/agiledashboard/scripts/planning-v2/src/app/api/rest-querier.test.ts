/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import type { MilestoneRepresentation } from "./rest-querier";
import {
    getClosedSubMilestones,
    getClosedTopMilestones,
    getOpenSubMilestones,
    getOpenTopMilestones,
} from "./rest-querier";

const identity = <T>(input: T): T => input;

describe(`rest-querier`, () => {
    describe(`milestones GET`, () => {
        it.each([
            [
                "/api/v1/projects/104/milestones",
                { status: "open" },
                50,
                (): Promise<MilestoneRepresentation[]> => getOpenTopMilestones(104, identity),
            ],
            [
                "/api/v1/milestones/26/milestones",
                { status: "open" },
                100,
                (): Promise<MilestoneRepresentation[]> => getOpenSubMilestones(26, identity),
            ],
            [
                "/api/v1/projects/104/milestones",
                { status: "closed" },
                50,
                (): Promise<MilestoneRepresentation[]> => getClosedTopMilestones(104, identity),
            ],
            [
                "/api/v1/milestones/26/milestones",
                { status: "closed" },
                100,
                (): Promise<MilestoneRepresentation[]> => getClosedSubMilestones(26, identity),
            ],
        ])(
            `will call GET recursively on the endpoint
            and will call the callback for each "chunk" of milestones retrieved`,
            async (endpoint_uri: string, query, expected_limit: number, functionUnderTest) => {
                const first_sprint = { id: 77, label: "First Sprint" };
                const second_sprint = { id: 98, label: "Second Sprint" };
                const tlpRecursiveGet = jest
                    .spyOn(tlp_fetch, "recursiveGet")
                    .mockResolvedValue([first_sprint, second_sprint]);

                const response = await functionUnderTest();

                expect(response[0]).toEqual(first_sprint);
                expect(response[1]).toEqual(second_sprint);
                expect(tlpRecursiveGet).toHaveBeenCalledWith(endpoint_uri, {
                    params: {
                        limit: expected_limit,
                        order: "asc",
                        fields: "slim",
                        query: JSON.stringify(query),
                    },
                    getCollectionCallback: expect.any(Function),
                });
            },
        );
    });
});

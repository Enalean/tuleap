/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { Campaign } from "../../../type";
import * as querier from "./execution-querier";
import type { TestExecutionResponse } from "@tuleap/plugin-docgen-docx";
import { getExecutionsForCampaigns } from "./executions-for-campaigns-retriever";

describe("getExecutionsForCampaigns", () => {
    it("should build a map to associate executions to a campaign", async () => {
        const campaign_non_reg: Campaign = { id: 101, label: "Non reg" } as Campaign;
        const campaign_new_features: Campaign = { id: 102, label: "New features" } as Campaign;

        jest.spyOn(querier, "getExecutions").mockImplementation(
            (campaign: Campaign): Promise<TestExecutionResponse[]> => {
                if (campaign.id === 101) {
                    return Promise.resolve([
                        {
                            definition: {
                                id: 123,
                                summary: "Test A",
                                all_requirements: [
                                    {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                ],
                            },
                            previous_result: {
                                status: "passed",
                                submitted_on: "2020-06-23T08:01:04-04:00",
                                submitted_by: {
                                    display_name: "John Doe",
                                },
                            },
                        } as unknown as TestExecutionResponse,
                    ]);
                }

                if (campaign.id === 102) {
                    return Promise.resolve([
                        {
                            definition: {
                                id: 124,
                                summary: "Test B",
                                all_requirements: [
                                    {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                ],
                            },
                            previous_result: null,
                        } as unknown as TestExecutionResponse,
                    ]);
                }

                throw Error("Unknown campaign");
            },
        );

        const executions_map = await getExecutionsForCampaigns([
            campaign_non_reg,
            campaign_new_features,
        ]);

        const executions_for_non_reg = executions_map.get(campaign_non_reg.id);
        expect(executions_for_non_reg?.campaign).toStrictEqual(campaign_non_reg);
        expect(executions_for_non_reg?.executions[0].definition.id).toBe(123);

        const executions_for_new_features = executions_map.get(campaign_new_features.id);
        expect(executions_for_new_features?.campaign).toStrictEqual(campaign_new_features);
        expect(executions_for_new_features?.executions[0].definition.id).toBe(124);
    });
});

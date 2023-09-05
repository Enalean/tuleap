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
import type { Campaign, ExecutionsForCampaignMap } from "../../../type";
import type { TestExecutionResponse } from "@tuleap/plugin-docgen-docx";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import { getExecutions } from "./execution-querier";

interface ExecutionsPromiseTuple {
    readonly campaign: Campaign;
    readonly execution_promise: Promise<TestExecutionResponse[]>;
}

export async function getExecutionsForCampaigns(
    campaigns: ReadonlyArray<Campaign>,
): Promise<ExecutionsForCampaignMap> {
    const executions_map: ExecutionsForCampaignMap = new Map();

    await limitConcurrencyPool(
        4,
        campaigns.map((campaign): ExecutionsPromiseTuple => {
            return {
                campaign,
                execution_promise: getExecutions(campaign),
            };
        }),
        async (execution_tuple: ExecutionsPromiseTuple): Promise<void> => {
            executions_map.set(execution_tuple.campaign.id, {
                campaign: execution_tuple.campaign,
                executions: await execution_tuple.execution_promise,
            });
        },
    );

    return executions_map;
}

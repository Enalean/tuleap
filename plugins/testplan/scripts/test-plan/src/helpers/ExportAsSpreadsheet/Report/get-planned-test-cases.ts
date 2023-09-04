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

import type {
    BacklogItem,
    Campaign,
    PlannedTestDefinitionFromREST,
    TestDefinition,
} from "../../../type";
import { getInternationalizedTestStatus } from "./internationalize-test-status";
import type { VueGettextProvider } from "../../vue-gettext-provider";

export interface PlannedTestCaseAssociatedWithTestExecAndCampaign {
    campaign_id: number;
    campaign_name: string;
    test_case_id: number;
    test_case_title: string;
    test_exec_id: number;
    test_exec_internationalized_status: string;
    test_exec_status: "passed" | "failed" | "blocked" | "notrun";
    test_exec_runner: string;
    test_exec_date: Date;
}

export function getPlannedTestCasesAssociatedWithCampaignAndTestExec(
    gettext_provider: VueGettextProvider,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>,
): PlannedTestCaseAssociatedWithTestExecAndCampaign[] {
    const campaigns_indexed_by_id: Map<string, Campaign> = new Map(
        campaigns.map((campaign: Campaign) => [String(campaign.id), campaign]),
    );

    const planned_test_defs = extractUniquePlannedTestCases(backlog_items);

    const sorted_planned_test_defs = Array.from(planned_test_defs).sort(
        (a: PlannedTestDefinitionFromREST, b: PlannedTestDefinitionFromREST): number => {
            if (a.test_campaign_defining_status.id === b.test_campaign_defining_status.id) {
                return (
                    a.test_execution_used_to_define_status.id -
                    b.test_execution_used_to_define_status.id
                );
            }

            return a.test_campaign_defining_status.id - b.test_campaign_defining_status.id;
        },
    );

    return sorted_planned_test_defs.map(
        (
            test_case: PlannedTestDefinitionFromREST,
        ): PlannedTestCaseAssociatedWithTestExecAndCampaign => {
            const campaign = campaigns_indexed_by_id.get(
                String(test_case.test_campaign_defining_status.id),
            );

            return {
                campaign_id: test_case.test_campaign_defining_status.id,
                campaign_name: campaign ? campaign.label : "",
                test_case_id: test_case.id,
                test_case_title: test_case.summary,
                test_exec_id: test_case.test_execution_used_to_define_status.id,
                test_exec_status: test_case.test_status,
                test_exec_internationalized_status: getInternationalizedTestStatus(
                    gettext_provider,
                    test_case.test_status,
                ),
                test_exec_runner:
                    test_case.test_execution_used_to_define_status.submitted_by.display_name,
                test_exec_date: new Date(
                    test_case.test_execution_used_to_define_status.submitted_on,
                ),
            };
        },
    );
}

function extractUniquePlannedTestCases(
    backlog_items: ReadonlyArray<BacklogItem>,
): PlannedTestDefinitionFromREST[] {
    const test_cases = backlog_items.flatMap(
        (backlog_item: BacklogItem): PlannedTestDefinitionFromREST[] => {
            return backlog_item.test_definitions.flatMap(
                (test_def: TestDefinition): PlannedTestDefinitionFromREST[] => {
                    if (test_def.test_status) {
                        return [test_def];
                    }
                    return [];
                },
            );
        },
    );

    return test_cases.filter(
        (
            test_case: PlannedTestDefinitionFromREST,
            index: number,
            all_test_cases: PlannedTestDefinitionFromREST[],
        ) => {
            return (
                all_test_cases.findIndex(
                    (value: PlannedTestDefinitionFromREST) => value.id === test_case.id,
                ) === index
            );
        },
    );
}

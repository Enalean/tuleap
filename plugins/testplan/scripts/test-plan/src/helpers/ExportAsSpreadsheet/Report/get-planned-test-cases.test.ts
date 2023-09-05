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

import { createVueGettextProviderPassthrough } from "../../vue-gettext-provider-for-test";
import { getPlannedTestCasesAssociatedWithCampaignAndTestExec } from "./get-planned-test-cases";
import type { BacklogItem, Campaign, TestDefinition } from "../../../type";

describe("Get planned test cases", () => {
    it("extracts sorted planned test cases", () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const test_def_41 = {
            id: 41,
            summary: "Title 41",
            test_status: "passed",
            test_campaign_defining_status: {
                id: 687,
            },
            test_execution_used_to_define_status: {
                id: 741,
                submitted_on: "2020-08-11T10:00:00.000Z",
                submitted_by: {
                    display_name: "Some user",
                },
            },
        } as TestDefinition;

        const planned_test_cases = getPlannedTestCasesAssociatedWithCampaignAndTestExec(
            gettext_provider,
            [
                {
                    test_definitions: [
                        {
                            id: 45,
                            summary: "Title 45",
                            test_status: "failed",
                            test_campaign_defining_status: {
                                id: 688,
                            },
                            test_execution_used_to_define_status: {
                                id: 745,
                                submitted_on: "2020-08-11T10:00:00.000Z",
                                submitted_by: {
                                    display_name: "Some user",
                                },
                            },
                        },
                        {
                            id: 46,
                            summary: "Title 46",
                            test_status: "passed",
                            test_campaign_defining_status: {
                                id: 687,
                            },
                            test_execution_used_to_define_status: {
                                id: 746,
                                submitted_on: "2020-08-11T10:00:00.000Z",
                                submitted_by: {
                                    display_name: "Some user",
                                },
                            },
                        },
                        test_def_41,
                        {
                            id: 42,
                            summary: "Title 42",
                            test_status: null,
                            test_campaign_defining_status: {
                                id: 687,
                            },
                            test_execution_used_to_define_status: null,
                        },
                    ],
                } as BacklogItem,
                {
                    test_definitions: [test_def_41],
                } as BacklogItem,
            ],
            [
                {
                    id: 687,
                    label: "Campaign 687",
                } as Campaign,
            ],
        );

        expect(planned_test_cases).toStrictEqual([
            {
                campaign_id: 687,
                campaign_name: "Campaign 687",
                test_case_id: 41,
                test_case_title: "Title 41",
                test_exec_date: new Date("2020-08-11T10:00:00.000Z"),
                test_exec_id: 741,
                test_exec_runner: "Some user",
                test_exec_status: "passed",
                test_exec_internationalized_status: "Passed",
            },
            {
                campaign_id: 687,
                campaign_name: "Campaign 687",
                test_case_id: 46,
                test_case_title: "Title 46",
                test_exec_date: new Date("2020-08-11T10:00:00.000Z"),
                test_exec_id: 746,
                test_exec_runner: "Some user",
                test_exec_status: "passed",
                test_exec_internationalized_status: "Passed",
            },
            {
                campaign_id: 688,
                campaign_name: "",
                test_case_id: 45,
                test_case_title: "Title 45",
                test_exec_date: new Date("2020-08-11T10:00:00.000Z"),
                test_exec_id: 745,
                test_exec_runner: "Some user",
                test_exec_status: "failed",
                test_exec_internationalized_status: "Failed",
            },
        ]);
    });
});

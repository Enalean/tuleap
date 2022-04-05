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

import { shallowMount } from "@vue/test-utils";
import type { RootState } from "../../store/type";
import CreateCampaignTestSelector from "./CreateCampaignTestSelector.vue";
import type { TrackerReport } from "../../helpers/Campaigns/tracker-reports-retriever";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("CreateCampaignTestSelector", () => {
    it("displays the possible tests", () => {
        const testdefinition_tracker_reports: TrackerReport[] = [
            { id: 102, label: "Test def tracker report label" },
        ];
        const wrapper = shallowMount(CreateCampaignTestSelector, {
            props: {
                initial_tests: { test_selector: "report", report_id: 102 },
                testdefinition_tracker_reports,
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        milestone_title: "Milestone Title",
                        testdefinition_tracker_name: "Test def tracker name",
                    } as RootState,
                }),
            },
        });

        const selector = wrapper.get("select");
        const all_options = selector.findAll("option");

        expect(all_options).toHaveLength(4);
        expect(selector.find("optgroup").exists()).toBe(true);
    });

    it("does not propose to select tests from the test definitions tracker reports when there is no tracker reports", () => {
        const wrapper = shallowMount(CreateCampaignTestSelector, {
            props: {
                initial_tests: { test_selector: "milestone" },
                testdefinition_tracker_reports: [],
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        milestone_title: "Milestone Title",
                        testdefinition_tracker_name: "Test def tracker name",
                    } as RootState,
                }),
            },
        });

        const selector = wrapper.get("select");

        expect(selector.find("optgroup").exists()).toBe(false);
    });

    it("selects a new set of tests", () => {
        const wrapper = shallowMount(CreateCampaignTestSelector, {
            props: {
                initial_tests: { test_selector: "milestone" },
                testdefinition_tracker_reports: [],
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        milestone_title: "Milestone Title",
                        testdefinition_tracker_name: "Test def tracker name",
                    } as RootState,
                }),
            },
        });

        wrapper.get("select").setValue("none");
        const emitted_input = wrapper.emitted("update:initial_tests");
        expect(emitted_input).toBeDefined();
        if (emitted_input === undefined) {
            throw new Error("Expected an update:modelValue event to be emitted");
        }
        expect(emitted_input).toHaveLength(1);
        expect(emitted_input[0]).toEqual([{ test_selector: "none" }]);
    });
});

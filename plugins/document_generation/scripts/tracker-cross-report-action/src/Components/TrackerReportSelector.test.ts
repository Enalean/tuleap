/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import TrackerReportSelector from "./TrackerReportSelector.vue";
import { getGlobalTestOptions } from "./global-options-for-test";
import * as rest_querier from "../rest-querier";
import type { TrackerReportResponse } from "@tuleap/plugin-tracker-rest-api-types/src";
import { nextTick } from "vue";

describe("TrackerReportSelector", () => {
    it("displays possible reports", async () => {
        jest.spyOn(rest_querier, "getTrackerReports").mockResolvedValue([
            { id: 100, label: "Public", is_public: true },
            { id: 101, label: "Private", is_public: false },
        ] as TrackerReportResponse[]);

        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                tracker_id: 21,
                report: {
                    id: 101,
                    label: "",
                },
            },
        });

        await nextTick();

        const selector = wrapper.get("select");

        expect(selector.findAll("option")).toHaveLength(2);
        expect(selector.findAll("optgroup")).toHaveLength(2);
    });

    it("disables the selector when no project ID is provided", () => {
        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                tracker_id: null,
                report: null,
            },
        });

        const selector = wrapper.get("select");

        expect(selector.element.disabled).toBe(true);
    });

    it("only shows report groups when there is a report to show", async () => {
        jest.spyOn(rest_querier, "getTrackerReports").mockResolvedValue([
            { id: 101, label: "Private", is_public: false },
        ] as TrackerReportResponse[]);

        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                tracker_id: 21,
                report: {
                    id: 101,
                    label: "",
                },
            },
        });

        await nextTick();

        const selector = wrapper.get("select");

        expect(selector.findAll("option")).toHaveLength(1);
        expect(selector.findAll("optgroup")).toHaveLength(1);
    });

    it("returns full report on load", async () => {
        const expected_report = { id: 101, label: "Private", is_public: false };
        jest.spyOn(rest_querier, "getTrackerReports").mockResolvedValue([
            { id: 100, label: "Public", is_public: true },
            expected_report,
        ] as TrackerReportResponse[]);

        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                tracker_id: 21,
                report: {
                    id: expected_report.id,
                    label: "",
                },
            },
        });

        await nextTick();

        const emitted_input = wrapper.emitted("update:report");
        expect(emitted_input).toBeDefined();
        if (emitted_input === undefined) {
            throw new Error("Expected an update event to be emitted");
        }
        expect(emitted_input[0]).toStrictEqual([expected_report]);
    });
});

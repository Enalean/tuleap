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
import type { TrackerReport } from "../type";

describe("TrackerReportSelector", () => {
    it("displays possible reports", () => {
        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                current_tracker_reports: [
                    { id: 100, name: "Public", is_public: true },
                    { id: 101, name: "Private", is_public: false },
                ] as TrackerReport[],
                report_id: 101,
            },
        });

        const selector = wrapper.get("select");

        expect(selector.findAll("option")).toHaveLength(2);
        expect(selector.findAll("optgroup")).toHaveLength(2);
    });

    it("only shows report groups when there is a report to show", () => {
        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                current_tracker_reports: [
                    { id: 101, name: "Private", is_public: false },
                ] as TrackerReport[],
                report_id: 101,
            },
        });

        const selector = wrapper.get("select");

        expect(selector.findAll("option")).toHaveLength(1);
        expect(selector.findAll("optgroup")).toHaveLength(1);
    });

    it("selects a new report", () => {
        const wrapper = shallowMount(TrackerReportSelector, {
            global: getGlobalTestOptions(),
            props: {
                current_tracker_reports: [
                    { id: 100, name: "Public", is_public: true },
                    { id: 101, name: "Private", is_public: false },
                ] as TrackerReport[],
                report_id: 101,
            },
        });

        wrapper.get("select").setValue(100);

        const emitted_input = wrapper.emitted("update:report_id");
        expect(emitted_input).toBeDefined();
        if (emitted_input === undefined) {
            throw new Error("Expected an update event to be emitted");
        }
        expect(emitted_input[0]).toEqual([100]);
    });
});

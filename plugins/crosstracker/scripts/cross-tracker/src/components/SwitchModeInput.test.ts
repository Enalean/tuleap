/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import SwitchModeInput from "./SwitchModeInput.vue";
import WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report";
import { describe, expect, it } from "vitest";
import { REPORT_ID } from "../injection-symbols";

describe("SwitchModeInput", () => {
    function getWrapper(
        writing_cross_tracker_report: WritingCrossTrackerReport,
    ): VueWrapper<InstanceType<typeof SwitchModeInput>> {
        return shallowMount(SwitchModeInput, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPORT_ID.valueOf()]: 15,
                },
            },
            props: {
                writing_cross_tracker_report,
            },
        });
    }

    describe("Check at component creation", () => {
        it("already in checked state when the current report is expert mode", () => {
            const report = new WritingCrossTrackerReport();
            report.toggleExpertMode();
            const wrapper = getWrapper(report);

            const input: HTMLInputElement = wrapper.find("[data-test=switch-to-expert-input]")
                .element as HTMLInputElement;
            expect(input.checked).toBe(true);
        });

        it("not checked when the current report is default mode", () => {
            const report = new WritingCrossTrackerReport();
            const wrapper = getWrapper(report);

            const input: HTMLInputElement = wrapper.find("[data-test=switch-to-expert-input]")
                .element as HTMLInputElement;
            expect(input.checked).toBe(false);
        });
    });

    describe("Emit switch mode event", () => {
        it("will send the switch mode event when the switch is clicked", () => {
            const report = new WritingCrossTrackerReport();
            const wrapper = getWrapper(report);

            wrapper.find("[data-test=switch-to-expert-input]").trigger("click");

            expect(wrapper.emitted()).toHaveProperty("switch-to-query-mode");
            const event = wrapper.emitted("switch-to-query-mode");
            if (event === undefined) {
                throw Error("The 'switch-to-query-mode' event should be emitted");
            }
            const expected_payload = { is_expert_mode: true };
            expect(event[0][0]).toStrictEqual(expected_payload);
        });
    });
});

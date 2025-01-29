/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import WritingMode from "./WritingMode.vue";
import { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";
import { CLEAR_FEEDBACKS, NOTIFY_FAULT } from "../../injection-symbols";
describe("WritingMode", () => {
    let resetSpy: Mock, errorSpy: Mock;

    beforeEach(() => {
        resetSpy = vi.fn();
        errorSpy = vi.fn();
    });

    function getWrapper(
        writing_cross_tracker_report: WritingCrossTrackerReport,
    ): VueWrapper<InstanceType<typeof WritingMode>> {
        return shallowMount(WritingMode, {
            props: {
                writing_cross_tracker_report,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [CLEAR_FEEDBACKS.valueOf()]: resetSpy,
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                },
            },
        });
    }

    describe("cancel()", () => {
        it("when I hit cancel, then an event will be emitted to cancel the query edition and switch the widget back to reading mode", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            const wrapper = getWrapper(writing_cross_tracker_report);

            wrapper.find("[data-test=writing-mode-cancel-button]").trigger("click");
            const emitted = wrapper.emitted("cancel-query-edition");
            expect(emitted).toBeDefined();
        });
    });

    describe("search()", () => {
        it("when I hit search, then an event will be emitted to preview the results and switch the widget to reading mode", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            const wrapper = getWrapper(writing_cross_tracker_report);

            wrapper.find("[data-test=search-report-button]").trigger("click");
            const emitted = wrapper.emitted("preview-result");
            expect(emitted).toBeDefined();
        });
    });
});

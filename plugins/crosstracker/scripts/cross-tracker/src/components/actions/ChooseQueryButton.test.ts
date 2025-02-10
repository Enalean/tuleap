/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { EMITTER, REPORT_ID } from "../../injection-symbols";
import { beforeEach, expect, vi, describe, it, afterEach } from "vitest";
import { ReadingCrossTrackerReport } from "../../domain/ReadingCrossTrackerReport";
import { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";
import ChooseQueryButton from "./ChooseQueryButton.vue";
import { EmitterStub } from "../../../tests/stubs/EmitterStub";
import type { Report } from "../../type";
import { REFRESH_ARTIFACTS_EVENT, SWITCH_QUERY_EVENT } from "../../helpers/emitter-provider";
vi.mock("@tuleap/tlp-dropdown", () => ({
    createDropdown: (): void => {
        // do nothing
    },
}));

describe("ChooseQueryButton", () => {
    let reading_cross_tracker_report: ReadingCrossTrackerReport,
        writing_cross_tracker_report: WritingCrossTrackerReport,
        emitter: EmitterStub;

    const queries: ReadonlyArray<Report> = [
        {
            uuid: "0194dfd6-a489-703b-aabd-9d473212d908",
            expert_query: "SELECT @id FROM @project = 'self' WHERE @id>1",
            title: " TQL query title",
            description: "",
        },
    ];
    const report_id = 15;
    const getWrapper = (): VueWrapper<InstanceType<typeof ChooseQueryButton>> => {
        return shallowMount(ChooseQueryButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPORT_ID.valueOf()]: report_id,
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                reading_cross_tracker_report,
                writing_cross_tracker_report,
                queries,
                selected_query: null,
            },
        });
    };
    beforeEach(() => {
        emitter = EmitterStub();
        reading_cross_tracker_report = new ReadingCrossTrackerReport();
        writing_cross_tracker_report = new WritingCrossTrackerReport();
    });
    afterEach(() => {
        vi.clearAllMocks();
    });

    it("should send events which updates the TQL query displayed and the artifact result", async () => {
        const wrapper = getWrapper();
        await wrapper.find("[data-test=query]").trigger("click");

        expect(emitter.emitted_event_name.length).toBe(2);
        expect(emitter.emitted_event_name[0]).toBe(REFRESH_ARTIFACTS_EVENT);
        expect(emitter.emitted_event_name[1]).toBe(SWITCH_QUERY_EVENT);
    });
});

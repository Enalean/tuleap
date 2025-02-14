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
import { beforeEach, expect, vi, describe, it } from "vitest";
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

    const FIRST_FILTERED_TITLE = "All artifacts' Ids of the current project";
    const SECOND_FILTERED_TITLE = "Get all Talbot";

    const queries: ReadonlyArray<Report> = [
        {
            uuid: "0194dfd6-a489-703b-aabd-9d473212d908",
            expert_query: "SELECT @id FROM @project = 'self' WHERE @id>1",
            title: FIRST_FILTERED_TITLE,
            description: "",
        },
        {
            uuid: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            expert_query: "SELECT @pretty_title FROM @project = 'self' WHERE @id>1",
            title: "Beautiful titles project artifacts",
            description: "",
        },
        {
            uuid: "00000000-1770-7214-b3ed-b92974949193",
            expert_query: "SELECT @id FROM @project.name = 'Talbot' WHERE @id>1",
            title: SECOND_FILTERED_TITLE,
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

    it("should send events which updates the TQL query displayed and the artifact result", async () => {
        const wrapper = getWrapper();
        await wrapper.find("[data-test=query]").trigger("click");

        expect(emitter.emitted_event_name.length).toBe(2);
        expect(emitter.emitted_event_name[0]).toBe(REFRESH_ARTIFACTS_EVENT);
        expect(emitter.emitted_event_message[0].unwrapOr("")).toStrictEqual({
            query: queries[0],
        });
        expect(emitter.emitted_event_name[1]).toBe(SWITCH_QUERY_EVENT);
        expect(emitter.emitted_event_message[1].isNothing()).toBe(true);
    });

    it.each([
        ["tal", 1, [SECOND_FILTERED_TITLE]],
        ["Talbot", 1, [SECOND_FILTERED_TITLE]],
        [SECOND_FILTERED_TITLE, 1, [SECOND_FILTERED_TITLE]],
        ["all", 2, [SECOND_FILTERED_TITLE, FIRST_FILTERED_TITLE]],
        ["ge", 1, [SECOND_FILTERED_TITLE]],
    ])(
        "filters the queries by the title: '%s'",
        async (filter_input, expected_length, expected_query_title) => {
            const wrapper = getWrapper();
            const queries = wrapper.findAll("[data-test=query]");

            expect(queries.length).toBe(3);
            await wrapper.find("[data-test=query-filter]").setValue(filter_input);
            const queries_1 = wrapper.findAll("[data-test=query]");
            expect(queries_1.length).toBe(expected_length);
            queries_1.forEach((query_element) => {
                if (query_element.element.textContent === null) {
                    throw new Error("The title should not be null");
                }
                expect(expected_query_title.includes(query_element.element.textContent)).toBe(true);
            });
        },
    );
});

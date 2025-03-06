/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { nextTick } from "vue";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import * as rest_querier from "../api/rest-querier";
import ReadingMode from "../components/reading-mode/ReadingMode.vue";
import WritingMode from "../components/writing-mode/WritingMode.vue";
import {
    CLEAR_FEEDBACKS,
    CURRENT_FAULT,
    CURRENT_SUCCESS,
    EMITTER,
    IS_MULTIPLE_QUERY_SUPPORTED,
    IS_USER_ADMIN,
    NOTIFY_FAULT,
    NOTIFY_SUCCESS,
    WIDGET_ID,
} from "../injection-symbols";
import ReadQuery from "./ReadQuery.vue";
import { useFeedbacks } from "../composables/useFeedbacks";
import type { EmitterProvider, Events, SwitchQueryEvent } from "../helpers/emitter-provider";
import { SWITCH_QUERY_EVENT } from "../helpers/emitter-provider";
import type { Query } from "../type";
import mitt from "mitt";

vi.useFakeTimers();

describe("ReadQuery", () => {
    let is_user_admin: boolean;
    let dispatched_switch_query_events: SwitchQueryEvent[];
    let emitter: EmitterProvider;

    beforeEach(() => {
        is_user_admin = true;
        dispatched_switch_query_events = [];
        emitter = mitt<Events>();
        emitter.on(SWITCH_QUERY_EVENT, (event) => {
            dispatched_switch_query_events.push(event);
        });

        vi.spyOn(rest_querier, "getQueries").mockReturnValue(
            okAsync([
                {
                    id: "0194dfd6-a489-703b-aabd-9d473212d908",
                    tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                    title: "My title",
                    description: "",
                },
            ]),
        );
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ReadQuery>> {
        const { notifyFault, notifySuccess, clearFeedbacks, current_fault, current_success } =
            useFeedbacks();
        return shallowMount(ReadQuery, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [WIDGET_ID.valueOf()]: 96,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                    [EMITTER.valueOf()]: emitter,
                    [IS_MULTIPLE_QUERY_SUPPORTED.valueOf()]: true,
                    [NOTIFY_FAULT.valueOf()]: notifyFault,
                    [NOTIFY_SUCCESS.valueOf()]: notifySuccess,
                    [CLEAR_FEEDBACKS.valueOf()]: clearFeedbacks,
                    [CURRENT_FAULT.valueOf()]: current_fault,
                    [CURRENT_SUCCESS.valueOf()]: current_success,
                },
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it(`Given a saved report,
            when I switch to writing mode to modify it,
            then the report will be in "edit-query" state
            and the writing report will be updated
            and it will clear the feedback messages`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(wrapper.vm.report_state).toBe("edit-query");
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
        });

        it(`Given I am not admin,
            when I try to switch to writing mode, then nothing will happen`, async () => {
            is_user_admin = false;
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(wrapper.vm.report_state).toBe("report-saved");
        });
    });

    describe(`handleCancelQueryEdition()`, () => {
        it(`Given I started to modify the report
            when I cancel,
            then the report will be back to its "report-saved" state
            and the reading report will be reset
            and it will clear the feedback messages`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper.findComponent(WritingMode).vm.$emit("cancel-query-edition");

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.isNothing()).toBe(true);
        });
    });

    describe("handlePreviewResult()", () => {
        it(`Given I started to modify the report
            when I preview the results
            then the report will be in "result-preview" state
            and the reading report will be updated
            and it will clear the feedback messages`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper.findComponent(WritingMode).vm.$emit("preview-result", {
                id: "0194dfd6-a489-703b-aabd-9d473212d908",
                tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                title: "My title",
                description: "",
            });

            expect(wrapper.vm.report_state).toBe("result-preview");
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.isNothing()).toBe(true);
        });
    });

    describe("reportSaved()", () => {
        it(`when the report is saved,
            then the reports will be updated
            and it will set a success message`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper.findComponent(WritingMode).vm.$emit("preview-result", {
                id: "0194dfd6-a489-703b-aabd-9d473212d908",
                tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                title: "My title",
                description: "",
            });
            await nextTick();
            wrapper.findComponent(ReadingMode).vm.$emit("saved", {
                id: "0194dfd6-a489-703b-aabd-9d473212d908",
                tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                title: "My title",
                description: "",
            });

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.unwrapOr(null)).toStrictEqual(expect.any(String));
        });
    });

    describe(`unsavedReportDiscarded()`, () => {
        it(`Given a report that has been modified,
            when its changes are discarded,
            then it will restore the reading and writing reports
            and will clear the feedback messages`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper.findComponent(WritingMode).vm.$emit("preview-result", {
                id: "0194dfd6-a489-703b-aabd-9d473212d908",
                tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                title: "My title",
                description: "",
            });
            await nextTick();
            wrapper.findComponent(ReadingMode).vm.$emit("discard-unsaved-report");

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.isNothing()).toBe(true);
        });
    });

    describe("loadBackendReport()", () => {
        it("When I load the report, then the reports will be initialized", async () => {
            const query = 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""';
            const uuid = "0194dfd6-a489-703b-aabd-9d473212d908";
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                okAsync([
                    { tql_query: query, title: " TQL query title", description: "", id: uuid },
                ]),
            );
            getWrapper();
            await vi.runOnlyPendingTimersAsync();
        });

        it("When there is a REST error, it will be shown", async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                errAsync(Fault.fromMessage("Report 41 not found")),
            );
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.current_fault.unwrapOr(null)?.isReportRetrieval()).toBe(true);
        });

        it("Force edit mode when widget has no query", async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync([]));
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.report_state).toBe("edit-query");
        });

        it("Does not emit a SWITCH_QUERY_EVENT when there are no queries", () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync([]));
            getWrapper();

            expect(dispatched_switch_query_events.length).toBe(0);
        });

        it("Does emit a SWITCH_QUERY_EVENT with the first query as parameter once done loading", async () => {
            const query = 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""';
            const uuid1 = "0194dfd6-a489-703b-aabd-9d473212d908";
            const uuid2 = "01952813-7ae7-7a27-bcc0-4a9c660dccb4";
            const queries: ReadonlyArray<Query> = [
                { tql_query: query, title: "TQL query title 1", description: "", id: uuid1 },
                { tql_query: query, title: "TQL query title 2", description: "", id: uuid2 },
            ];
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync(queries));
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_switch_query_events[0].query).toStrictEqual(queries[0]);
        });
    });

    describe(`isXLSXExportAllowed`, () => {
        it(`when the report state is not "report-saved", it does not allow CSV export`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();

            expect(wrapper.vm.is_export_allowed).toBe(false);
        });

        it(`when there was an error, it does not allow XLSX export`, () => {
            const wrapper = getWrapper();
            wrapper.vm.current_fault = Option.fromValue(Fault.fromMessage("Ooops"));

            expect(wrapper.vm.is_export_allowed).toBe(false);
        });

        it(`when user is NOT admin and there is no error,
            it allows XLSX export`, () => {
            is_user_admin = false;
            const wrapper = getWrapper();

            expect(wrapper.vm.is_export_allowed).toBe(true);
        });

        it(`when user is admin and there is an error selected in the report,
            it does not allow XLSX export`, async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                errAsync(Fault.fromMessage("Ooops an error")),
            );

            const wrapper = getWrapper();
            wrapper.vm.current_fault = Option.fromValue(Fault.fromMessage("Ooops"));

            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.is_export_allowed).toBe(false);
        });

        it(`when user is admin and there are no invalid trackers,
            it allows CSV export`, () => {
            const wrapper = getWrapper();

            expect(wrapper.vm.is_export_allowed).toBe(true);
        });
    });
});

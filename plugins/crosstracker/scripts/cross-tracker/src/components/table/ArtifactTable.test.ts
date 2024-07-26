/*
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
import { ref } from "vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { IntlFormatter } from "@tuleap/date-helper";
import { en_US_LOCALE } from "@tuleap/core-constants";
import WritingCrossTrackerReport from "../../writing-mode/writing-cross-tracker-report";
import ArtifactTable from "./ArtifactTable.vue";
import * as rest_querier from "../../api/rest-querier";
import type { Artifact } from "../../type";
import ArtifactTableRow from "./ArtifactTableRow.vue";
import { TuleapAPIFaultStub } from "../../../tests/stubs/TuleapAPIFaultStub";
import { DATE_FORMATTER, REPORT_STATE } from "../../injection-symbols";
import type { ReportState } from "../../domain/ReportState";

vi.useFakeTimers();

describe("ArtifactTable", () => {
    let errorSpy: Mock;

    beforeEach(() => {
        errorSpy = vi.fn();
    });

    function getWrapper(report_state: ReportState): VueWrapper<InstanceType<typeof ArtifactTable>> {
        const date_formatter = IntlFormatter(en_US_LOCALE, "Europe/Paris", "date");
        return shallowMount(ArtifactTable, {
            global: {
                ...getGlobalTestOptions({
                    mutations: {
                        setErrorMessage: errorSpy,
                    },
                }),
                provide: {
                    [DATE_FORMATTER.valueOf()]: date_formatter,
                    [REPORT_STATE.valueOf()]: ref(report_state),
                },
            },
            props: {
                writing_cross_tracker_report: new WritingCrossTrackerReport(),
            },
        });
    }

    describe("loadArtifacts() -", () => {
        it("Given report is saved, it loads artifacts of report", () => {
            const getReportContent = vi
                .spyOn(rest_querier, "getReportContent")
                .mockReturnValue(okAsync({ artifacts: [], total: 0 }));
            getWrapper("report-saved");
            expect(getReportContent).toHaveBeenCalled();
        });

        it("Given report is not saved, it loads artifacts of current selected trackers", () => {
            const getQueryResult = vi
                .spyOn(rest_querier, "getQueryResult")
                .mockReturnValue(okAsync({ artifacts: [], total: 0 }));
            getWrapper("result-preview");
            expect(getQueryResult).toHaveBeenCalled();
        });

        it("when there is a REST error, it will be displayed", async () => {
            const error_message = "Internal Server Error";
            vi.spyOn(rest_querier, "getReportContent").mockReturnValue(
                errAsync(Fault.fromMessage(error_message)),
            );
            getWrapper("report-saved");
            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][1]).toContain(error_message);
        });

        it("when there is a Tuleap API error, it will be shown", async () => {
            const error_message = "Error while parsing the query";
            vi.spyOn(rest_querier, "getReportContent").mockReturnValue(
                errAsync(TuleapAPIFaultStub.fromMessage(error_message)),
            );
            getWrapper("report-saved");
            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][1]).toContain(error_message);
        });

        it(`Given the user does not have the permission to see all the matching artifacts on a call,
            then a load more button is displayed to retrieve the next ones`, async () => {
            const total = 32;
            const first_batch = { artifacts: [{ id: 123 } as Artifact], total };
            const second_batch = { artifacts: [{ id: 545 } as Artifact], total };
            const getReportContent = vi
                .spyOn(rest_querier, "getReportContent")
                .mockReturnValueOnce(okAsync(first_batch));
            const wrapper = getWrapper("report-saved");
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findAllComponents(ArtifactTableRow)).toHaveLength(1);

            getReportContent.mockReturnValueOnce(okAsync(second_batch));
            await wrapper.find("[data-test=load-more]").trigger("click");

            expect(wrapper.findAllComponents(ArtifactTableRow)).toHaveLength(2);
            expect(wrapper.find("[data-test=load-more]").exists()).toBe(false);
        });
    });
});

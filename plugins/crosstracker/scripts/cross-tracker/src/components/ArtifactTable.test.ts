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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report";
import ArtifactTable from "./ArtifactTable.vue";
import * as rest_querier from "../api/rest-querier";
import type { Artifact, ArtifactsCollection, State } from "../type";
import ArtifactTableRow from "./ArtifactTableRow.vue";

jest.useFakeTimers();

describe("ArtifactTable", () => {
    let writingCrossTrackerReport: WritingCrossTrackerReport,
        getReportContent: jest.SpyInstance,
        getQueryResult: jest.SpyInstance,
        errorSpy: jest.Mock;

    beforeEach(() => {
        writingCrossTrackerReport = new WritingCrossTrackerReport();

        getReportContent = jest.spyOn(rest_querier, "getReportContent");
        getQueryResult = jest.spyOn(rest_querier, "getQueryResult");
        errorSpy = jest.fn();
    });

    function instantiateComponent(
        state: Partial<State>,
    ): VueWrapper<InstanceType<typeof ArtifactTable>> {
        return shallowMount(ArtifactTable, {
            global: {
                ...getGlobalTestOptions({
                    state: state as State,
                    mutations: {
                        setErrorMessage: errorSpy,
                    },
                }),
            },
            props: {
                writingCrossTrackerReport,
            },
        });
    }

    describe("loadArtifacts() -", () => {
        it("Given report is saved, it loads artifacts of report", () => {
            mockFetchSuccess(getReportContent);
            instantiateComponent({ is_report_saved: true });
            expect(getReportContent).toHaveBeenCalled();
        });

        it("Given report is not saved, it loads artifacts of current selected trackers", () => {
            mockFetchSuccess(getQueryResult);
            instantiateComponent({ is_report_saved: false });
            expect(getQueryResult).toHaveBeenCalled();
        });

        it("when there is a REST error, it will be displayed", async () => {
            mockFetchError(getReportContent, {
                error_json: {
                    error: { status: 500 },
                },
            });
            instantiateComponent({ is_report_saved: true });
            await jest.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalledWith(expect.any(Object), "An error occurred");
        });

        it("when there is a translated REST error, it will be shown", async () => {
            mockFetchError(getReportContent, {
                error_json: {
                    error: { status: 400, i18n_error_message: "Error while parsing the query" },
                },
            });

            instantiateComponent({ is_report_saved: true });
            await jest.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalledWith(
                expect.any(Object),
                "Error while parsing the query",
            );
        });

        it(`Given the user does not have the permission to see all the matching artifacts on a call,
    then a load more button is displayed to retrieve the next ones.`, async () => {
            getReportContent.mockImplementation(function (
                report_id: number,
                limit: number,
                offset: number,
            ): Promise<ArtifactsCollection> {
                if (offset === 0) {
                    return Promise.resolve({
                        artifacts: [{ id: 123 } as Artifact],
                        total: "32",
                    });
                } else if (offset === 30) {
                    return Promise.resolve({
                        artifacts: [{ id: 124 } as Artifact],
                        total: "32",
                    });
                }
                throw Error("Unexpected offset " + offset);
            });
            const wrapper = instantiateComponent({ is_report_saved: true });
            await jest.runOnlyPendingTimersAsync();

            expect(wrapper.findAllComponents(ArtifactTableRow)).toHaveLength(1);

            await wrapper.find("[data-test=load-more]").trigger("click");

            expect(wrapper.findAllComponents(ArtifactTableRow)).toHaveLength(2);
            expect(wrapper.find("[data-test=load-more]").exists()).toBe(false);
        });
    });
});

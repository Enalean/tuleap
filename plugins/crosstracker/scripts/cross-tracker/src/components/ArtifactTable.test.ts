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

import { createCrossTrackerLocalVue } from "../helpers/local-vue-for-test";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report";
import ArtifactTable from "./ArtifactTable.vue";
import * as rest_querier from "../api/rest-querier";
import type { ArtifactsCollection, Artifact, State } from "../type";
import ArtifactTableRow from "./ArtifactTableRow.vue";

describe("ArtifactTable", () => {
    let writingCrossTrackerReport: WritingCrossTrackerReport,
        getReportContent: jest.SpyInstance,
        getQueryResult: jest.SpyInstance;
    let store = {
        commit: jest.fn(),
    };

    beforeEach(() => {
        writingCrossTrackerReport = new WritingCrossTrackerReport();

        getReportContent = jest.spyOn(rest_querier, "getReportContent");
        getQueryResult = jest.spyOn(rest_querier, "getQueryResult");
    });

    async function instantiateComponent(state: State): Promise<Wrapper<ArtifactTable>> {
        const store_options = { state: state };
        store = createStoreMock(store_options);

        return shallowMount(ArtifactTable, {
            localVue: await createCrossTrackerLocalVue(),
            propsData: {
                writingCrossTrackerReport,
            },
            mocks: { $store: store },
        });
    }

    describe("loadArtifacts() -", () => {
        it("Given report is saved, it loads artifacts of report", async () => {
            mockFetchSuccess(getReportContent);
            await instantiateComponent({ is_report_saved: true } as State);
            expect(getReportContent).toHaveBeenCalled();
        });

        it("Given report is not saved, it loads artifacts of current selected trackers", async () => {
            mockFetchSuccess(getQueryResult);
            await instantiateComponent({ is_report_saved: false } as State);
            expect(getQueryResult).toHaveBeenCalled();
        });

        it("when there is a REST error, it will be displayed", async () => {
            mockFetchError(getReportContent, {
                error_json: {
                    error: { status: 500 },
                },
            });
            const wrapper = await instantiateComponent({ is_report_saved: true } as State);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
                "An error occurred",
            );
        });

        it("when there is a translated REST error, it will be shown", async () => {
            mockFetchError(getReportContent, {
                error_json: {
                    error: { status: 400, i18n_error_message: "Error while parsing the query" },
                },
            });

            const wrapper = await instantiateComponent({ is_report_saved: true } as State);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
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
            const wrapper = await instantiateComponent({ is_report_saved: true } as State);

            // Wait for artifacts to be retrieved
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.findAllComponents(ArtifactTableRow)).toHaveLength(1);

            await wrapper.find("[data-test=load-more]").trigger("click");

            // Wait for artifacts to be retrieved
            await wrapper.vm.$nextTick();

            expect(wrapper.findAllComponents(ArtifactTableRow)).toHaveLength(2);
            expect(wrapper.find("[data-test=load-more]").exists()).toBe(false);
        });
    });
});

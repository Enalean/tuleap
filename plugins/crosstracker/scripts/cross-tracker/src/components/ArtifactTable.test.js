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

import Vue from "vue";
import {
    mockFetchError,
    mockFetchSuccess,
} from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { createStore } from "../store/index.js";
import WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report.js";
import ArtifactTable from "./ArtifactTable.vue";
import GetTextPlugin from "vue-gettext";
import * as rest_querier from "../api/rest-querier.js";

describe("ArtifactTable", () => {
    let Widget, writingCrossTrackerReport, getReportContent, getQueryResult;

    beforeEach(() => {
        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });
        Widget = Vue.extend(ArtifactTable);
        writingCrossTrackerReport = new WritingCrossTrackerReport();

        getReportContent = jest.spyOn(rest_querier, "getReportContent");
        mockFetchSuccess(getReportContent);

        getQueryResult = jest.spyOn(rest_querier, "getQueryResult");
    });

    function instantiateComponent() {
        const vm = new Widget({
            store: createStore(),
            propsData: {
                writingCrossTrackerReport,
            },
        });
        vm.$mount();

        return vm;
    }

    describe("loadArtifacts() -", () => {
        it("Given report is saved, it loads artifacts of report", () => {
            const vm = instantiateComponent();
            vm.$store.replaceState({
                is_report_saved: true,
            });

            vm.loadArtifacts();

            expect(getReportContent).toHaveBeenCalled();
        });

        it("Given report is not saved, it loads artifacts of current selected trackers", () => {
            const vm = instantiateComponent();
            vm.$store.replaceState({
                is_report_saved: false,
            });

            vm.loadArtifacts();

            expect(getQueryResult).toHaveBeenCalled();
        });

        it("when there is a REST error, it will be displayed", () => {
            mockFetchError(getReportContent, { status: 500 });
            const vm = instantiateComponent();
            jest.spyOn(vm.$store, "commit").mockImplementation(() => {});

            return vm.loadArtifacts().then(() => {
                expect(vm.$store.commit).toHaveBeenCalledWith(
                    "setErrorMessage",
                    "An error occurred"
                );
            });
        });

        it("when there is a translated REST error, it will be shown", () => {
            const vm = instantiateComponent();
            const error_json = {
                error: {
                    i18n_error_message: "Error while parsing the query",
                },
            };
            mockFetchError(getReportContent, { status: 400, error_json });
            jest.spyOn(vm.$store, "commit").mockImplementation(() => {});

            return vm.loadArtifacts().then(() => {
                expect(vm.$store.commit).toHaveBeenCalledWith(
                    "setErrorMessage",
                    "Error while parsing the query"
                );
            });
        });
    });
});

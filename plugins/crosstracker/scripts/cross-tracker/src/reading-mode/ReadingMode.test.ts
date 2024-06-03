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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReadingMode from "./ReadingMode.vue";
import type { TrackerForInit } from "../backend-cross-tracker-report";
import BackendCrossTrackerReport from "../backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-cross-tracker-report";
import * as rest_querier from "../api/rest-querier";
import type { Report, State } from "../type";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("ReadingMode", () => {
    let backendCrossTrackerReport: BackendCrossTrackerReport,
        readingCrossTrackerReport: ReadingCrossTrackerReport,
        is_user_admin: boolean,
        has_error_message: boolean,
        errorSpy: jest.Mock,
        discardSpy: jest.Mock;

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        is_user_admin = true;
        has_error_message = false;
        errorSpy = jest.fn();
        discardSpy = jest.fn();
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof ReadingMode>> {
        const store_options = {
            state: { is_user_admin } as State,
            getters: { has_error_message: () => has_error_message },
            mutations: {
                setErrorMessage: errorSpy,
                discardUnsavedReport: discardSpy,
            },
        };

        return shallowMount(ReadingMode, {
            global: { ...getGlobalTestOptions(store_options) },
            props: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it("When I switch to the writing mode, then an event will be emitted", () => {
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeDefined();
            expect(wrapper.find("[data-test=tracker-list-reading-mode]").exists()).toBe(true);
        });

        it(`Given I am browsing as project member,
            when I try to switch to writing mode, nothing will happen`, () => {
            is_user_admin = false;
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeUndefined();
            expect(wrapper.find("[data-test=tracker-list-reading-mode]").exists()).toBe(true);
        });
    });

    describe("saveReport()", () => {
        it(`When I save the report,
            the backend report will be updated and an event will be emitted`, async () => {
            const initBackend = jest.spyOn(backendCrossTrackerReport, "init");
            initBackend.mockImplementation(() => Promise.resolve());
            const duplicateBackend = jest.spyOn(backendCrossTrackerReport, "duplicateFromReport");
            const trackers = new Map();
            trackers.set(36, { id: 36 } as TrackerForInit);
            trackers.set(17, { id: 17 } as TrackerForInit);
            const expert_query = '@description != ""';
            const report = { trackers, expert_query } as Report;

            const updateReport = jest.spyOn(rest_querier, "updateReport").mockResolvedValue(report);
            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(duplicateBackend).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(updateReport).toHaveBeenCalled();
            expect(initBackend).toHaveBeenCalledWith(trackers, expert_query);
            const emitted = wrapper.emitted("saved");
            expect(emitted).toBeDefined();
        });

        it("Given the report is in error, then nothing will happen", async () => {
            has_error_message = true;
            const updateReport = jest.spyOn(rest_querier, "updateReport");

            const wrapper = instantiateComponent();
            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", async () => {
            jest.spyOn(rest_querier, "updateReport").mockRejectedValue(
                new FetchWrapperError("Not found", {
                    json: (): Promise<{ error: { code: number; message: string } }> =>
                        Promise.resolve({ error: { code: 404, message: "Report not found" } }),
                } as Response),
            );

            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(errorSpy).toHaveBeenCalledWith(expect.any(Object), "Report not found");
        });
    });

    describe("cancelReport() -", () => {
        it("when I click on 'Cancel', then the reading report will be reset", async () => {
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-cancel-report]").trigger("click");

            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(discardSpy).toHaveBeenCalled();
        });
    });
});

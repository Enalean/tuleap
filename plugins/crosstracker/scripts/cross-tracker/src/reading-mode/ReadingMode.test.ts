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

import { shallowMount } from "@vue/test-utils";
import ReadingMode from "./ReadingMode.vue";
import BackendCrossTrackerReport from "../backend-cross-tracker-report";
import type { TrackerForInit } from "../backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-cross-tracker-report";
import * as rest_querier from "../api/rest-querier";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import type { Report } from "../type";
import { createCrossTrackerLocalVue } from "../helpers/local-vue-for-test";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("ReadingMode", () => {
    let backendCrossTrackerReport: BackendCrossTrackerReport,
        readingCrossTrackerReport: ReadingCrossTrackerReport;

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
    });

    async function instantiateComponent(): Promise<Wrapper<ReadingMode>> {
        const store_options = { state: { is_user_admin: true } };
        const store = createStoreMock(store_options);

        return shallowMount(ReadingMode, {
            localVue: await createCrossTrackerLocalVue(),
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
            },
            mocks: {
                $store: createStoreMock(store),
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it("When I switch to the writing mode, then an event will be emitted", async () => {
            const wrapper = await instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted()["switch-to-writing-mode"];
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }
            expect(wrapper.find("[data-test=tracker-list-reading-mode]").exists()).toBeTruthy();
        });

        it(`Given I am browsing as project member,
            when I try to switch to writing mode, nothing will happen`, async () => {
            const wrapper = await instantiateComponent();
            wrapper.vm.$store.state.is_user_admin = false;

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted()["switch-to-writing-mode"];
            if (emitted) {
                throw new Error("Event have been emitted");
            }
            expect(wrapper.find("[data-test=tracker-list-reading-mode]").exists()).toBeTruthy();
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
            const wrapper = await instantiateComponent();

            wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");
            await wrapper.vm.$nextTick(); // Component is rendered
            await wrapper.vm.$nextTick(); // During rest call

            expect(duplicateBackend).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(updateReport).toHaveBeenCalled();
            expect(initBackend).toHaveBeenCalledWith(trackers, expert_query);
            const emitted = wrapper.emitted().saved;
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }
        });

        it("Given the report is in error, then nothing will happen", async () => {
            const wrapper = await instantiateComponent();
            wrapper.vm.$store.getters.has_error_message = true;

            wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            const updateReport = jest.spyOn(rest_querier, "updateReport");
            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", async () => {
            jest.spyOn(rest_querier, "updateReport").mockRejectedValue(
                new FetchWrapperError("Not found", {
                    json: (): Promise<{ error: { code: number; message: string } }> =>
                        Promise.resolve({ error: { code: 404, message: "Report not found" } }),
                } as Response),
            );

            const wrapper = await instantiateComponent();

            wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");
            await wrapper.vm.$nextTick(); // Component is loaded and rendered
            await wrapper.vm.$nextTick(); // During rest call
            await wrapper.vm.$nextTick(); // During parse error

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
                "Report not found",
            );
        });
    });

    describe("cancelReport() -", () => {
        it("when I click on 'Cancel', then the reading report will be reset", async () => {
            const duplicateReading = jest
                .spyOn(readingCrossTrackerReport, "duplicateFromReport")
                .mockImplementation(() => Promise.resolve());
            const wrapper = await instantiateComponent();

            wrapper.get("[data-test=cross-tracker-cancel-report]").trigger("click");

            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("discardUnsavedReport");
        });
    });
});

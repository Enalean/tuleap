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

import { createCrossTrackerLocalVue } from "./helpers/local-vue-for-test";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import BackendCrossTrackerReport from "./backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import * as rest_querier from "./api/rest-querier";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type { InvalidTracker, State } from "./type";

describe("CrossTrackerWidget", () => {
    let backendCrossTrackerReport: BackendCrossTrackerReport,
        readingCrossTrackerReport: ReadingCrossTrackerReport,
        writingCrossTrackerReport: WritingCrossTrackerReport,
        getReport: jest.SpyInstance;
    let store = {
        commit: jest.fn(),
    };

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
    });

    async function instantiateComponent(state: State): Promise<Wrapper<CrossTrackerWidget>> {
        const store_options = { state: state, getters: { has_success_message: false } };
        store = createStoreMock(store_options);

        return shallowMount(CrossTrackerWidget, {
            localVue: await createCrossTrackerLocalVue(),
            propsData: {
                writingCrossTrackerReport,
                backendCrossTrackerReport,
                readingCrossTrackerReport,
            },
            mocks: { $store: store },
        });
    }

    describe("switchToWritingMode() -", () => {
        it(`when I switch to the writing mode,
            then the writing report will be updated and a mutation will be committed`, async () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue([
                { id: 102 } as ProjectReference,
            ]);
            const invalid_trackers: Array<InvalidTracker> = [];
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = await instantiateComponent({
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
                reading_mode: true,
            } as State);
            await wrapper.vm.$nextTick(); // wait for component loaded

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("switchToWritingMode");
        });

        it(`Given I am not admin,
            when I try to switch to writing mode, then nothing will happen`, async () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue([]);
            const invalid_trackers: Array<InvalidTracker> = [];
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = await instantiateComponent({
                is_user_admin: false,
                invalid_trackers: invalid_trackers,
                reading_mode: true,
            } as State);
            await wrapper.vm.$nextTick(); // wait for component loaded

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(duplicate).not.toHaveBeenCalled();
            expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith("switchToWritingMode");
        });
    });

    describe("switchToReadingMode() -", () => {
        it(`When I switch to the reading mode with saved state,
            then the writing report will be updated and a mutation will be committed`, async () => {
            const invalid_trackers: Array<InvalidTracker> = [];
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = await instantiateComponent({
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
                reading_mode: false,
            } as State);
            await wrapper.vm.$nextTick(); // wait for component loaded

            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: true });

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("switchToReadingMode", true);
        });

        it(`When I switch to the reading mode with unsaved state,
            then a batch of artifacts will be loaded,
            the reading report will be updated and a mutation will be committed`, async () => {
            const invalid_trackers: Array<InvalidTracker> = [];
            const duplicate = jest.spyOn(readingCrossTrackerReport, "duplicateFromWritingReport");
            const wrapper = await instantiateComponent({
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
                reading_mode: false,
            } as State);
            await wrapper.vm.$nextTick(); // wait for component loaded

            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: false });

            expect(duplicate).toHaveBeenCalledWith(writingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("switchToReadingMode", false);
        });
    });

    describe("loadBackendReport() -", () => {
        beforeEach(() => {
            getReport = jest.spyOn(rest_querier, "getReport");
        });

        it("When I load the report, then the reports will be initialized", async () => {
            const trackers = [{ id: 25 }, { id: 30 }];
            const expert_query = '@title != ""';
            getReport.mockResolvedValue({ trackers, expert_query });
            jest.spyOn(backendCrossTrackerReport, "init").mockImplementation(() => {
                // nothing to mock
            });
            const invalid_trackers: Array<InvalidTracker> = [];
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = await instantiateComponent({
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
            } as State);

            expect(wrapper.vm.$data.is_loading).toBe(false);
            expect(backendCrossTrackerReport.init).toHaveBeenCalledWith(trackers, expert_query);
            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
        });

        it("When there is a REST error, it will be shown", async () => {
            const message = "Report 41 not found";
            mockFetchError(getReport, {
                error_json: {
                    error: { message },
                },
            });
            const invalid_trackers: Array<InvalidTracker> = [];
            const wrapper = await instantiateComponent({
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
            } as State);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setErrorMessage", message);
        });
    });

    describe("reportSaved() -", () => {
        it(`when the report is saved,
            then the reports will be updated and a mutation will be committed`, async () => {
            const invalid_trackers: Array<InvalidTracker> = [];
            const wrapper = await instantiateComponent({
                is_user_admin: true,
                invalid_trackers: invalid_trackers,
                reading_mode: true,
            } as State);
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            await wrapper.vm.$nextTick();

            wrapper.findComponent(ReadingMode).vm.$emit("saved");

            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "switchReportToSaved",
                expect.any(String)
            );
        });
    });
});

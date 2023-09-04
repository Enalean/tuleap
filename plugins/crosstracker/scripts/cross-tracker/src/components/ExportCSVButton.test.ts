/**
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

import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ExportCSVButton from "./ExportCSVButton.vue";
import * as rest_querier from "../api/rest-querier";
import * as download_helper from "../helpers/download-helper";
import * as bom_helper from "../helpers/bom-helper";
import { createCrossTrackerLocalVue } from "../helpers/local-vue-for-test";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("ExportCSVButton", () => {
    let download: jest.SpyInstance, getCSVReport: jest.SpyInstance, addBOM: jest.SpyInstance;
    let store = {};
    beforeEach(() => {
        download = jest.spyOn(download_helper, "download").mockImplementation(() => {
            //nothing to mock
        });
        getCSVReport = jest.spyOn(rest_querier, "getCSVReport");
        addBOM = jest.spyOn(bom_helper, "addBOM");
    });

    async function instantiateComponent(): Promise<Wrapper<ExportCSVButton>> {
        const store_options = {
            state: { report_id: 1 },
            getters: { should_display_export_button: true },
        };
        store = createStoreMock(store_options);

        return shallowMount(ExportCSVButton, {
            localVue: await createCrossTrackerLocalVue(),
            mocks: { $store: store },
        });
    }

    describe("exportCSV()", () => {
        it(`When the server responds,
            then it will hide feedbacks,
            show a spinner and offer to download a CSV file with the results`, async () => {
            const wrapper = await instantiateComponent();
            wrapper.vm.$store.state.report_id = 36;
            const csv = `"id"\r\n72\r\n17\r\n`;
            getCSVReport.mockResolvedValue(csv);
            addBOM.mockImplementation((csv) => csv);

            wrapper.find("[data-test=export-cvs-button]").trigger("click");

            expect(wrapper.vm.$data.is_loading).toBe(true);
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetFeedbacks");
            expect(getCSVReport).toHaveBeenCalledWith(36);
            expect(download).toHaveBeenCalledWith(csv, "export-36.csv", "text/csv;encoding:utf-8");
            expect(wrapper.vm.$data.is_loading).toBe(false);
        });

        it("When there is a REST error, then it will be shown", async () => {
            const wrapper = await instantiateComponent();
            getCSVReport.mockImplementation(() =>
                Promise.reject(
                    new FetchWrapperError("Not found", {
                        status: 404,
                        text: () => Promise.resolve("Report with id 90 not found"),
                    } as Response),
                ),
            );

            wrapper.find("[data-test=export-cvs-button]").trigger("click");
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$data.is_loading).toBe(false);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
                "Report with id 90 not found",
            );
        });

        it("When there is a 50x REST error, then a generic error message will be shown", async () => {
            const wrapper = await instantiateComponent();
            getCSVReport.mockImplementation(() =>
                Promise.reject({
                    response: {
                        status: 503,
                    },
                }),
            );
            getCSVReport.mockImplementation(() =>
                Promise.reject(
                    new FetchWrapperError("Forbidden", {
                        status: 503,
                    } as Response),
                ),
            );

            wrapper.find("[data-test=export-cvs-button]").trigger("click");
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$data.is_loading).toBe(false);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
                expect.any(String),
            );
        });
    });
});

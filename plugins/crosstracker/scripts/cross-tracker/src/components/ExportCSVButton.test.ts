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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import ExportCSVButton from "./ExportCSVButton.vue";
import * as rest_querier from "../api/rest-querier";
import * as download_helper from "../helpers/download-helper";
import * as bom_helper from "../helpers/bom-helper";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { CLEAR_FEEDBACKS, NOTIFY_FAULT, REPORT_ID } from "../injection-symbols";

const report_id = 36;
describe("ExportCSVButton", () => {
    let resetSpy: Mock, errorSpy: Mock;

    beforeEach(() => {
        resetSpy = vi.fn();
        errorSpy = vi.fn();
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ExportCSVButton>> {
        return shallowMount(ExportCSVButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                    [CLEAR_FEEDBACKS.valueOf()]: resetSpy,
                    [REPORT_ID.valueOf()]: report_id,
                },
            },
        });
    }

    describe("exportCSV()", () => {
        it(`When the server responds,
            then it will hide feedbacks,
            show a spinner and offer to download a CSV file with the results`, async () => {
            const wrapper = getWrapper();
            const csv = `"id"\r\n72\r\n17\r\n`;
            const getCSVReport = vi
                .spyOn(rest_querier, "getCSVReport")
                .mockReturnValue(okAsync(csv));
            vi.spyOn(bom_helper, "addBOM").mockImplementation((csv) => csv);
            const download = vi.spyOn(download_helper, "download").mockImplementation(() => {
                //Do nothing
            });

            await wrapper.find("[data-test=export-csv-button]").trigger("click");

            expect(resetSpy).toHaveBeenCalled();
            expect(getCSVReport).toHaveBeenCalledWith(report_id);
            expect(download).toHaveBeenCalledWith(csv, "export-36.csv", "text/csv;encoding:utf-8");
        });

        it("When there is a REST error, then it will be shown", async () => {
            const wrapper = getWrapper();
            vi.spyOn(rest_querier, "getCSVReport").mockReturnValue(
                errAsync(Fault.fromMessage("Report with id 90 not found")),
            );

            await wrapper.find("[data-test=export-csv-button]").trigger("click");

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isCSVExport()).toBe(true);
        });
    });
});

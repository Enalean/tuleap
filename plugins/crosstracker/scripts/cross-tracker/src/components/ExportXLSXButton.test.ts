/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import {
    CLEAR_FEEDBACKS,
    GET_COLUMN_NAME,
    NOTIFY_FAULT,
    REPORT_ID,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../injection-symbols";
import ExportXLSXButton from "./ExportXLSXButton.vue";
import { RetrieveArtifactsTableStub } from "../../tests/stubs/RetrieveArtifactsTableStub";
import { Fault } from "@tuleap/fault";
import { errAsync, okAsync } from "neverthrow";
import { ColumnNameGetter } from "../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../helpers/vue-gettext-provider-for-test";
vi.useFakeTimers();

const downloadXLSXDocument = vi.fn();
vi.mock("../helpers/exporter/export-document", () => {
    return {
        downloadXLSXDocument: downloadXLSXDocument,
    };
});

const downloadXLSX = vi.fn();
vi.mock("../helpers/exporter/xlsx/download-xlsx", () => {
    return {
        downloadXLSX: downloadXLSX,
    };
});

const report_id = 36;
describe("ExportXLSXButton", () => {
    let resetSpy: Mock, errorSpy: Mock;

    beforeEach(() => {
        resetSpy = vi.fn();
        errorSpy = vi.fn();
        downloadXLSXDocument.mockReset();
        downloadXLSX.mockReset();
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ExportXLSXButton>> {
        return shallowMount(ExportXLSXButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                    [CLEAR_FEEDBACKS.valueOf()]: resetSpy,
                    [RETRIEVE_ARTIFACTS_TABLE.valueOf()]:
                        RetrieveArtifactsTableStub.withDefaultContent(),
                    [REPORT_ID.valueOf()]: report_id,
                    [GET_COLUMN_NAME.valueOf()]: ColumnNameGetter(
                        createVueGettextProviderPassThrough(),
                    ),
                },
            },
            props: {
                query_id: "0194dfd6-a489-703b-aabd-9d473212d908",
            },
        });
    }

    describe("exportCSV()", () => {
        it(`When the server responds,
            then it will hide feedbacks,
            show a spinner and offer to download a XLSX file with the results`, async () => {
            const wrapper = getWrapper();

            downloadXLSXDocument.mockImplementation(() => {
                return okAsync(null);
            });
            const xlsx_button = wrapper.find("[data-test=export-xlsx-button]");
            const xlsx_button_icon = wrapper.find("[data-test=export-xlsx-button-icon]");
            expect(xlsx_button_icon.classes()).toContain("fa-download");

            await xlsx_button.trigger("click");

            expect(xlsx_button_icon.classes()).toContain("fa-circle-notch");

            await vi.runOnlyPendingTimersAsync();

            expect(xlsx_button_icon.classes()).toContain("fa-download");
            expect(resetSpy).toHaveBeenCalled();
            expect(errorSpy).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", async () => {
            downloadXLSXDocument.mockImplementation(() => {
                return errAsync(Fault.fromMessage("Bad Request: invalid searchable"));
            });
            const wrapper = getWrapper();
            await wrapper.find("[data-test=export-xlsx-button]").trigger("click");
            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isXLSXExport()).toBe(true);
        });
    });
});

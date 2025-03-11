/**
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { EMITTER, GET_COLUMN_NAME, RETRIEVE_ARTIFACTS_TABLE } from "../injection-symbols";
import ExportXLSXButton from "./ExportXLSXButton.vue";
import { RetrieveArtifactsTableStub } from "../../tests/stubs/RetrieveArtifactsTableStub";
import { Fault } from "@tuleap/fault";
import { errAsync, okAsync } from "neverthrow";
import { ColumnNameGetter } from "../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../helpers/vue-gettext-provider-for-test";
import type { EmitterProvider, Events, NotifyFaultEvent } from "../helpers/emitter-provider";
import { CLEAR_FEEDBACK_EVENT, NOTIFY_FAULT_EVENT } from "../helpers/emitter-provider";
import mitt from "mitt";

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

describe("ExportXLSXButton", () => {
    let emitter: EmitterProvider;
    let dispatched_clear_events: true[];
    let dispatched_fault_events: NotifyFaultEvent[];

    beforeEach(() => {
        downloadXLSXDocument.mockReset();
        downloadXLSX.mockReset();
        emitter = mitt<Events>();
        dispatched_clear_events = [];
        dispatched_fault_events = [];
        emitter.on(CLEAR_FEEDBACK_EVENT, () => {
            dispatched_clear_events.push(true);
        });
        emitter.on(NOTIFY_FAULT_EVENT, (event) => {
            dispatched_fault_events.push(event);
        });
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ExportXLSXButton>> {
        return shallowMount(ExportXLSXButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [RETRIEVE_ARTIFACTS_TABLE.valueOf()]:
                        RetrieveArtifactsTableStub.withDefaultContent(),
                    [GET_COLUMN_NAME.valueOf()]: ColumnNameGetter(
                        createVueGettextProviderPassThrough(),
                    ),
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                current_query: {
                    id: "",
                    tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                    title: "The title of my query",
                    description: "",
                    is_default: false,
                },
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
            expect(dispatched_clear_events).toHaveLength(1);
            expect(dispatched_fault_events).toHaveLength(0);
        });

        it("When there is a REST error, then it will be shown", async () => {
            downloadXLSXDocument.mockImplementation(() => {
                return errAsync(Fault.fromMessage("Bad Request: invalid searchable"));
            });
            const wrapper = getWrapper();
            await wrapper.find("[data-test=export-xlsx-button]").trigger("click");
            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_fault_events).toHaveLength(1);
            expect(dispatched_fault_events[0].fault.isXLSXExport()).toBe(true);
        });
    });
});

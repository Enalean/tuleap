/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { EMITTER, GET_COLUMN_NAME, TABLE_DATA_STORE } from "../../../injection-symbols";
import { errAsync, okAsync } from "neverthrow";
import { ColumnNameGetter } from "../../../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../../../helpers/vue-gettext-provider-for-test";
import type { Events, NotifyFaultEvent } from "../../../helpers/widget-events";
import { NOTIFY_FAULT_EVENT } from "../../../helpers/widget-events";
import type { Emitter } from "mitt";
import mitt from "mitt";
import { Fault } from "@tuleap/fault";
import ExportWithLinksXLSXButton from "./ExportWithLinksXLSXButton.vue";
import { TableDataStoreTestBuilder } from "../../../../tests/builders/TableDataStoreTestBuilder";

vi.useFakeTimers();

const downloadXLSXWithLinkDocument = vi.fn();
vi.mock("../../../helpers/exporter/export-document", () => {
    return {
        downloadXLSXWithLinkDocument: downloadXLSXWithLinkDocument,
    };
});

const downloadXLSX = vi.fn();
vi.mock("../../../helpers/exporter/xlsx/download-xlsx", () => {
    return {
        downloadXLSX: downloadXLSX,
    };
});

describe("ExportWithLinksXLSXButton", () => {
    let emitter: Emitter<Events>;
    let dispatched_fault_events: NotifyFaultEvent[];

    const registerFaultEvents = (event: NotifyFaultEvent): void => {
        dispatched_fault_events.push(event);
    };

    beforeEach(() => {
        downloadXLSXWithLinkDocument.mockReset();
        downloadXLSX.mockReset();
        emitter = mitt<Events>();
        dispatched_fault_events = [];
        emitter.on(NOTIFY_FAULT_EVENT, registerFaultEvents);
    });

    afterEach(() => {
        emitter.off(NOTIFY_FAULT_EVENT, registerFaultEvents);
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ExportWithLinksXLSXButton>> {
        return shallowMount(ExportWithLinksXLSXButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [TABLE_DATA_STORE.valueOf()]: TableDataStoreTestBuilder().build(),
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

    describe("exportXLSX()", () => {
        it(`When the server responds,
            then it will hide feedbacks,
            show a spinner and offer to download a XLSX file with the results and close the modal`, async () => {
            const wrapper = getWrapper();

            downloadXLSXWithLinkDocument.mockImplementation(() => {
                return okAsync(null);
            });
            const xlsx_button = wrapper.find("[data-test=export-xlsx-button]");
            const xlsx_button_icon = wrapper.find("[data-test=export-xlsx-button-icon]");
            expect(xlsx_button_icon.classes()).toContain("fa-download");

            await xlsx_button.trigger("click");

            expect(xlsx_button_icon.classes()).toContain("fa-circle-notch");

            await vi.runOnlyPendingTimersAsync();

            expect(xlsx_button_icon.classes()).toContain("fa-download");
            expect(dispatched_fault_events).toHaveLength(0);
            expect(wrapper.emitted()).toHaveProperty("hide-modal");
        });

        it("When there is a REST error, then it will be shown", async () => {
            downloadXLSXWithLinkDocument.mockImplementation(() => {
                return errAsync(Fault.fromMessage("Bad Request: invalid searchable"));
            });
            const wrapper = getWrapper();
            await wrapper.find("[data-test=export-xlsx-button]").trigger("click");
            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_fault_events).toHaveLength(1);
            expect(dispatched_fault_events[0].fault.isXLSXExport()).toBe(true);
            expect(wrapper.emitted()).not.toHaveProperty("hide-modal");
        });
    });
});

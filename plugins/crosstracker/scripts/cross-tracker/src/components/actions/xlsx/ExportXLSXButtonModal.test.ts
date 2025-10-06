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
import { EMITTER, GET_COLUMN_NAME, RETRIEVE_ARTIFACTS_TABLE } from "../../../injection-symbols";
import { RetrieveArtifactsTableStub } from "../../../../tests/stubs/RetrieveArtifactsTableStub";
import { ColumnNameGetter } from "../../../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../../../helpers/vue-gettext-provider-for-test";
import type { Events } from "../../../helpers/widget-events";
import {
    DISPLAY_XLSX_MODAL_EVENT,
    STARTING_XLSX_EXPORT_EVENT,
} from "../../../helpers/widget-events";
import type { Emitter } from "mitt";
import mitt from "mitt";
import ExportXLSXButtonModal from "./ExportXLSXButtonModal.vue";

vi.useFakeTimers();

describe("ExportXLSXButtonModal", () => {
    let emitter: Emitter<Events>;
    let dispatched_xlsx_export_event: true[];
    let dispatched_xlsx_modal_event: true[];

    const registerXLSXExportEvent = (): void => {
        dispatched_xlsx_export_event.push(true);
    };
    const registerXLSXModalEvent = (): void => {
        dispatched_xlsx_modal_event.push(true);
    };

    beforeEach(() => {
        emitter = mitt<Events>();
        dispatched_xlsx_export_event = [];
        dispatched_xlsx_modal_event = [];
        emitter.on(STARTING_XLSX_EXPORT_EVENT, registerXLSXExportEvent);
        emitter.on(DISPLAY_XLSX_MODAL_EVENT, registerXLSXModalEvent);
    });

    afterEach(() => {
        emitter.on(STARTING_XLSX_EXPORT_EVENT, registerXLSXExportEvent);
        emitter.on(DISPLAY_XLSX_MODAL_EVENT, registerXLSXModalEvent);
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ExportXLSXButtonModal>> {
        return shallowMount(ExportXLSXButtonModal, {
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

    describe("exportXLSX()", () => {
        it("Emit which clear any feedback and spawn the XLSX modal when the button is clicked", async () => {
            const wrapper = getWrapper();
            await wrapper.find("[data-test=export-xlsx-button]").trigger("click");
            await vi.runOnlyPendingTimersAsync();
            expect(dispatched_xlsx_export_event).toHaveLength(1);
            expect(dispatched_xlsx_modal_event).toHaveLength(1);
        });
    });
});

/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi, afterEach } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { IntlFormatter } from "@tuleap/date-helper";
import { en_US_LOCALE } from "@tuleap/core-constants";
import { nextTick, ref } from "vue";
import SelectableTable from "./SelectableTable.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    EMITTER,
    GET_COLUMN_NAME,
    IS_EXPORT_ALLOWED,
    WIDGET_ID,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../../injection-symbols";
import { DATE_CELL, NUMERIC_CELL, PRETTY_TITLE_CELL, TEXT_CELL } from "../../domain/ArtifactsTable";
import { RetrieveArtifactsTableStub } from "../../../tests/stubs/RetrieveArtifactsTableStub";
import { ArtifactsTableBuilder } from "../../../tests/builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import type { RetrieveArtifactsTable } from "../../domain/RetrieveArtifactsTable";
import { Fault } from "@tuleap/fault";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";
import EmptyState from "../EmptyState.vue";
import ExportXLSXButton from "../ExportXLSXButton.vue";
import { ColumnNameGetter } from "../../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../../helpers/vue-gettext-provider-for-test";
import type { Query } from "../../type";
import type { Events, NotifyFaultEvent } from "../../helpers/widget-events";
import { NOTIFY_FAULT_EVENT } from "../../helpers/widget-events";
import type { Emitter } from "mitt";
import mitt from "mitt";
import SelectablePagination from "./SelectablePagination.vue";
import { PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import ArtifactRows from "./ArtifactRows.vue";

vi.useFakeTimers();

const DATE_COLUMN_NAME = "start_date";
const NUMERIC_COLUMN_NAME = "remaining_effort";
const TEXT_COLUMN_NAME = "details";

describe(`SelectableTable`, () => {
    let is_xslx_export_allowed: boolean;
    let query: Query;
    let emitter: Emitter<Events>;
    let dispatched_fault_events: NotifyFaultEvent[];

    const registerFaultEvent = (event: NotifyFaultEvent): void => {
        dispatched_fault_events.push(event);
    };

    beforeEach(() => {
        is_xslx_export_allowed = true;

        query = {
            id: "",
            tql_query: `SELECT start_date WHERE start_date != ''`,
            title: "",
            description: "",
            is_default: false,
        };

        emitter = mitt<Events>();
        dispatched_fault_events = [];
        emitter.on(NOTIFY_FAULT_EVENT, registerFaultEvent);
    });

    afterEach(() => {
        emitter.off(NOTIFY_FAULT_EVENT, registerFaultEvent);
    });

    const getWrapper = (
        table_retriever: RetrieveArtifactsTable,
    ): VueWrapper<InstanceType<typeof SelectableTable>> => {
        return shallowMount(SelectableTable, {
            global: {
                ...getGlobalTestOptions(),
                directives: {
                    "dompurify-html": buildVueDompurifyHTMLDirective(),
                },
                provide: {
                    [DATE_FORMATTER.valueOf()]: IntlFormatter(en_US_LOCALE, "Europe/Paris", "date"),
                    [DATE_TIME_FORMATTER.valueOf()]: IntlFormatter(
                        en_US_LOCALE,
                        "Europe/Paris",
                        "date-with-time",
                    ),
                    [RETRIEVE_ARTIFACTS_TABLE.valueOf()]: table_retriever,
                    [WIDGET_ID.valueOf()]: 15,
                    [IS_EXPORT_ALLOWED.valueOf()]: ref(is_xslx_export_allowed),
                    [GET_COLUMN_NAME.valueOf()]: ColumnNameGetter(
                        createVueGettextProviderPassThrough(),
                    ),
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                query,
            },
        });
    };

    describe(`onMounted()`, () => {
        it(`will retrieve the query result,
            will show a loading spinner
            and will display an ArtifactRows component with level 0`, async () => {
            const table = new ArtifactsTableBuilder()
                .withColumn(DATE_COLUMN_NAME)
                .withColumn(NUMERIC_COLUMN_NAME)
                .withColumn(TEXT_COLUMN_NAME)
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(DATE_COLUMN_NAME, {
                            type: DATE_CELL,
                            value: Option.fromValue("2021-09-26T07:40:03+09:00"),
                            with_time: true,
                        })
                        .addCell(NUMERIC_COLUMN_NAME, {
                            type: NUMERIC_CELL,
                            value: Option.fromValue(74),
                        })
                        .addCell(TEXT_COLUMN_NAME, {
                            type: TEXT_CELL,
                            value: "<p>Griffith</p>",
                        })
                        .build(),
                )
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(DATE_COLUMN_NAME, {
                            type: DATE_CELL,
                            value: Option.fromValue("2025-09-19T13:54:07+10:00"),
                            with_time: true,
                        })
                        .addCell(NUMERIC_COLUMN_NAME, {
                            type: NUMERIC_CELL,
                            value: Option.fromValue(3),
                        })
                        .build(),
                )
                .build();

            const table_result = {
                table,
                total: 2,
            };
            const table_retriever = RetrieveArtifactsTableStub.withContent(
                table_result,
                table_result,
                [table_result.table],
            );

            const wrapper = getWrapper(table_retriever);

            await nextTick();
            expect(wrapper.find("[data-test=loading]").exists()).toBe(true);

            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.find("[data-test=loading").exists()).toBe(false);
            const headers = wrapper
                .findAll("[data-test=column-header]")
                .map((header) => header.text());

            expect(headers).toContain(DATE_COLUMN_NAME);
            expect(headers).toContain(NUMERIC_COLUMN_NAME);
            expect(headers).toContain(TEXT_COLUMN_NAME);
            expect(wrapper.findComponent(ArtifactRows).exists()).toBe(true);
            expect(wrapper.findComponent(ArtifactRows).props("level")).toBe(0);
        });

        it(`when there is a REST error, it will be shown`, async () => {
            const table_retriever = RetrieveArtifactsTableStub.withFault(
                Fault.fromMessage("Bad Request: invalid searchable"),
            );

            getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_fault_events).toHaveLength(1);
            expect(dispatched_fault_events[0].fault.isArtifactsRetrieval()).toBe(true);
        });
    });
    describe("Empty state", () => {
        it("displays the empty state and no XLSX button nor pagination when there is no result", () => {
            const table_result = {
                table: new ArtifactsTableBuilder().build(),
                total: 0,
            };
            const table_retriever = RetrieveArtifactsTableStub.withContent(
                table_result,
                table_result,
                [table_result.table],
            );

            const wrapper = getWrapper(table_retriever);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ExportXLSXButton).exists()).toBe(false);
            expect(wrapper.findComponent(SelectablePagination).exists()).toBe(false);
        });
    });
    describe("Header column name classes", () => {
        it("should add the additional classes to the header column name, if it is a @pretty_title column or if it is the last cell of row", async () => {
            const table = new ArtifactsTableBuilder()
                .withColumn(PRETTY_TITLE_COLUMN_NAME)
                .withColumn(NUMERIC_COLUMN_NAME)
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(PRETTY_TITLE_COLUMN_NAME, {
                            type: PRETTY_TITLE_CELL,
                            title: "earthmaking",
                            tracker_name: "lifesome",
                            artifact_id: 512,
                            color: "inca-silver",
                        })
                        .addCell(NUMERIC_COLUMN_NAME, {
                            type: NUMERIC_CELL,
                            value: Option.fromValue(74),
                        })
                        .build(),
                )
                .build();

            const table_result = {
                table,
                total: 1,
            };
            const table_retriever = RetrieveArtifactsTableStub.withContent(
                table_result,
                table_result,
                [table_result.table],
            );

            const wrapper = getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            const headers_classes = wrapper
                .findAll("[data-test=column-header]")
                .map((header) => header.classes());

            expect(headers_classes[0]).toContain("is-pretty-title-column");
            expect(headers_classes[0]).toContain("headers-cell");

            expect(headers_classes[1]).toContain("is-last-cell-of-row");
            expect(headers_classes[1]).toContain("headers-cell");
            expect(headers_classes[1]).not.toContain("is-pretty-title-column");
        });
    });
});

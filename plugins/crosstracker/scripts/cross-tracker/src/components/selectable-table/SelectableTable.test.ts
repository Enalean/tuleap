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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { IntlFormatter } from "@tuleap/date-helper";
import { en_US_LOCALE } from "@tuleap/core-constants";
import { nextTick, ref } from "vue";
import SelectableTable from "./SelectableTable.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";
import { ProjectInfoStub } from "../../../tests/stubs/ProjectInfoStub";
import { TrackerInfoStub } from "../../../tests/stubs/TrackerInfoStub";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    IS_CSV_EXPORT_ALLOWED,
    NOTIFY_FAULT,
    REPORT_STATE,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../../injection-symbols";
import { DATE_CELL, NUMERIC_CELL, TEXT_CELL } from "../../domain/ArtifactsTable";
import { RetrieveArtifactsTableStub } from "../../../tests/stubs/RetrieveArtifactsTableStub";
import { ArtifactsTableBuilder } from "../../../tests/builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import type {
    ArtifactsTableWithTotal,
    RetrieveArtifactsTable,
} from "../../domain/RetrieveArtifactsTable";
import { Fault } from "@tuleap/fault";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";
import EmptyState from "../EmptyState.vue";
import type { ReportState } from "../../domain/ReportState";
import SelectableCell from "./SelectableCell.vue";

vi.useFakeTimers();

const DATE_COLUMN_NAME = "start_date";
const NUMERIC_COLUMN_NAME = "remaining_effort";
const TEXT_COLUMN_NAME = "details";

describe(`SelectableTable`, () => {
    let errorSpy: Mock,
        report_state: ReportState,
        is_csv_export_allowed: boolean,
        writing_cross_tracker_report: WritingCrossTrackerReport;

    beforeEach(() => {
        errorSpy = vi.fn();
        report_state = "report-saved";
        is_csv_export_allowed = true;

        writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = `SELECT start_date WHERE start_date != ''`;
        writing_cross_tracker_report.addTracker(
            ProjectInfoStub.withId(116),
            TrackerInfoStub.withId(186),
        );
        writing_cross_tracker_report.toggleExpertMode();
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
                    [REPORT_STATE.valueOf()]: ref(report_state),
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                    [IS_CSV_EXPORT_ALLOWED.valueOf()]: ref(is_csv_export_allowed),
                },
            },
            props: {
                writing_cross_tracker_report,
            },
        });
    };

    describe(`onMounted()`, () => {
        it(`will retrieve the query result,
            will show a loading spinner
            and will show a table-like grid with the selected columns and artifact values`, async () => {
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
            expect(wrapper.findAllComponents(SelectableCell)).toHaveLength(6);
        });

        it(`when there is a REST error, it will be shown`, async () => {
            const table_retriever = RetrieveArtifactsTableStub.withFault(
                Fault.fromMessage("Bad Request: invalid searchable"),
            );

            getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isArtifactsRetrieval()).toBe(true);
        });

        it(`does nothing when the report mode is not expert`, async () => {
            const table_retriever = RetrieveArtifactsTableStub.withFault(
                Fault.fromMessage("Invalid query"),
            );
            writing_cross_tracker_report.toggleExpertMode();

            getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).not.toHaveBeenCalled();
        });
    });
    describe("loadArtifact()", () => {
        let initial_report_with_total: ArtifactsTableWithTotal;
        let query_report_with_total: ArtifactsTableWithTotal;
        beforeEach(() => {
            const initial_report = new ArtifactsTableBuilder()
                .withColumn(DATE_COLUMN_NAME)
                .withColumn(NUMERIC_COLUMN_NAME)
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

            const query_report = new ArtifactsTableBuilder()
                .withColumn(TEXT_COLUMN_NAME)
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(TEXT_COLUMN_NAME, {
                            type: TEXT_CELL,
                            value: "not hehehe",
                        })
                        .build(),
                )
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(TEXT_COLUMN_NAME, {
                            type: TEXT_CELL,
                            value: "hehe",
                        })
                        .build(),
                )
                .build();

            initial_report_with_total = {
                table: initial_report,
                total: 2,
            };

            query_report_with_total = {
                table: query_report,
                total: 1,
            };
        });
        it("returns the current query report, if the current report is not saved", async () => {
            const table_retriever = RetrieveArtifactsTableStub.withContent(
                query_report_with_total,
                initial_report_with_total,
            );
            report_state = "result-preview";
            const wrapper = getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            expect(
                wrapper.findAll("[data-test=column-header]").map((header) => header.text()),
            ).toContain(TEXT_COLUMN_NAME);
            expect(wrapper.findAllComponents(SelectableCell)).toHaveLength(2);
        });
        it("returns the saved report, if the current report is saved", async () => {
            const table_retriever = RetrieveArtifactsTableStub.withContent(
                query_report_with_total,
                initial_report_with_total,
            );

            const wrapper = getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            const headers = wrapper
                .findAll("[data-test=column-header]")
                .map((header) => header.text());
            expect(headers).toContain(DATE_COLUMN_NAME);
            expect(headers).toContain(NUMERIC_COLUMN_NAME);
            expect(wrapper.findAllComponents(SelectableCell)).toHaveLength(4);
        });
    });
    describe("Empty state", () => {
        it("displays the empty state when there is no result", () => {
            const table_result = {
                table: new ArtifactsTableBuilder().build(),
                total: 0,
            };
            const table_retriever = RetrieveArtifactsTableStub.withContent(
                table_result,
                table_result,
            );

            const wrapper = getWrapper(table_retriever);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
        });
    });
});

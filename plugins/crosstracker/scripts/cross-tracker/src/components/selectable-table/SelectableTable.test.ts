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
import { nextTick } from "vue";
import SelectableTable from "./SelectableTable.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import WritingCrossTrackerReport from "../../writing-mode/writing-cross-tracker-report";
import { ProjectInfoStub } from "../../../tests/stubs/ProjectInfoStub";
import { TrackerInfoStub } from "../../../tests/stubs/TrackerInfoStub";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../../injection-symbols";
import { DATE_CELL } from "../../domain/ArtifactsTable";
import { RetrieveArtifactsTableStub } from "../../../tests/stubs/RetrieveArtifactsTableStub";
import { ArtifactsTableBuilder } from "../../../tests/builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import type { RetrieveArtifactsTable } from "../../domain/RetrieveArtifactsTable";
import { Fault } from "@tuleap/fault";

vi.useFakeTimers();

const COLUMN_NAME = "start_date";

describe(`SelectableTable`, () => {
    let errorSpy: Mock;

    beforeEach(() => {
        errorSpy = vi.fn();
    });

    const getWrapper = (
        table_retriever: RetrieveArtifactsTable,
    ): VueWrapper<InstanceType<typeof SelectableTable>> => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = `SELECT start_date WHERE start_date != ''`;
        writing_cross_tracker_report.addTracker(
            ProjectInfoStub.withId(116),
            TrackerInfoStub.withId(186),
        );

        return shallowMount(SelectableTable, {
            global: {
                ...getGlobalTestOptions({
                    mutations: {
                        setErrorMessage: errorSpy,
                    },
                }),
                provide: {
                    [DATE_FORMATTER.valueOf()]: IntlFormatter(en_US_LOCALE, "Europe/Paris", "date"),
                    [DATE_TIME_FORMATTER.valueOf()]: IntlFormatter(
                        en_US_LOCALE,
                        "Europe/Paris",
                        "date-with-time",
                    ),
                    [RETRIEVE_ARTIFACTS_TABLE.valueOf()]: table_retriever,
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
                .withColumn(COLUMN_NAME)
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(COLUMN_NAME, {
                            type: DATE_CELL,
                            value: Option.fromValue("2021-09-26T07:40:03+09:00"),
                            with_time: true,
                        })
                        .build(),
                )
                .withArtifactRow(
                    new ArtifactRowBuilder()
                        .addCell(COLUMN_NAME, {
                            type: DATE_CELL,
                            value: Option.fromValue("2025-09-19T13:54:07+10:00"),
                            with_time: true,
                        })
                        .build(),
                )
                .build();

            const table_retriever = RetrieveArtifactsTableStub.withContent({
                table,
                total: 2,
            });

            const wrapper = getWrapper(table_retriever);

            await nextTick();
            expect(wrapper.find("[data-test=loading]").exists()).toBe(true);

            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.find("[data-test=loading").exists()).toBe(false);
            expect(
                wrapper.findAll("[data-test=column-header]").map((header) => header.text()),
            ).toContain(COLUMN_NAME);
            expect(wrapper.findAll("[data-test=cell]")).toHaveLength(2);
        });

        it(`when there is a REST error, it will be shown`, async () => {
            const error_message = "Bad Request: invalid searchable";
            const table_retriever = RetrieveArtifactsTableStub.withFault(
                Fault.fromMessage(error_message),
            );

            getWrapper(table_retriever);

            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][1]).toContain(error_message);
        });
    });
});

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

import { describe, expect, it } from "vitest";
import { PROJECT_COLUMN_NAME, PRETTY_TITLE_COLUMN_NAME } from "../../../../domain/ColumnName";
import type { PrettyTitleCell } from "../../../../domain/ArtifactsTable";
import { PROJECT_CELL, PRETTY_TITLE_CELL } from "../../../../domain/ArtifactsTable";
import { ColumnNameGetter } from "../../../../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../../../vue-gettext-provider-for-test";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { formatDataWithLink } from "./data-with-link-formater";
import { v4 as uuidv4 } from "uuid";
import type { RowEntry } from "../../../../domain/TableDataStore";
import type { ContentSection } from "../without-link/data-formater";
import { TableDataStoreTestBuilder } from "../../../../../tests/builders/TableDataStoreTestBuilder";
import { ArtifactRowBuilder } from "../../../../../tests/builders/ArtifactRowBuilder";

describe("data-with-link-formater", () => {
    const project_column = "Project";
    const project_name = "CT4-V Blackwing";
    const other_project_name = "Charger SRT Hellcat Redeye";

    const first_tracker = "Twin-Turbo";
    const second_tracker = "Supercharged";

    const pretty_title_column = "Artifact";

    const parent_row_1_uuid = uuidv4();
    const parent_row_1_pretty_title: PrettyTitleCell = {
        tracker_name: first_tracker,
        color: "acid-green",
        artifact_id: 6,
        title: "Boost 1.2bar",
        type: PRETTY_TITLE_CELL,
    };

    const parent_row_1 = new ArtifactRowBuilder()
        .withRowUuid(parent_row_1_uuid)
        .addCell(PRETTY_TITLE_COLUMN_NAME, parent_row_1_pretty_title)
        .addCell(PROJECT_COLUMN_NAME, {
            type: PROJECT_CELL,
            name: other_project_name,
            icon: "",
        })
        .build();

    const parent_entry_1: RowEntry = {
        parent_row_uuid: null,
        row: parent_row_1,
    };

    const parent_row_2_uuid = uuidv4();
    const parent_row_2_pretty_title: PrettyTitleCell = {
        tracker_name: second_tracker,
        color: "coral-pink",
        artifact_id: 8,
        title: "Fuel Injector",
        type: PRETTY_TITLE_CELL,
    };
    const parent_row_2 = new ArtifactRowBuilder()
        .withRowUuid(parent_row_2_uuid)
        .addCell(PRETTY_TITLE_COLUMN_NAME, parent_row_2_pretty_title)
        .addCell(PROJECT_COLUMN_NAME, {
            type: PROJECT_CELL,
            name: project_name,
            icon: "",
        })
        .build();

    const parent_entry_2: RowEntry = {
        parent_row_uuid: null,
        row: parent_row_2,
    };

    const link_row_first_level_1_uuid = uuidv4();
    const link_row_first_level_1_pretty_title: PrettyTitleCell = {
        tracker_name: second_tracker,
        color: "coral-pink",
        artifact_id: 8,
        title: "Big Rotary-screw compressor",
        type: PRETTY_TITLE_CELL,
    };

    const link_row_first_level_1 = new ArtifactRowBuilder()
        .isAForwardRow()
        .withRowUuid(link_row_first_level_1_uuid)
        .addCell(PRETTY_TITLE_COLUMN_NAME, link_row_first_level_1_pretty_title)
        .addCell(PROJECT_COLUMN_NAME, {
            type: PROJECT_CELL,
            name: project_name,
            icon: "",
        })
        .build();

    const link_entry_first_level_1: RowEntry = {
        parent_row_uuid: parent_row_2_uuid,
        row: link_row_first_level_1,
    };

    const link_row_first_level_2_uuid = uuidv4();
    const link_row_first_level_2_pretty_title: PrettyTitleCell = {
        tracker_name: second_tracker,
        color: "acid-green",
        artifact_id: 8,
        title: "Big Rotary-screw compressor",
        type: PRETTY_TITLE_CELL,
    };
    const link_row_first_level_2 = new ArtifactRowBuilder()
        .isAReverseRow()
        .withRowUuid(link_row_first_level_2_uuid)
        .addCell(PRETTY_TITLE_COLUMN_NAME, link_row_first_level_2_pretty_title)
        .addCell(PROJECT_COLUMN_NAME, {
            type: PROJECT_CELL,
            name: project_name,
            icon: "",
        })
        .build();

    const link_entry_first_level_2: RowEntry = {
        parent_row_uuid: parent_row_2_uuid,
        row: link_row_first_level_2,
    };

    const link_row_second_level_1_uuid = uuidv4();
    const link_row_second_level_1_pretty_title: PrettyTitleCell = {
        tracker_name: second_tracker,
        color: "acid-green",
        artifact_id: 8,
        title: "Turbos",
        type: PRETTY_TITLE_CELL,
    };

    const link_row_second_level_1 = new ArtifactRowBuilder()
        .withRowUuid(link_row_second_level_1_uuid)
        .addCell(PRETTY_TITLE_COLUMN_NAME, link_row_second_level_1_pretty_title)
        .addCell(PROJECT_COLUMN_NAME, {
            type: PROJECT_CELL,
            name: project_name,
            icon: "",
        })
        .build();

    const link_entry_second_level_1: RowEntry = {
        parent_row_uuid: link_row_first_level_1_uuid,
        row: link_row_second_level_1,
    };

    /**
     * ┌───────────────┐
     * │parent_entry_1 │
     * └───────────────┘
     * ┌───────────────┐
     * │parent_entry_2 │
     * └──────────┬────┘
     *            │         ┌──────────────────────────────┐
     *            ├────────►│link_entry_first_level_1      │
     *            │         └────────────────┬─────────────┘
     *            │                          │
     *            │                          │               ┌───────────────────────────┐
     *            │                          └──────────────►│link_entry_second_level_1  │
     *            │                                          └───────────────────────────┘
     *            │         ┌───────────────────────────────┐
     *            └────────►│link_entry_first_level_2       │
     *                      └───────────────────────────────┘
     */
    it("generates the formatted data with linked artifact that will be used to create the XLSX document with rows", () => {
        const table_data_builder = TableDataStoreTestBuilder();
        table_data_builder.withColumns(PRETTY_TITLE_COLUMN_NAME, PROJECT_COLUMN_NAME);
        table_data_builder.withEntries(
            parent_entry_1,
            parent_entry_2,
            link_entry_first_level_1,
            link_entry_first_level_2,
            link_entry_second_level_1,
        );

        const result = formatDataWithLink(
            table_data_builder.build(),
            ColumnNameGetter(createVueGettextProviderPassThrough()),
        );

        const content_cell_result: ContentSection = {
            headers: [
                new TextCell(pretty_title_column),
                new TextCell(project_column),
                new TextCell(pretty_title_column),
                new TextCell(project_column),
                new TextCell(pretty_title_column),
                new TextCell(project_column),
            ],
            rows: [
                [
                    new TextCell(
                        parent_row_1_pretty_title.tracker_name +
                            "#" +
                            parent_row_1_pretty_title.artifact_id +
                            " " +
                            parent_row_1_pretty_title.title,
                    ),
                    new TextCell(other_project_name),
                ],
                [
                    new TextCell(
                        parent_row_2_pretty_title.tracker_name +
                            "#" +
                            parent_row_2_pretty_title.artifact_id +
                            " " +
                            parent_row_2_pretty_title.title,
                    ),
                    new TextCell(project_name),
                    new TextCell(
                        link_row_first_level_1_pretty_title.tracker_name +
                            "#" +
                            link_row_first_level_1_pretty_title.artifact_id +
                            " " +
                            link_row_first_level_1_pretty_title.title,
                    ),
                    new TextCell(project_name),
                    new TextCell(
                        link_row_second_level_1_pretty_title.tracker_name +
                            "#" +
                            link_row_second_level_1_pretty_title.artifact_id +
                            " " +
                            link_row_second_level_1_pretty_title.title,
                    ),
                    new TextCell(project_name),
                ],
                [
                    new TextCell(
                        parent_row_2_pretty_title.tracker_name +
                            "#" +
                            parent_row_2_pretty_title.artifact_id +
                            " " +
                            parent_row_2_pretty_title.title,
                    ),
                    new TextCell(project_name),
                    new TextCell(
                        link_row_first_level_2_pretty_title.tracker_name +
                            "#" +
                            link_row_first_level_2_pretty_title.artifact_id +
                            " " +
                            link_row_first_level_2_pretty_title.title,
                    ),
                    new TextCell(project_name),
                ],
            ],
        };
        expect(content_cell_result).toStrictEqual(result);
    });
});

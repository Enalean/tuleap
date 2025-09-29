/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { ArtifactsTableBuilder } from "../../../tests/builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import { RetrieveArtifactLinksStub } from "../../../tests/stubs/RetrieveArtifactLinksStub";
import { DATE_CELL, NUMERIC_CELL, TEXT_CELL } from "../../domain/ArtifactsTable";
import type { ArtifactsTable } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { RETRIEVE_ARTIFACT_LINKS } from "../../injection-symbols";
import type { RetrieveArtifactLinks } from "../../domain/RetrieveArtifactLinks";
import ArtifactRows from "./ArtifactRows.vue";
import RowArtifact from "./RowArtifact.vue";

const DATE_COLUMN_NAME = "start_date";
const NUMERIC_COLUMN_NAME = "remaining_effort";
const TEXT_COLUMN_NAME = "details";

describe("ArtifactRows", () => {
    let table: ArtifactsTable,
        artifact_links_table_retriever: RetrieveArtifactLinks,
        ancestors: number[];

    beforeEach(() => {
        artifact_links_table_retriever = RetrieveArtifactLinksStub.withDefaultContent();
        ancestors = [123, 135];

        table = new ArtifactsTableBuilder()
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
                    .buildWithExpectedNumberOfLinks(2, 1),
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
    });

    const getWrapper = (
        artifacts_table: ArtifactsTable,
    ): VueWrapper<InstanceType<typeof ArtifactRows>> => {
        return shallowMount(ArtifactRows, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [RETRIEVE_ARTIFACT_LINKS.valueOf()]: artifact_links_table_retriever,
                },
            },
            props: {
                rows: artifacts_table.rows,
                columns: artifacts_table.columns,
                tql_query: "SELECT @id FROM @project='self' WHERE @id>1",
                level: 0,
                ancestors,
                parent_row: null,
            },
        });
    };

    it(`will show a table-like grid with the selected columns and artifact values`, () => {
        const wrapper = getWrapper(table);

        expect(wrapper.findAllComponents(RowArtifact)).toHaveLength(2);
    });

    it("should propagate its own level to each RowArtifact", () => {
        const wrapper = getWrapper(table);

        const artifact_rows = wrapper.findAllComponents(RowArtifact);

        expect(artifact_rows).not.toHaveLength(0);
        artifact_rows.forEach((artifact_row) => {
            expect(artifact_row.props("level")).toBe(wrapper.props("level"));
        });
    });

    describe("ancestors propagation", () => {
        it("should propagate ancestors to its rows", () => {
            const wrapper = getWrapper(table);

            const artifact_rows = wrapper.findAllComponents(RowArtifact);

            expect(artifact_rows).not.toHaveLength(0);
            artifact_rows.forEach((artifact_row) => {
                expect(artifact_row.props("ancestors")).toStrictEqual(wrapper.props("ancestors"));
            });
        });

        it("should propagate ancestors to its rows even if it is empty", () => {
            ancestors = [];
            const wrapper = getWrapper(table);

            const artifact_rows = wrapper.findAllComponents(RowArtifact);

            expect(artifact_rows).not.toHaveLength(0);
            artifact_rows.forEach((artifact_row) => {
                expect(artifact_row.props("ancestors")).toStrictEqual(wrapper.props("ancestors"));
            });
        });
    });
});

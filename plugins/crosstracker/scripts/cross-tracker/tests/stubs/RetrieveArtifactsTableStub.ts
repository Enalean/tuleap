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

import { errAsync, okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type {
    ArtifactsTableWithTotal,
    RetrieveArtifactsTable,
} from "../../src/domain/RetrieveArtifactsTable";
import type { ArtifactsTable } from "../../src/domain/ArtifactsTable";
import { TEXT_CELL } from "../../src/domain/ArtifactsTable";
import { ArtifactsTableBuilder } from "../builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../builders/ArtifactRowBuilder";

export const RetrieveArtifactsTableStub = {
    withContent(
        query_table_with_total: ArtifactsTableWithTotal,
        report_table_with_all_artifact: ReadonlyArray<ArtifactsTable>,
    ): RetrieveArtifactsTable {
        return {
            getSelectableQueryResult: () => okAsync(query_table_with_total),
            getSelectableQueryFullResult: () => okAsync(report_table_with_all_artifact),
        };
    },

    withFault(fault: Fault): RetrieveArtifactsTable {
        return {
            getSelectableQueryResult: () => errAsync(fault),
            getSelectableQueryFullResult: () => errAsync(fault),
        };
    },
    withDefaultContent(): RetrieveArtifactsTable {
        const column_name = "SL65 AMG";
        const table = new ArtifactsTableBuilder()
            .withColumn(column_name)
            .withArtifactRow(
                new ArtifactRowBuilder()
                    .addCell(column_name, {
                        type: TEXT_CELL,
                        value: "<p>V12 goes brrr</p>",
                    })
                    .build(),
            )
            .build();

        const table_result = {
            table,
            total: 1,
        };
        return RetrieveArtifactsTableStub.withContent(table_result, [table_result.table]);
    },
};

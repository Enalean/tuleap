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

import type { ArtifactRow, ArtifactsTable } from "../../src/domain/ArtifactsTable";
import type { ArtifactsTableWithTotal } from "../../src/domain/RetrieveArtifactsTable";

export class ArtifactsTableBuilder {
    #columns: Set<string> = new Set();
    #rows: ArtifactRow[] = [];

    public withColumn(column_name: string): this {
        this.#columns.add(column_name);
        return this;
    }

    public withArtifactRow(row: ArtifactRow): this {
        this.#rows.push(row);
        return this;
    }

    public withTotalNumberOfRow(row: ArtifactRow, total_number_of_row: number): this {
        for (let i = 0; i < total_number_of_row; i++) {
            this.#rows.push(row);
        }
        return this;
    }

    public buildWithTotal(total: number): ArtifactsTableWithTotal {
        return {
            table: this.build(),
            total: total,
        };
    }

    public build(): ArtifactsTable {
        return {
            columns: this.#columns,
            rows: this.#rows,
        };
    }
}

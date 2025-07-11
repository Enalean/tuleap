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

import type { ArtifactRow, Cell } from "../../src/domain/ArtifactsTable";

export class ArtifactRowBuilder {
    #id: number = 698;
    #row = {
        expected_number_of_forward_links: 2,
        expected_number_of_reverse_links: 1,
        uri: "/plugins/tracker/?aid=698",
        cells: new Map(),
    };

    public addCell(column_name: string, cell: Cell): this {
        this.#row.cells.set(column_name, cell);
        return this;
    }

    public withRowId(id: number): this {
        this.#id = id;
        return this;
    }

    public buildWithExpectedNumberOfLinks(
        expected_number_of_forward_links: number,
        expected_number_of_reverse_links: number,
    ): ArtifactRow {
        return {
            id: this.#id,
            expected_number_of_forward_links,
            expected_number_of_reverse_links,
            uri: "/plugins/tracker/?aid=698",
            cells: new Map(),
        };
    }

    public build(): ArtifactRow {
        return {
            id: this.#id,
            ...this.#row,
        };
    }
}

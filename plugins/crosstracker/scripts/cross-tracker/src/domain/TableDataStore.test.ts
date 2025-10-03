/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import { describe, it, expect, beforeEach, afterEach } from "vitest";
import type { Emitter } from "mitt";
import mitt from "mitt";
import type { Events } from "../helpers/widget-events";
import { INSERTED_ROW_EVENT, REMOVED_ROW_EVENT } from "../helpers/widget-events";
import { TableDataStore } from "./TableDataStore";
import type { ArtifactRow } from "./ArtifactsTable";
import { v4 as uuidv4 } from "uuid";
import type { ColumnName } from "./ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "./ColumnName";

describe("RowCollectionStore", () => {
    let emitter: Emitter<Events>;
    let store: TableDataStore;

    const row_uuid = uuidv4();
    const parent_row_uuid = uuidv4();

    const row: ArtifactRow = {
        row_uuid: row_uuid,
        artifact_id: 123,
        artifact_uri: "/artifact/123",
        cells: new Map(),
        expected_number_of_forward_links: 0,
        expected_number_of_reverse_links: 0,
        direction: "no-direction",
    };

    const parent_row: ArtifactRow = {
        row_uuid: parent_row_uuid,
        artifact_id: 456,
        artifact_uri: "/artifact/456",
        cells: new Map(),
        expected_number_of_forward_links: 0,
        expected_number_of_reverse_links: 0,
        direction: "no-direction",
    };

    beforeEach(() => {
        emitter = mitt<Events>();
        store = TableDataStore(emitter);

        store.listen();
    });

    afterEach(() => {
        store.removeListeners();
    });

    it("should add row to existing collection", () => {
        emitter.emit(INSERTED_ROW_EVENT, { row, parent_row });

        expect(store.getRowCollection().length).toBe(1);
        expect(store.getRowCollection()[0].parent_row_uuid).toBe(parent_row_uuid);
        expect(store.getRowCollection()[0].row.row_uuid).toBe(row_uuid);
    });

    it("should remove row", () => {
        emitter.emit(REMOVED_ROW_EVENT, { row });

        expect(store.getRowCollection().length).toBe(0);
    });

    it("should empty the store on reset", () => {
        emitter.emit(INSERTED_ROW_EVENT, { row, parent_row });
        emitter.emit(INSERTED_ROW_EVENT, { row, parent_row });
        store.setColumns(new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME));

        expect(store.getRowCollection().length).toBe(2);
        expect(store.getColumns().size).toBe(1);

        store.resetStore();

        expect(store.getRowCollection().length).toBe(0);
        expect(store.getColumns().size).toBe(0);
    });
});

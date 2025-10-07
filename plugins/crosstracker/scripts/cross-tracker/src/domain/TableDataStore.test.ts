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
import { describe, it, expect, beforeEach } from "vitest";
import { TableDataStore } from "./TableDataStore";
import type { ArtifactRow } from "./ArtifactsTable";
import { FORWARD_DIRECTION, NO_DIRECTION, REVERSE_DIRECTION } from "./ArtifactsTable";
import { v4 as uuidv4 } from "uuid";
import type { ColumnName } from "./ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "./ColumnName";

describe("TableDataStore", () => {
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
        direction: "forward",
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
        store = TableDataStore();
    });

    it("should add row to existing collection", () => {
        store.addEntry({ row: parent_row, parent_row_uuid: null });

        expect(store.getRowCollection().length).toBe(1);
        expect(store.getRowCollection()[0].parent_row_uuid).toBe(null);
        expect(store.getRowCollection()[0].row.row_uuid).toBe(parent_row_uuid);
    });

    it("should remove row", () => {
        store.removeEntryByParentUUID(row.row_uuid);

        expect(store.getRowCollection().length).toBe(0);
    });

    it("should empty the store on reset", () => {
        store.addEntry({ row: parent_row, parent_row_uuid: null });
        store.addEntry({ row, parent_row_uuid });
        store.setColumns(new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME));

        expect(store.getRowCollection().length).toBe(2);
        expect(store.getColumns().size).toBe(1);

        store.resetStore();

        expect(store.getRowCollection().length).toBe(0);
        expect(store.getColumns().size).toBe(0);
    });

    describe("Ordering on the stored collection", () => {
        it(`adds the element a the end of the collection if they have no parents
             ┌─────────────────────┐
             │  first element      │
             │                     │
             ┌─────────────────────┐
             │  second element     │
             │                     │
             └─────────────────────┘
        `, () => {
            const first_element = {
                parent_row_uuid: null,
                row: row,
            };

            const second_element = {
                parent_row_uuid: null,
                row: row,
            };

            store.addEntry(first_element);
            store.addEntry(second_element);

            const row_collection = store.getRowCollection();

            expect(row_collection.length).toBe(2);
            expect(row_collection[0]).toStrictEqual(first_element);
            expect(row_collection[1]).toStrictEqual(second_element);
        });

        it(`adds the child directly below the parent
              ┌─────────────────────────┐
              │                         │
              │  first element          │
              │                         │
              └────┌─────────────────────────────┐
                   │                             │
                   │   first child element       │
                   │                             │
              ┌──────────────────────────┐───────┘
              │                          │
              │  second element          │
              │                          │
              └──────────────────────────┘
        `, () => {
            const first_root_element_uuid = uuidv4();
            const second_root_element_uuid = uuidv4();

            const first_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: first_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const second_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: second_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const first_child_element = {
                parent_row_uuid: first_root_element_uuid,
                row: row,
            };

            store.addEntry(first_root_element);
            store.addEntry(second_root_element);
            store.addEntry(first_child_element);

            const row_collection = store.getRowCollection();

            expect(row_collection.length).toBe(3);
            expect(row_collection[0]).toStrictEqual(first_root_element);
            expect(row_collection[1]).toStrictEqual(first_child_element);
            expect(row_collection[2]).toStrictEqual(second_root_element);
        });

        it(`adds the child after the last child of same direction of the parent if it as already some
              ┌─────────────────────────┐
              │   first element         │
              │   no direction          │
              │                         │
              └────┌─────────────────────────────┐
                   │   first child element       │
                   │   forward                   │
                   │                             │
                   ┌─────────────────────────────┐
                   │   second child element      │
                   │   forward                   │
                   │                             │
              ┌──────────────────────────┐───────┘
              │  second element          │
              │  no direction            │
              │                          │
              └──────────────────────────┘
        `, () => {
            const first_root_element_uuid = uuidv4();
            const second_root_element_uuid = uuidv4();
            const first_child_of_first_root_element_uuid = uuidv4();
            const second_child_of_first_root_element_uuid = uuidv4();

            const first_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: first_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const second_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: second_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const first_child_of_first_root_element = {
                parent_row_uuid: first_root_element_uuid,
                row: {
                    row_uuid: first_child_of_first_root_element_uuid,
                    direction: FORWARD_DIRECTION,
                } as ArtifactRow,
            };

            const second_child_of_first_root_element = {
                parent_row_uuid: first_root_element_uuid,
                row: {
                    row_uuid: second_child_of_first_root_element_uuid,
                    direction: FORWARD_DIRECTION,
                } as ArtifactRow,
            };

            store.addEntry(first_root_element);
            store.addEntry(second_root_element);
            store.addEntry(first_child_of_first_root_element);
            store.addEntry(second_child_of_first_root_element);

            const row_collection = store.getRowCollection();

            expect(row_collection.length).toBe(4);
            expect(row_collection[0]).toStrictEqual(first_root_element);
            expect(row_collection[1]).toStrictEqual(first_child_of_first_root_element);
            expect(row_collection[2]).toStrictEqual(second_child_of_first_root_element);
            expect(row_collection[3]).toStrictEqual(second_root_element);
        });

        it(`adds the child directly after the parent if it is a FORWARD_DIRECTION AND there
            are no previous FORWARD_DIRECTION children
              ┌─────────────────────────┐
              │   first element         │
              │   no direction          │
              │                         │
              └────┌─────────────────────────────┐
                   │   first child element       │
                   │   forward                   │
                   │                             │
                   ┌─────────────────────────────┐
                   │   second child element      │
                   │   reverse                   │
                   │                             │
              ┌──────────────────────────┐───────┘
              │  second element          │
              │  no direction            │
              │                          │
              └──────────────────────────┘
          `, () => {
            const first_root_element_uuid = uuidv4();
            const second_root_element_uuid = uuidv4();
            const first_child_of_first_root_element_uuid = uuidv4();
            const second_child_of_first_root_element_uuid = uuidv4();

            const first_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: first_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const second_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: second_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const first_child_of_first_root_element = {
                parent_row_uuid: first_root_element_uuid,
                row: {
                    row_uuid: first_child_of_first_root_element_uuid,
                    direction: FORWARD_DIRECTION,
                } as ArtifactRow,
            };

            const second_child_of_first_root_element = {
                parent_row_uuid: first_root_element_uuid,
                row: {
                    row_uuid: second_child_of_first_root_element_uuid,
                    direction: REVERSE_DIRECTION,
                } as ArtifactRow,
            };

            store.addEntry(first_root_element);
            store.addEntry(second_root_element);
            store.addEntry(second_child_of_first_root_element);
            store.addEntry(first_child_of_first_root_element);

            const row_collection = store.getRowCollection();

            expect(row_collection.length).toBe(4);
            expect(row_collection[0]).toStrictEqual(first_root_element);
            expect(row_collection[1]).toStrictEqual(first_child_of_first_root_element);
            expect(row_collection[2]).toStrictEqual(second_child_of_first_root_element);
            expect(row_collection[3]).toStrictEqual(second_root_element);
        });

        it(`adds the child at the end of the parent’s children if it is a REVERSE_DIRECTION AND there
            are no previous REVERSE_DIRECTION children
              ┌─────────────────────────┐
              │   first element         │
              │   no direction          │
              │                         │
              └────┌─────────────────────────────┐
                   │   first child element       │
                   │   forward                   │
                   │                             │
                   ┌─────────────────────────────┐
                   │   second child element      │
                   │   reverse                   │
                   │                             │
              ┌──────────────────────────┐───────┘
              │  second element          │
              │  no direction            │
              │                          │
              └──────────────────────────┘
          `, () => {
            const first_root_element_uuid = uuidv4();
            const second_root_element_uuid = uuidv4();
            const first_child_of_first_root_element_uuid = uuidv4();
            const second_child_of_first_root_element_uuid = uuidv4();

            const first_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: first_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const second_root_element = {
                parent_row_uuid: null,
                row: {
                    row_uuid: second_root_element_uuid,
                    direction: NO_DIRECTION,
                } as ArtifactRow,
            };

            const first_child_of_first_root_element = {
                parent_row_uuid: first_root_element_uuid,
                row: {
                    row_uuid: first_child_of_first_root_element_uuid,
                    direction: FORWARD_DIRECTION,
                } as ArtifactRow,
            };

            const second_child_of_first_root_element = {
                parent_row_uuid: first_root_element_uuid,
                row: {
                    row_uuid: second_child_of_first_root_element_uuid,
                    direction: REVERSE_DIRECTION,
                } as ArtifactRow,
            };

            store.addEntry(first_root_element);
            store.addEntry(second_root_element);
            store.addEntry(first_child_of_first_root_element);
            store.addEntry(second_child_of_first_root_element);

            const row_collection = store.getRowCollection();

            expect(row_collection.length).toBe(4);
            expect(row_collection[0]).toStrictEqual(first_root_element);
            expect(row_collection[1]).toStrictEqual(first_child_of_first_root_element);
            expect(row_collection[2]).toStrictEqual(second_child_of_first_root_element);
            expect(row_collection[3]).toStrictEqual(second_root_element);
        });
    });
});

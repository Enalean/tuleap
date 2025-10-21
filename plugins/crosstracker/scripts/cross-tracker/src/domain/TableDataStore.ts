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

import type { ArtifactRow, ArtifactsTable } from "./ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "./ArtifactsTable";

export type RowEntry = {
    readonly parent_row_uuid: string | null;
    readonly row: ArtifactRow;
};

export type TableDataStore = {
    getRowCollection(): Array<RowEntry>;
    resetStore(): void;
    getParentByUUId(uuid: string): RowEntry | undefined;
    setColumns(columns: ArtifactsTable["columns"]): void;
    getColumns(): ArtifactsTable["columns"];
    addEntry(row: RowEntry): void;
    removeEntryByParentUUID(parent_uuid: string): void;
};

export const TableDataStore = (): TableDataStore => {
    const ELEMENT_OF_COLLECTION_NOT_FOUND = -1;
    let row_collection: Array<RowEntry> = [];
    let table_columns: ArtifactsTable["columns"] = new Set();

    const parentAlreadyHasChildren = (parent: RowEntry): boolean => {
        return (
            row_collection.find((entry) => entry.parent_row_uuid === parent.row.row_uuid) !==
            undefined
        );
    };

    const parentAlreadyHasChildrenOfSameDirection = (
        parent: RowEntry,
        child: RowEntry,
    ): boolean => {
        return (
            row_collection.findLastIndex(
                (entry) =>
                    entry.parent_row_uuid === parent.row.row_uuid &&
                    child.row.direction === entry.row.direction,
            ) !== ELEMENT_OF_COLLECTION_NOT_FOUND
        );
    };

    const isATopLevelRow = (row: RowEntry): boolean => {
        return row.parent_row_uuid === null;
    };

    const getParentOfRow = (row: RowEntry): RowEntry => {
        const parent = row_collection.find((entry) => entry.row.row_uuid === row.parent_row_uuid);

        if (parent === undefined) {
            throw new Error("Parent is not found in collection");
        }
        return parent;
    };

    const isLinkOfDirection = (row: RowEntry, direction: string): boolean => {
        return row.row.direction === direction;
    };

    const getInsertionIndexForParentWithChildren = (parent: RowEntry, row: RowEntry): number => {
        if (parentAlreadyHasChildrenOfSameDirection(parent, row)) {
            return row_collection.findLastIndex(
                (entry) =>
                    entry.parent_row_uuid === parent.row.row_uuid &&
                    row.row.direction === entry.row.direction,
            );
        }
        if (isLinkOfDirection(row, FORWARD_DIRECTION)) {
            return row_collection.indexOf(parent);
        }
        if (isLinkOfDirection(row, REVERSE_DIRECTION)) {
            return row_collection.findLastIndex(
                (entry) => entry.parent_row_uuid === parent.row.row_uuid,
            );
        }
        return ELEMENT_OF_COLLECTION_NOT_FOUND;
    };

    const removeEntry = (uuid: string): void => {
        const direct_children = row_collection.filter((item) => item.parent_row_uuid === uuid);

        for (const child of direct_children) {
            removeEntry(child.row.row_uuid);
        }

        row_collection = row_collection.filter((item) => item.parent_row_uuid !== uuid);
    };

    return {
        getRowCollection(): Array<RowEntry> {
            return row_collection;
        },

        resetStore(): void {
            row_collection = [];
            table_columns = new Set();
        },

        getParentByUUId(uuid: string): RowEntry | undefined {
            const current_item = row_collection.find((item) => item.row.row_uuid === uuid);

            return row_collection.find(
                (item) => item.row.row_uuid === current_item?.parent_row_uuid,
            );
        },

        setColumns(columns: ArtifactsTable["columns"]): void {
            table_columns = columns;
        },

        getColumns(): ArtifactsTable["columns"] {
            return table_columns;
        },

        addEntry(row: RowEntry): void {
            if (isATopLevelRow(row)) {
                row_collection.push(row);
                return;
            }
            const parent = getParentOfRow(row);

            let insertion_index = ELEMENT_OF_COLLECTION_NOT_FOUND;

            if (parentAlreadyHasChildren(parent)) {
                insertion_index = getInsertionIndexForParentWithChildren(parent, row);
            } else {
                insertion_index = row_collection.indexOf(parent);
            }

            if (insertion_index === ELEMENT_OF_COLLECTION_NOT_FOUND) {
                throw new Error("Error while trying to determine parent’s position");
            }

            row_collection.splice(insertion_index + 1, 0, row);
        },
        removeEntryByParentUUID(uuid: string): void {
            removeEntry(uuid);
        },
    };
};

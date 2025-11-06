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
import { Option } from "@tuleap/option";

export type RowEntry = {
    readonly parent_row_uuid: string | null;
    readonly row: ArtifactRow;
};

export type TableDataStore = {
    getRowCollection(): Array<RowEntry>;
    resetStore(): void;
    setColumns(columns: ArtifactsTable["columns"]): void;
    getColumns(): ArtifactsTable["columns"];
    addEntry(row: RowEntry): void;
    removeEntryByParentUUID(parent_uuid: string): void;
    retrieveParentOfRow(row: RowEntry): RowEntry;
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

    const getParentOfRow = (row: RowEntry): Option<RowEntry> => {
        const parent = row_collection.find((entry) => entry.row.row_uuid === row.parent_row_uuid);

        if (parent === undefined) {
            return Option.nothing();
        }
        return Option.fromValue(parent);
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

    const wouldCreateCircularReference = (row_entry: RowEntry, parent_row: RowEntry): boolean => {
        const great_daddy = getParentOfRow(parent_row);
        return great_daddy.match(
            (value) => {
                return value.row.artifact_id === row_entry.row.artifact_id;
            },
            () => {
                return false;
            },
        );
    };

    const addChildEntry = (row: RowEntry, parent__entry: RowEntry): void => {
        if (wouldCreateCircularReference(row, parent__entry)) {
            return;
        }

        const is_duplicate = row_collection.some(
            (entry) =>
                entry.parent_row_uuid === row.parent_row_uuid &&
                entry.row.artifact_id === row.row.artifact_id,
        );

        if (is_duplicate) {
            return;
        }

        let insertion_index = ELEMENT_OF_COLLECTION_NOT_FOUND;

        if (parentAlreadyHasChildren(parent__entry)) {
            insertion_index = getInsertionIndexForParentWithChildren(parent__entry, row);
        } else {
            insertion_index = row_collection.indexOf(parent__entry);
        }

        if (insertion_index === ELEMENT_OF_COLLECTION_NOT_FOUND) {
            throw new Error("Error while trying to determine parent’s position");
        }

        row_collection.splice(insertion_index + 1, 0, row);
    };

    return {
        getRowCollection(): Array<RowEntry> {
            return [...row_collection];
        },

        resetStore(): void {
            row_collection = [];
            table_columns = new Set();
        },

        setColumns(columns: ArtifactsTable["columns"]): void {
            table_columns = columns;
        },

        getColumns(): ArtifactsTable["columns"] {
            return new Set(table_columns);
        },

        addEntry(row: RowEntry): void {
            if (isATopLevelRow(row)) {
                row_collection.push(row);
                return;
            }

            const parent = getParentOfRow(row);
            parent.match(
                (parent__entry) => addChildEntry(row, parent__entry),
                () => {},
            );
        },
        removeEntryByParentUUID(uuid: string): void {
            removeEntry(uuid);
        },

        retrieveParentOfRow(row: RowEntry): RowEntry {
            const parent = getParentOfRow(row);
            return parent.match(
                (row_entry) => {
                    return row_entry;
                },
                () => {
                    throw new Error("Parent not found");
                },
            );
        },
    };
};

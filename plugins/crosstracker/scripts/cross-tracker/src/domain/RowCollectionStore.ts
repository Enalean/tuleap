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

import type { ArtifactRow } from "./ArtifactsTable";
import type { Events, InsertedRowEvent, RemovedRowEvent } from "../helpers/widget-events";
import { INSERTED_ROW_EVENT, REMOVED_ROW_EVENT } from "../helpers/widget-events";
import type { Emitter } from "mitt";

export type RowEntry = {
    readonly parent_row_uuid: string | null;
    readonly row: ArtifactRow;
};

export type RowCollectionStore = {
    listen(): void;
    removeListeners(): void;
    getRowCollection(): Array<RowEntry>;
    resetStore(): void;
    getParentByUUId(uuid: string): RowEntry | undefined;
};

export const RowCollectionStore = (emitter: Emitter<Events>): RowCollectionStore => {
    let row_collection: Array<RowEntry> = [];

    const handleInsertedRowEvent = (event: InsertedRowEvent): void => {
        row_collection.push({
            parent_row_uuid: event.parent_row ? event.parent_row.row_uuid : null,
            row: event.row,
        });
    };

    const handleRemovedRowEvent = (event: RemovedRowEvent): void => {
        row_collection = row_collection.filter((item) => item.row.row_uuid !== event.row.row_uuid);
    };

    return {
        listen(): void {
            emitter.on(INSERTED_ROW_EVENT, handleInsertedRowEvent);
            emitter.on(REMOVED_ROW_EVENT, handleRemovedRowEvent);
        },

        removeListeners(): void {
            emitter.off(INSERTED_ROW_EVENT, handleInsertedRowEvent);
            emitter.off(REMOVED_ROW_EVENT, handleRemovedRowEvent);
        },

        getRowCollection(): Array<RowEntry> {
            return row_collection;
        },

        resetStore(): void {
            row_collection = [];
        },

        getParentByUUId(uuid: string): RowEntry | undefined {
            const current_item = row_collection.find((item) => item.row.row_uuid === uuid);

            return row_collection.find(
                (item) => item.row.row_uuid === current_item?.parent_row_uuid,
            );
        },
    };
};
